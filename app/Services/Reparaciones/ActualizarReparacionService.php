<?php

namespace App\Services\Reparaciones;

use App\Models\Reparacion;
use App\Models\DetalleReparacion;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\EstadoReparacion;
use App\Models\TipoMovimientoStock; 
use App\Exceptions\Ventas\SinStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarReparacionService
{
    /**
     * Mapa de Transiciones Válidas (Máquina de Estados)
     */
    private const TRANSICIONES_VALIDAS = [
        EstadoReparacion::RECIBIDO => [
            EstadoReparacion::DIAGNOSTICO, 
            EstadoReparacion::CANCELADO, 
            EstadoReparacion::ANULADO
        ],
        EstadoReparacion::DIAGNOSTICO => [
            EstadoReparacion::EN_REPARACION, 
            EstadoReparacion::ESPERANDO_REPUESTO, 
            EstadoReparacion::CANCELADO, 
            EstadoReparacion::ANULADO
        ],
        EstadoReparacion::EN_REPARACION => [
            EstadoReparacion::LISTO, 
            EstadoReparacion::ESPERANDO_REPUESTO, 
            EstadoReparacion::DEMORADO, 
            EstadoReparacion::DIAGNOSTICO
        ],
        EstadoReparacion::ESPERANDO_REPUESTO => [
            EstadoReparacion::EN_REPARACION, 
            EstadoReparacion::CANCELADO
        ],
        EstadoReparacion::DEMORADO => [
            EstadoReparacion::EN_REPARACION, 
            EstadoReparacion::LISTO, 
            EstadoReparacion::CANCELADO
        ],
        EstadoReparacion::LISTO => [
            EstadoReparacion::ENTREGADO, 
            EstadoReparacion::EN_REPARACION
        ],
        // Estados Finales
        EstadoReparacion::ENTREGADO => [],
        EstadoReparacion::CANCELADO => [],
        EstadoReparacion::ANULADO   => [],
    ];

    public function handle(Reparacion $reparacion, array $datos, int $userId): Reparacion
    {
        // 1. Validar Estado Final
        if ($this->esEstadoFinal($reparacion->estado->nombreEstado)) {
            throw new \Exception("La reparación está en estado '{$reparacion->estado->nombreEstado}' y no admite más modificaciones.");
        }

        // 2. Validar Transición de Estado
        $nuevoEstadoId = $datos['estado_reparacion_id'];
        $nuevoEstado = EstadoReparacion::findOrFail($nuevoEstadoId);
        
        if ($reparacion->estado_reparacion_id != $nuevoEstadoId) {
            if (!$this->esTransicionValida($reparacion->estado->nombreEstado, $nuevoEstado->nombreEstado)) {
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

            // 5. Lógica de Cierre
            if ($nuevoEstado->nombreEstado === EstadoReparacion::ENTREGADO && !$reparacion->fecha_entrega_real) {
                $reparacion->update(['fecha_entrega_real' => now()]);
            }

            Log::info("Reparación #{$reparacion->codigo_reparacion} actualizada a '{$nuevoEstado->nombreEstado}' por User ID: {$userId}");

            return $reparacion;
        });
    }

    private function esEstadoFinal(string $estadoActual): bool
    {
        return in_array($estadoActual, [
            EstadoReparacion::ENTREGADO, 
            EstadoReparacion::CANCELADO, 
            EstadoReparacion::ANULADO
        ]);
    }

    private function esTransicionValida(string $estadoActual, string $estadoNuevo): bool
    {
        if ($estadoActual === $estadoNuevo) return true;
        $posibles = self::TRANSICIONES_VALIDAS[$estadoActual] ?? [];
        return in_array($estadoNuevo, $posibles);
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