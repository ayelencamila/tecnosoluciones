<?php

namespace App\Services\Reparaciones;

use App\Models\Reparacion;
use App\Models\EstadoReparacion;
use App\Models\Stock;
use App\Models\MovimientoStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnularReparacionService
{
    public function handle(Reparacion $reparacion, string $motivo, int $userId): void
    {
        DB::transaction(function () use ($reparacion, $motivo, $userId) {
            
            // 1. Validar que no esté ya finalizada/entregada (Regla de Negocio)
            // Asumimos que si ya se entregó, no se anula, se hace garantía (otro CU).
            if ($reparacion->estado->nombreEstado === 'Entregado') {
                 throw new \Exception("No se puede anular una reparación que ya fue entregada al cliente.");
            }

            // 2. Buscar Estado "Cancelado" o "Anulado"
            $estadoCancelado = EstadoReparacion::where('nombreEstado', 'Cancelado')
                ->orWhere('nombreEstado', 'Anulado')
                ->firstOrFail();

            // 3. Revertir Stock de Repuestos (Si se usaron)
            foreach ($reparacion->repuestos as $detalle) {
                // Solo devolvemos stock si NO es un servicio
                if ($detalle->producto->unidadMedida !== 'Servicio') {
                    $stockRegistro = Stock::where('productoID', $detalle->producto_id)->first();
                    
                    if ($stockRegistro) {
                        $stockAnterior = $stockRegistro->cantidad_disponible;
                        
                        // Devolvemos la cantidad
                        $stockRegistro->increment('cantidad_disponible', $detalle->cantidad);
                        
                        // Registramos el movimiento de devolución
                        MovimientoStock::create([
                            'productoID' => $detalle->producto_id,
                            'tipoMovimiento' => 'ENTRADA', // Vuelve a entrar
                            'cantidad' => $detalle->cantidad,
                            'stockAnterior' => $stockAnterior,
                            'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
                            'motivo' => "Anulación Reparación #{$reparacion->codigo_reparacion}",
                            'referenciaID' => $reparacion->reparacionID,
                            'referenciaTabla' => 'reparaciones',
                        ]);
                    }
                }
            }

            // 4. Actualizar la Reparación
            $reparacion->update([
                'estado_reparacion_id' => $estadoCancelado->estadoReparacionID,
                'observaciones' => $reparacion->observaciones . "\n[ANULADA]: " . $motivo,
                'anulada' => true, // Usamos el flag booleano que creamos en la migración
            ]);

            Log::info("Reparación #{$reparacion->reparacionID} anulada por User {$userId}. Motivo: {$motivo}");
        });
    }
}