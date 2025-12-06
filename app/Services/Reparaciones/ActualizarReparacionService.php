<?php

namespace App\Services\Reparaciones;

use App\Models\Reparacion;
use App\Models\DetalleReparacion;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\EstadoReparacion; // Importante importar el modelo
use App\Exceptions\Ventas\SinStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarReparacionService
{
    /**
     * Mapa de Transiciones Válidas (Máquina de Estados)
     * Define el flujo de negocio permitido usando constantes referenciales.
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
            EstadoReparacion::DIAGNOSTICO // Retroceso permitido si el diagnóstico inicial falló
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
            EstadoReparacion::EN_REPARACION // Retroceso por garantía interna o falla en control de calidad
        ],
        // Estados Finales (No tienen salida)
        EstadoReparacion::ENTREGADO => [],
        EstadoReparacion::CANCELADO => [],
        EstadoReparacion::ANULADO   => [],
    ];

    public function handle(Reparacion $reparacion, array $datos, int $userId): Reparacion
    {
        // 1. Validar si la reparación permite modificaciones (Estado Final)
        if ($this->esEstadoFinal($reparacion->estado->nombreEstado)) {
            throw new \Exception("La reparación está en estado '{$reparacion->estado->nombreEstado}' y no admite más modificaciones.");
        }

        // 2. Validar Transición de Estado (solo si cambia el estado)
        $nuevoEstadoId = $datos['estado_reparacion_id'];
        $nuevoEstado = EstadoReparacion::findOrFail($nuevoEstadoId);
        
        // Solo validar transición si el estado realmente cambió
        if ($reparacion->estado_reparacion_id != $nuevoEstadoId) {
            if (!$this->esTransicionValida($reparacion->estado->nombreEstado, $nuevoEstado->nombreEstado)) {
                throw new \Exception("Transición de estado inválida: No se puede pasar de '{$reparacion->estado->nombreEstado}' a '{$nuevoEstado->nombreEstado}'.");
            }
        }

        return DB::transaction(function () use ($reparacion, $datos, $nuevoEstado, $userId) {
            
            // 3. Actualizar Cabecera
            $reparacion->update([
                'estado_reparacion_id' => $nuevoEstado->estadoReparacionID,
                'diagnostico_tecnico' => $datos['diagnostico_tecnico'] ?? $reparacion->diagnostico_tecnico,
                'observaciones' => $datos['observaciones'] ?? $reparacion->observaciones,
                'tecnico_id' => $datos['tecnico_id'] ?? $reparacion->tecnico_id ?? $userId,
            ]);

            // 4. Procesar Nuevos Repuestos
            if (!empty($datos['repuestos'])) {
                foreach ($datos['repuestos'] as $item) {
                    $this->agregarRepuesto($reparacion, $item);
                }
            }

            // 5. Lógica de Cierre (Si pasa a Entregado)
            if ($nuevoEstado->nombreEstado === EstadoReparacion::ENTREGADO && !$reparacion->fecha_entrega_real) {
                $reparacion->update(['fecha_entrega_real' => now()]);
            }

            // 6. Log de Auditoría
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
        if ($estadoActual === $estadoNuevo) {
            return true;
        }

        $posibles = self::TRANSICIONES_VALIDAS[$estadoActual] ?? [];
        
        if (empty($posibles)) return false; 

        return in_array($estadoNuevo, $posibles);
    }

    private function agregarRepuesto(Reparacion $reparacion, array $itemData): void
    {
        $producto = Producto::findOrFail($itemData['producto_id']);
        $cantidad = $itemData['cantidad'];

        // Validar Stock y Descontar (Si es físico)
        if ($producto->unidadMedida !== 'Servicio') {
            if (!$producto->tieneStock($cantidad)) {
                throw new SinStockException($producto->nombre, $cantidad, $producto->stock_total);
            }

            $stockRegistro = Stock::where('productoID', $producto->id)->firstOrFail();
            $stockAnterior = $stockRegistro->cantidad_disponible;
            $stockRegistro->decrement('cantidad_disponible', $cantidad);

            MovimientoStock::create([
                'productoID' => $producto->id,
                'tipoMovimiento' => 'SALIDA',
                'cantidad' => $cantidad,
                'stockAnterior' => $stockAnterior,
                'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
                'motivo' => 'Repuesto en Reparación ' . $reparacion->codigo_reparacion,
                'referenciaID' => $reparacion->reparacionID,
                'referenciaTabla' => 'reparaciones',
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