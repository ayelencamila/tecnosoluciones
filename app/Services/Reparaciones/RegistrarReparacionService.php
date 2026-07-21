<?php

namespace App\Services\Reparaciones;

use App\Exceptions\Ventas\SinStockException;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\DetalleReparacion;
use App\Models\EstadoReparacion;
use App\Models\EtapaImagenReparacion;
use App\Models\ImagenReparacion;
use App\Models\MovimientoStock;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Models\Stock;
use App\Models\TipoMovimientoStock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarReparacionService
{
    public function handle(array $datosValidados, int $usuarioID): Reparacion
    {
        // 1. VALIDACIÓN PREVIA (Optimista - Fail Fast)
        if (! empty($datosValidados['items'])) {
            $this->validarStockPrevio($datosValidados['items']);
        }

        return DB::transaction(function () use ($datosValidados, $usuarioID) {

            // 2. PREPARAR DATOS MAESTROS
            $estadoInicial = EstadoReparacion::where('nombreEstado', 'Recibido')->firstOrFail();
            $codigoReparacion = 'REP-'.Carbon::now()->format('Ymd').'-'.time();

            // 3. CREAR LA REPARACIÓN (Cabecera)
            $reparacion = Reparacion::create([
                // Tenant: se atribuye a la empresa del usuario que registra (scoping manual).
                'empresa_id' => auth()->user()?->empresa_id ?? 1,
                'clienteID' => $datosValidados['clienteID'],
                'tecnico_id' => $datosValidados['tecnico_id'], // CU-11 Paso 5: Técnico asignado
                'estado_reparacion_id' => $estadoInicial->estadoReparacionID,
                'codigo_reparacion' => $codigoReparacion,
                'modelo_id' => $datosValidados['modelo_id'],
                'numero_serie_imei' => $datosValidados['numero_serie_imei'] ?? null,
                'clave_bloqueo' => $datosValidados['clave_bloqueo'] ?? null,
                'accesorios_dejados' => $datosValidados['accesorios_dejados'] ?? null,
                'falla_declarada' => $datosValidados['falla_declarada'],
                'observaciones' => $datosValidados['observaciones'] ?? null,
                'fecha_ingreso' => Carbon::now(),
                'fecha_promesa' => $datosValidados['fecha_promesa'] ?? null,
                // Presupuesto inmediato (flujo simplificado)
                'costo_mano_obra' => $datosValidados['costo_mano_obra'] ?? 0,
                'total_final' => $datosValidados['total_final'] ?? 0,
            ]);

            // 3.1 REGISTRAR EN AUDITORÍA (CU-11 Paso 10)
            Auditoria::registrar(
                accion: Auditoria::ACCION_CREAR_REPARACION,
                tabla: 'reparaciones',
                registroId: $reparacion->reparacionID,
                datosAnteriores: null,
                datosNuevos: $reparacion->toArray(),
                motivo: "Ingreso de reparación {$codigoReparacion} - Cliente: {$reparacion->clienteID}",
                detalles: "Falla: {$datosValidados['falla_declarada']}",
                usuarioId: $usuarioID
            );

            // 4. GUARDAR IMÁGENES
            if (isset($datosValidados['imagenes'])) {
                $this->procesarImagenes($reparacion, $datosValidados['imagenes']);
            }

            // 5. PROCESAR ITEMS (Repuestos iniciales) CON BLOQUEO
            if (! empty($datosValidados['items'])) {
                $this->procesarItems($reparacion, $datosValidados['items'], $usuarioID);
            }

            // 6. CU-32: Registrar comprobante de Ingreso de Reparación
            $tipoComprobante = \DB::table('tipos_comprobante')->where('codigo', 'INGRESO_REPARACION')->value('tipo_id');
            $estadoEmitido = \DB::table('estados_comprobante')->where('nombre', 'EMITIDO')->value('estado_id');

            if ($tipoComprobante && $estadoEmitido) {
                \App\Models\Comprobante::create([
                    'tipo_entidad' => $reparacion->getMorphClass(),
                    'entidad_id' => $reparacion->reparacionID,
                    'usuario_id' => $usuarioID,
                    'tipo_comprobante_id' => $tipoComprobante,
                    'numero_correlativo' => 'ING-'.$codigoReparacion,
                    'fecha_emision' => now(),
                    'estado_comprobante_id' => $estadoEmitido,
                ]);
            }

            Log::info("Reparación registrada con éxito: ID {$reparacion->reparacionID} - Código: {$codigoReparacion}");

            return $reparacion;
        });
    }

    private function procesarImagenes(Reparacion $reparacion, array $imagenes): void
    {
        foreach ($imagenes as $imagen) {
            if ($imagen instanceof UploadedFile) {
                $ruta = $imagen->storePublicly(
                    'reparaciones/'.date('Y')."/{$reparacion->reparacionID}",
                    'public'
                );

                ImagenReparacion::create([
                    'reparacion_id' => $reparacion->reparacionID,
                    'ruta_archivo' => $ruta,
                    'nombre_original' => $imagen->getClientOriginalName(),
                    'etapa_id' => EtapaImagenReparacion::where('nombre', 'ingreso')->value('etapa_id'),
                ]);
            }
        }
    }

    private function obtenerPrecioParaCliente(Producto $producto, Cliente $cliente): float
    {
        $precioProducto = PrecioProducto::where('productoID', $producto->id)
            ->where('tipoClienteID', $cliente->tipoClienteID)
            ->where('fechaDesde', '<=', Carbon::now())
            ->where(function ($query) {
                $query->where('fechaHasta', '>=', Carbon::now())
                    ->orWhereNull('fechaHasta');
            })
            ->orderBy('fechaDesde', 'desc')
            ->first();

        return (float) ($precioProducto?->precio ?? $producto->precios()->latest('fechaDesde')->first()?->precio ?? 0);
    }

    private function procesarItems(Reparacion $reparacion, array $items, int $usuarioID): void
    {
        $cliente = Cliente::findOrFail($reparacion->clienteID);

        foreach ($items as $itemData) {
            $producto = Producto::findOrFail($itemData['producto_id']);

            // Precio según tipo de cliente (mayorista/minorista)
            $precioUnitario = $this->obtenerPrecioParaCliente($producto, $cliente);

            $cantidad = $itemData['cantidad'];
            $subtotal = $precioUnitario * $cantidad;

            // Registrar detalle
            DetalleReparacion::create([
                'reparacion_id' => $reparacion->reparacionID,
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
            ]);

            // Descuento de Stock Seguro (los servicios no descuentan stock)
            if (! $producto->es_servicio) {
                $this->descontarStock($producto, $cantidad, $reparacion, $usuarioID);
            }
        }
    }

    private function descontarStock(Producto $producto, int $cantidad, Reparacion $reparacion, int $usuarioID): void
    {
        // 1. Obtener Tipo de Movimiento Dinámicamente (SIN HARDCODEO)
        $tipoMovimiento = TipoMovimientoStock::where('nombre', 'Salida (Venta)')->first();

        if (! $tipoMovimiento) {
            throw new \Exception("Error de Configuración Crítico: No se encontró el tipo de movimiento 'Salida (Venta)' en la base de datos.");
        }

        // 2. CORRECCIÓN ACID: Bloqueo pesimista
        $stockRegistro = Stock::where('productoID', $producto->id)
            ->lockForUpdate() // PARA EVITAR CONDICIÓN DE CARRERA
            ->first();

        if (! $stockRegistro) {
            throw new SinStockException($producto->nombre, $cantidad, 0, 'No hay registro de stock para este producto.');
        }

        // 3. Validación estricta dentro del bloqueo
        if ($stockRegistro->cantidad_disponible < $cantidad) {
            throw new SinStockException($producto->nombre, $cantidad, $stockRegistro->cantidad_disponible);
        }

        $stockAnterior = $stockRegistro->cantidad_disponible;

        $stockRegistro->decrement('cantidad_disponible', $cantidad);

        // 4. Crear Movimiento usando datos dinámicos
        MovimientoStock::create([
            'stock_id' => $stockRegistro->stock_id,
            'productoID' => $producto->id,
            'tipo_movimiento_id' => $tipoMovimiento->id,
            'cantidad' => $cantidad,
            'stockAnterior' => $stockAnterior,
            'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
            'motivo' => 'Uso en Reparación: '.$reparacion->codigo_reparacion,
            'referenciaID' => $reparacion->reparacionID,
            'referenciaTabla' => 'reparaciones',
            'user_id' => $usuarioID,
            'fecha_movimiento' => now(),
        ]);
    }

    private function validarStockPrevio(array $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::findOrFail($item['producto_id']);

            if (! $producto->es_servicio) {
                if (! $producto->tieneStock($item['cantidad'])) {
                    throw new SinStockException($producto->nombre, $item['cantidad'], $producto->stock_total);
                }
            }
        }
    }
}
