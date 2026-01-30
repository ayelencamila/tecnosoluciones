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
use App\Exceptions\Ventas\SinStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarReparacionService
{
    /**
     * Mapa de Transiciones Válidas por ID de Estado
     * 
     * FLUJO FLEXIBLE: Permite avanzar lógicamente sin forzar todos los pasos intermedios.
     * Un técnico puede recibir, diagnosticar y reparar en una sola sesión.
     * 
     * Estados según BD:
     * 1 = Recibido
     * 2 = Diagnóstico  
     * 3 = Presupuestado
     * 4 = En Reparación
     * 5 = Espera de Repuesto
     * 6 = Reparado (listo para entregar)
     * 7 = Entregado (estado final)
     * 8 = Anulado (estado final)
     */
    private const TRANSICIONES_VALIDAS = [
        1 => [2, 3, 4, 5, 6, 8], // Recibido → puede avanzar a cualquiera (excepto Entregado directo)
        2 => [1, 3, 4, 5, 6, 8], // Diagnóstico → puede volver a Recibido o avanzar
        3 => [2, 4, 5, 6, 8],    // Presupuestado → puede retroceder a Diagnóstico o avanzar
        4 => [2, 3, 5, 6, 8],    // En Reparación → puede retroceder o completar
        5 => [2, 4, 6, 8],       // Espera de Repuesto → puede retomar o completar
        6 => [4, 7, 8],          // Reparado → puede volver a reparación, entregar o anular
        7 => [],                 // Entregado (estado final)
        8 => [],                 // Anulado (estado final)
    ];

    public function handle(Reparacion $reparacion, array $datos, int $userId): Reparacion
    {
        // 1. Validar Estado Final usando el método del modelo
        if ($reparacion->estado->esFinal()) {
            throw new \Exception("La reparación está en estado '{$reparacion->estado->nombreEstado}' y no admite más modificaciones.");
        }

        // 2. Validar Transición de Estado usando IDs (respeta la BD)
        $estadoActualId = $reparacion->estado_reparacion_id;
        $nuevoEstadoId = $datos['estado_reparacion_id'];
        $nuevoEstado = EstadoReparacion::findOrFail($nuevoEstadoId);
        
        if ($estadoActualId != $nuevoEstadoId) {
            if (!$this->esTransicionValidaPorId($estadoActualId, $nuevoEstadoId)) {
                throw new \Exception("Transición de estado inválida: No se puede pasar de '{$reparacion->estado->nombreEstado}' a '{$nuevoEstado->nombreEstado}'.");
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

            // 5. Lógica de Cierre (ID 7 = Entregado según BD)
            if ($nuevoEstado->estadoReparacionID == 7 && !$reparacion->fecha_entrega_real) {
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
                'productoID' => $producto->id,
                'deposito_id' => $stockRegistro->deposito_id,
                'tipoMovimiento' => 'SALIDA',
                'tipo_movimiento_id' => $tipoMovimiento->id,
                'cantidad' => $cantidad,
                'signo' => $tipoMovimiento->signo,
                'stockAnterior' => $stockAnterior,
                'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
                'motivo' => 'Repuesto en Reparación ' . $reparacion->codigo_reparacion,
                'referenciaID' => $reparacion->reparacionID,
                'referenciaTabla' => 'reparaciones',
                'user_id' => $userId,
                'fecha_movimiento' => now()
            ]);
        }

        $precio = $producto->precios()->latest('fechaDesde')->first()?->precio ?? 0;

        DetalleReparacion::create([
            'reparacion_id' => $reparacion->reparacionID,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $precio * $cantidad,
        ]);
    }
}