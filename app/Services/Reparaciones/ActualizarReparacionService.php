<?php

namespace App\Services\Reparaciones;

use App\Models\Reparacion;
use App\Models\DetalleReparacion;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\EstadoReparacion;
use App\Models\TipoMovimientoStock;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\PrecioProducto;
use App\Exceptions\Ventas\SinStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ActualizarReparacionService
{
    /**
     * Mapa de Transiciones Válidas por ID de Estado
     * 
     * FLUJO SIMPLIFICADO (sin Diagnóstico ni Presupuestado):
     * El cliente da presupuesto al momento del ingreso.
     * 
     * Estados según BD (nueva numeración):
     * 1 = Recibido (con presupuesto)
     * 2 = En Reparación
     * 3 = Espera de Repuesto [PAUSA SLA]
     * 4 = Reparado (listo para entregar) [PAUSA SLA]
     * 5 = Entregado (estado final)
     * 6 = Anulado (estado final)
     * 
     * Flujo principal: 1 → 2 → 4 → 5
     * Con desvío:      2 → 3 → 2 (espera repuesto)
     * Anulación:       Desde cualquier estado no-final → 6
     */
    private const TRANSICIONES_VALIDAS = [
        1 => [2, 3, 4, 6],       // Recibido → En Reparación, Espera Repuesto, Reparado, Anulado
        2 => [1, 3, 4, 6],       // En Reparación → Recibido (corrección), Espera Repuesto, Reparado, Anulado
        3 => [2, 4, 6],          // Espera de Repuesto → En Reparación (retoma), Reparado, Anulado
        4 => [2, 5, 6],          // Reparado → En Reparación (volver a trabajar), Entregado, Anulado
        5 => [],                 // Entregado (estado final)
        6 => [],                 // Anulado (estado final)
    ];

    public function handle(Reparacion $reparacion, array $datos, int $userId): Reparacion
    {
        // CU-12 Pre-condición/Excepción 3b: Validar que no esté en estado final
        if ($reparacion->estado->esFinal()) {
            throw new \DomainException("La reparación se encuentra en estado '{$reparacion->estado->nombreEstado}' que no permite modificaciones.");
        }

        // 2. Validar Transición de Estado usando IDs (respeta la BD)
        $estadoActualId = $reparacion->estado_reparacion_id;
        $nuevoEstadoId = $datos['estado_reparacion_id'];
        $nuevoEstado = EstadoReparacion::findOrFail($nuevoEstadoId);
        
        // CU-12 Excepción 5b: Validar transición de estado permitida
        if ($estadoActualId != $nuevoEstadoId) {
            if (!$this->esTransicionValidaPorId($estadoActualId, $nuevoEstadoId)) {
                throw new \DomainException("Cambio de estado no permitido: No se puede pasar de '{$reparacion->estado->nombreEstado}' a '{$nuevoEstado->nombreEstado}'. Por favor, seleccione un estado válido para el flujo actual.");
            }
        }

        return DB::transaction(function () use ($reparacion, $datos, $nuevoEstado, $userId) {
            
            // 3. Actualizar Cabecera 
            $reparacion->update([
                // Gestión
                'estado_reparacion_id' => $nuevoEstado->estadoReparacionID,
                'diagnostico_tecnico'  => $datos['diagnostico_tecnico'] ?? $reparacion->diagnostico_tecnico,
                'observaciones'        => $datos['observaciones'] ?? $reparacion->observaciones,
                'tecnico_id'           => $datos['tecnico_id'] ?? $reparacion->tecnico_id ?? $userId,
                'costo_mano_obra'      => $datos['costo_mano_obra'] ?? $reparacion->costo_mano_obra,
                'total_final'          => $datos['total_final'] ?? $reparacion->total_final,
                
                // Datos del Equipo (Corrección de Ingreso)
                'modelo_id'            => $datos['modelo_id'] ?? $reparacion->modelo_id,
                'numero_serie_imei'    => $datos['numero_serie_imei'] ?? $reparacion->numero_serie_imei,
                'clave_bloqueo'        => $datos['clave_bloqueo'] ?? $reparacion->clave_bloqueo,
                'accesorios_dejados'   => $datos['accesorios_dejados'] ?? $reparacion->accesorios_dejados,
                'falla_declarada'      => $datos['falla_declarada'] ?? $reparacion->falla_declarada,
                'fecha_promesa'        => $datos['fecha_promesa'] ?? $reparacion->fecha_promesa,
            ]);

            // 4. Procesar Nuevos Repuestos
            if (!empty($datos['repuestos'])) {
                foreach ($datos['repuestos'] as $item) {
                    $this->agregarRepuesto($reparacion, $item, $userId);
                }
            }

            // 5. Lógica de Cierre (ID 5 = Entregado según nueva numeración BD)
            if ($nuevoEstado->estadoReparacionID == 5 && !$reparacion->fecha_entrega_real) {
                $reparacion->update(['fecha_entrega_real' => now()]);

                // CU-32: Registrar comprobante de Entrega de Reparación
                $tipoComprobante = \DB::table('tipos_comprobante')->where('codigo', 'ENTREGA_REPARACION')->value('tipo_id');
                $estadoEmitido = \DB::table('estados_comprobante')->where('nombre', 'EMITIDO')->value('estado_id');

                if ($tipoComprobante && $estadoEmitido) {
                    \App\Models\Comprobante::create([
                        'tipo_entidad' => $reparacion->getMorphClass(),
                        'entidad_id' => $reparacion->reparacionID,
                        'usuario_id' => $userId,
                        'tipo_comprobante_id' => $tipoComprobante,
                        'numero_correlativo' => 'ENT-' . $reparacion->codigo_reparacion,
                        'fecha_emision' => now(),
                        'estado_comprobante_id' => $estadoEmitido,
                    ]);
                }
            }

            // 5b. Lógica de Anulación (ID 6 = Anulado): revertir stock de repuestos
            if ($nuevoEstado->estadoReparacionID == 6) {
                $this->revertirStockRepuestos($reparacion, $userId);
            }

            // 6. CU-12 Paso 10: Registrar operación en el historial de operaciones
            Auditoria::registrar(
                accion: Auditoria::ACCION_ACTUALIZAR_REPARACION,
                tabla: 'reparaciones',
                registroId: $reparacion->reparacionID,
                datosAnteriores: [
                    'estado_anterior' => $reparacion->getOriginal('estado_reparacion_id'),
                    'diagnostico_anterior' => $reparacion->getOriginal('diagnostico_tecnico'),
                ],
                datosNuevos: [
                    'estado_nuevo' => $nuevoEstado->estadoReparacionID,
                    'estado_nombre' => $nuevoEstado->nombreEstado,
                    'diagnostico_nuevo' => $datos['diagnostico_tecnico'] ?? null,
                    'repuestos_agregados' => count($datos['repuestos'] ?? []),
                ],
                motivo: "Actualización de reparación {$reparacion->codigo_reparacion}",
                detalles: "Estado cambiado a: {$nuevoEstado->nombreEstado}" . 
                          (isset($datos['diagnostico_tecnico']) ? " | Diagnóstico actualizado" : "") .
                          (!empty($datos['repuestos']) ? " | " . count($datos['repuestos']) . " repuestos agregados" : ""),
                usuarioId: $userId
            );

            Log::info("Reparación #{$reparacion->codigo_reparacion} actualizada a '{$nuevoEstado->nombreEstado}' por User ID: {$userId}");

            return $reparacion;
        });
    }

    /**
     * Valida si la transición entre estados es válida usando IDs (respeta BD)
     */
    private function esTransicionValidaPorId(int $estadoActualId, int $nuevoEstadoId): bool
    {
        if ($estadoActualId === $nuevoEstadoId) return true;
        $posibles = self::TRANSICIONES_VALIDAS[$estadoActualId] ?? [];
        return in_array($nuevoEstadoId, $posibles);
    }

    private function agregarRepuesto(Reparacion $reparacion, array $itemData, int $userId): void
    {
        $producto = Producto::findOrFail($itemData['producto_id']);
        $cantidad = $itemData['cantidad'];

        // Validar Stock y Descontar (Si es físico)
        if ($producto->unidadMedida !== 'Servicio') {
            
            // 1. Obtener Tipo de Movimiento Dinámicamente
            $tipoMovimiento = TipoMovimientoStock::where('nombre', 'Salida (Venta)')->first();
            if (!$tipoMovimiento) {
                throw new \Exception("Error de Configuración: No se encontró el tipo de movimiento 'Salida (Venta)'.");
            }

            // 2. Bloqueo Pesimista (ACID)
            $stockRegistro = Stock::where('productoID', $producto->id)
                                  ->lockForUpdate()
                                  ->firstOrFail();

            if ($stockRegistro->cantidad_disponible < $cantidad) {
                throw new SinStockException($producto->nombre, $cantidad, $stockRegistro->cantidad_disponible);
            }

            $stockAnterior = $stockRegistro->cantidad_disponible;
            $stockRegistro->decrement('cantidad_disponible', $cantidad);

            MovimientoStock::create([
                'stock_id' => $stockRegistro->stock_id,
                'productoID' => $producto->id,
                'tipo_movimiento_id' => $tipoMovimiento->id,
                'cantidad' => $cantidad,
                'stockAnterior' => $stockAnterior,
                'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
                'motivo' => 'Repuesto en Reparación ' . $reparacion->codigo_reparacion,
                'referenciaID' => $reparacion->reparacionID,
                'referenciaTabla' => 'reparaciones',
                'user_id' => $userId,
                'fecha_movimiento' => now(),
            ]);
        }

        $cliente = Cliente::findOrFail($reparacion->clienteID);
        $precio = $this->obtenerPrecioParaCliente($producto, $cliente);

        DetalleReparacion::create([
            'reparacion_id' => $reparacion->reparacionID,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $precio * $cantidad,
        ]);
    }

    /**
     * Revertir stock de repuestos al anular una reparación.
     * Reincorpora las cantidades al inventario con movimiento de tipo "Devolución (Entrada)".
     */
    private function revertirStockRepuestos(Reparacion $reparacion, int $userId): void
    {
        $reparacion->load('repuestos.producto');

        $tipoDevolucion = TipoMovimientoStock::where('nombre', 'Devolución (Entrada)')->first();
        if (!$tipoDevolucion) {
            Log::error("No se encontró tipo de movimiento 'Devolución (Entrada)' al anular reparación {$reparacion->codigo_reparacion}");
            return;
        }

        foreach ($reparacion->repuestos as $detalle) {
            $producto = $detalle->producto;
            if (!$producto || $producto->es_servicio) continue;

            $cantidad = (int) $detalle->cantidad;

            $stockRegistro = Stock::where('productoID', $producto->id)
                                  ->lockForUpdate()
                                  ->first();

            if ($stockRegistro) {
                $stockAnterior = $stockRegistro->cantidad_disponible;
                $stockRegistro->increment('cantidad_disponible', $cantidad);

                MovimientoStock::create([
                    'stock_id' => $stockRegistro->stock_id,
                    'productoID' => $producto->id,
                    'tipo_movimiento_id' => $tipoDevolucion->id,
                    'cantidad' => $cantidad,
                    'stockAnterior' => $stockAnterior,
                    'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
                    'motivo' => 'Anulación Reparación ' . $reparacion->codigo_reparacion,
                    'referenciaID' => $reparacion->reparacionID,
                    'referenciaTabla' => 'reparaciones',
                    'user_id' => $userId,
                    'fecha_movimiento' => now(),
                ]);
            } else {
                Log::error("Stock no encontrado para Producto ID {$producto->id} al anular Reparación {$reparacion->codigo_reparacion}");
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
}