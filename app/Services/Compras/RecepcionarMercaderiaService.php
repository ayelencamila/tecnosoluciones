<?php

namespace App\Services\Compras;

use App\Models\OrdenCompra;
use App\Models\RecepcionMercaderia;
use App\Models\DetalleRecepcion;
use App\Models\DetalleOrdenCompra;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\TipoMovimientoStock;
use App\Models\EstadoOrdenCompra;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * CU-23: Recepcionar Mercadería
 * 
 * Servicio que orquesta el proceso de recepción de mercadería:
 * 1. Valida la orden de compra
 * 2. Valida las cantidades a recibir
 * 3. Crea el registro de recepción
 * 4. Actualiza el stock de productos
 * 5. Actualiza el estado de la OC
 * 6. Registra en auditoría
 * 
 * Lineamientos:
 * - Larman: Controlador de caso de uso
 * - GRASP: Alta cohesión, bajo acoplamiento
 */
class RecepcionarMercaderiaService
{
    /**
     * Ejecuta el proceso de recepción de mercadería
     * 
     * @param int $ordenCompraId ID de la orden de compra
     * @param array $items Array de items [{detalle_orden_id, cantidad_recibida, observacion_item?}]
     * @param string $observaciones Motivo/observación general de la recepción
     * @param int $usuarioId ID del usuario que recepciona
     * @return RecepcionMercaderia
     * @throws Exception
     */
    public function ejecutar(int $ordenCompraId, array $items, string $observaciones, int $usuarioId): RecepcionMercaderia
    {
        return DB::transaction(function () use ($ordenCompraId, $items, $observaciones, $usuarioId) {
            // 1. Validar orden de compra
            $orden = $this->validarOrdenCompra($ordenCompraId);
            
            // 2. Validar cantidades
            $this->validarCantidades($orden, $items);
            
            // 3. Determinar tipo de recepción
            $tipoRecepcion = $this->determinarTipoRecepcion($orden, $items);
            
            // 4. Crear registro de recepción
            $recepcion = $this->crearRecepcion($orden, $observaciones, $tipoRecepcion, $usuarioId);
            
            // 5. Procesar items y actualizar stock
            $this->procesarItems($recepcion, $orden, $items, $usuarioId);
            
            // 6. Actualizar estado de la OC
            $this->actualizarEstadoOrden($orden, $tipoRecepcion);
            
            // 7. Registrar en auditoría
            $this->registrarAuditoria($recepcion, $usuarioId);
            
            Log::info("CU-23: Recepción {$recepcion->numero_recepcion} creada para OC {$orden->numero_oc}", [
                'recepcion_id' => $recepcion->id,
                'orden_id' => $orden->id,
                'tipo' => $tipoRecepcion,
                'items' => count($items),
            ]);
            
            return $recepcion;
        });
    }

    /**
     * Valida que la OC exista y esté en estado válido para recepción
     * 
     * Excepción 3a: Orden no encontrada o no apta para recepción
     */
    private function validarOrdenCompra(int $ordenCompraId): OrdenCompra
    {
        $orden = OrdenCompra::with(['detalles.producto', 'estado'])->find($ordenCompraId);
        
        if (!$orden) {
            throw new Exception("Orden de Compra no encontrada.");
        }
        
        $estadosValidos = [
            EstadoOrdenCompra::ENVIADA,
            EstadoOrdenCompra::CONFIRMADA,
            EstadoOrdenCompra::RECIBIDA_PARCIAL,
        ];
        
        if (!in_array($orden->estado->nombre, $estadosValidos)) {
            throw new Exception("La Orden de Compra no está en un estado válido para recepción. Estado actual: {$orden->estado->nombre}");
        }
        
        return $orden;
    }

    /**
     * Valida las cantidades a recibir
     * 
     * Excepción 5a: Cantidad excede lo pendiente
     * Excepción 5b: Producto no corresponde a la OC
     */
    private function validarCantidades(OrdenCompra $orden, array $items): void
    {
        foreach ($items as $item) {
            $detalleOrden = $orden->detalles->firstWhere('id', $item['detalle_orden_id']);
            
            if (!$detalleOrden) {
                throw new Exception("El ítem con ID {$item['detalle_orden_id']} no pertenece a esta Orden de Compra.");
            }
            
            $cantidadPendiente = $detalleOrden->cantidad_pedida - $detalleOrden->cantidad_recibida;
            
            if ($item['cantidad_recibida'] > $cantidadPendiente) {
                throw new Exception(
                    "La cantidad a recibir ({$item['cantidad_recibida']}) excede la cantidad pendiente ({$cantidadPendiente}) " .
                    "para el producto '{$detalleOrden->producto->nombre}'."
                );
            }
            
            if ($item['cantidad_recibida'] < 0) {
                throw new Exception("La cantidad a recibir no puede ser negativa.");
            }
        }
    }

    /**
     * Determina si la recepción es parcial o total
     */
    private function determinarTipoRecepcion(OrdenCompra $orden, array $items): string
    {
        $totalPendienteAntes = $orden->detalles->sum(function ($detalle) {
            return $detalle->cantidad_pedida - $detalle->cantidad_recibida;
        });
        
        $totalARecibir = collect($items)->sum('cantidad_recibida');
        
        if ($totalARecibir >= $totalPendienteAntes) {
            return RecepcionMercaderia::TIPO_TOTAL;
        }
        
        return RecepcionMercaderia::TIPO_PARCIAL;
    }

    /**
     * Crea el registro principal de recepción
     */
    private function crearRecepcion(OrdenCompra $orden, string $observaciones, string $tipo, int $usuarioId): RecepcionMercaderia
    {
        return RecepcionMercaderia::create([
            'numero_recepcion' => RecepcionMercaderia::generarNumeroRecepcion(),
            'orden_compra_id' => $orden->id,
            'user_id' => $usuarioId,
            'fecha_recepcion' => now(),
            'observaciones' => $observaciones,
            'tipo' => $tipo,
        ]);
    }

    /**
     * Procesa cada ítem: crea detalle de recepción, actualiza cantidad recibida en OC, actualiza stock
     */
    private function procesarItems(RecepcionMercaderia $recepcion, OrdenCompra $orden, array $items, int $usuarioId): void
    {
        foreach ($items as $item) {
            if ($item['cantidad_recibida'] <= 0) {
                continue; // Ignorar items con cantidad 0
            }
            
            $detalleOrden = $orden->detalles->firstWhere('id', $item['detalle_orden_id']);
            
            // Crear detalle de recepción (sin producto_id - 3FN)
            DetalleRecepcion::create([
                'recepcion_id' => $recepcion->id,
                'detalle_orden_id' => $detalleOrden->id,
                // producto_id se obtiene vía detalleOrden (3FN)
                'cantidad_recibida' => $item['cantidad_recibida'],
                'observacion_item' => $item['observacion_item'] ?? null,
            ]);
            
            // Actualizar cantidad recibida en el detalle de la OC
            $detalleOrden->increment('cantidad_recibida', $item['cantidad_recibida']);
            
            // Actualizar stock del producto
            $this->actualizarStock($detalleOrden->producto_id, $item['cantidad_recibida'], $recepcion, $usuarioId);
        }
    }

    /**
     * Actualiza el stock del producto
     * 
     * Excepción 10a: Error al actualizar stock (se maneja con try-catch y alerta interna)
     */
    private function actualizarStock(int $productoId, int $cantidad, RecepcionMercaderia $recepcion, int $usuarioId): void
    {
        try {
            // Buscar o crear stock en depósito principal (ID 1)
            $stock = Stock::firstOrCreate(
                ['producto_id' => $productoId, 'deposito_id' => 1],
                ['cantidad_disponible' => 0, 'cantidad_reservada' => 0]
            );
            
            // Incrementar stock
            $stockAnterior = $stock->cantidad_disponible;
            $stock->increment('cantidad_disponible', $cantidad);
            
            // Registrar movimiento de stock (usa nombre del TipoMovimientoStockSeeder)
            $tipoIngreso = TipoMovimientoStock::where('nombre', 'Entrada (Compra)')->first();
            
            MovimientoStock::create([
                'producto_id' => $productoId,
                'deposito_id' => 1,
                'tipo_movimiento_id' => $tipoIngreso?->id,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_posterior' => $stock->cantidad_disponible,
                'referencia_tipo' => 'recepcion_mercaderia',
                'referencia_id' => $recepcion->id,
                'usuario_id' => $usuarioId,
                'observacion' => "Recepción de mercadería {$recepcion->numero_recepcion}",
            ]);
            
        } catch (Exception $e) {
            // Excepción 10a: Registrar incidente pero continuar
            Log::error("CU-23 Excepción 10a: Error al actualizar stock para producto {$productoId}", [
                'recepcion_id' => $recepcion->id,
                'error' => $e->getMessage(),
            ]);
            
            // La recepción continúa, se genera alerta interna
            // En un sistema real, esto enviaría una notificación al admin
        }
    }

    /**
     * Actualiza el estado de la orden de compra
     * 
     * Excepción 10b: Error al actualizar estado (se maneja con try-catch)
     */
    private function actualizarEstadoOrden(OrdenCompra $orden, string $tipoRecepcion): void
    {
        try {
            $nuevoEstado = $tipoRecepcion === RecepcionMercaderia::TIPO_TOTAL
                ? EstadoOrdenCompra::RECIBIDA_TOTAL
                : EstadoOrdenCompra::RECIBIDA_PARCIAL;
            
            $orden->update([
                'estado_id' => EstadoOrdenCompra::idPorNombre($nuevoEstado),
            ]);
            
        } catch (Exception $e) {
            // Excepción 10b: Registrar incidente pero la recepción ya se aplicó
            Log::error("CU-23 Excepción 10b: Error al actualizar estado de OC {$orden->numero_oc}", [
                'orden_id' => $orden->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Registra la operación en auditoría
     * 
     * Excepción 11a: Error al registrar en historial (se maneja con try-catch)
     */
    private function registrarAuditoria(RecepcionMercaderia $recepcion, int $usuarioId): void
    {
        try {
            Auditoria::create([
                'tabla' => 'recepciones_mercaderia',
                'registro_id' => $recepcion->id,
                'accion' => 'crear',
                'usuario_id' => $usuarioId,
                'datos_nuevos' => $recepcion->toJson(),
                'ip' => request()->ip(),
            ]);
        } catch (Exception $e) {
            // Excepción 11a: Registrar incidente pero la recepción ya se completó
            Log::error("CU-23 Excepción 11a: Error al registrar en auditoría", [
                'recepcion_id' => $recepcion->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
