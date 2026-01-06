<?php

namespace App\Listeners;

use App\Events\ClienteRespondioBonificacion;
use App\Models\Reparacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Listener que procesa la decisión del cliente sobre la bonificación
 * CU-14/CU-15: Actualiza el estado de la reparación según la decisión
 * 
 * Principio aplicado: Single Responsibility (SOLID)
 * - Este listener SOLO procesa la decisión del cliente
 * - No envía notificaciones (eso lo hace otro listener si es necesario)
 */
class ProcesarDecisionCliente
{
    /**
     * Handle the event.
     */
    public function handle(ClienteRespondioBonificacion $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                $bonificacion = $event->bonificacion;
                $decision = $event->decision;

                // 1. Actualizar decisión del cliente en la bonificación
                $bonificacion->update([
                    'decision_cliente' => $decision,
                    'fecha_decision_cliente' => now(),
                ]);

                // 2. Actualizar estado de la reparación según decisión
                $reparacion = $bonificacion->reparacion;

                if ($decision === 'continuar') {
                    // Cliente acepta la bonificación, continuar con la reparación
                    // Buscar estado "En Reparación" o el apropiado
                    $estadoEnReparacion = \App\Models\EstadoReparacion::where('nombreEstado', 'En Reparación')
                        ->orWhere('nombreEstado', 'En reparación')
                        ->first();

                    if ($estadoEnReparacion && $reparacion->estado_reparacion_id !== $estadoEnReparacion->estadoReparacionID) {
                        $reparacion->update([
                            'estado_reparacion_id' => $estadoEnReparacion->estadoReparacionID,
                            'observaciones' => ($reparacion->observaciones ?? '') . "\nCliente aceptó bonificación del {$bonificacion->porcentaje_aprobado}% el " . now()->format('d/m/Y H:i'),
                        ]);
                    }

                    Log::info("Cliente aceptó bonificación", [
                        'bonificacion_id' => $bonificacion->bonificacionID,
                        'reparacion_id' => $reparacion->reparacionID,
                        'decision' => 'continuar',
                    ]);

                } else {
                    // Cliente rechaza, quiere retirar el equipo
                    // Buscar estado "Listo para Retiro" o "Cancelado"
                    $estadoListoRetiro = \App\Models\EstadoReparacion::where('nombreEstado', 'LIKE', '%Listo%')
                        ->orWhere('nombreEstado', 'LIKE', '%Retiro%')
                        ->first();

                    if (!$estadoListoRetiro) {
                        // Si no existe "Listo", buscar "Cancelado"
                        $estadoListoRetiro = \App\Models\EstadoReparacion::where('nombreEstado', 'LIKE', '%Cancelad%')->first();
                    }

                    if ($estadoListoRetiro && $reparacion->estado_reparacion_id !== $estadoListoRetiro->estadoReparacionID) {
                        $reparacion->update([
                            'estado_reparacion_id' => $estadoListoRetiro->estadoReparacionID,
                            'observaciones' => ($reparacion->observaciones ?? '') . "\nCliente rechazó bonificación y solicitó retiro el " . now()->format('d/m/Y H:i'),
                        ]);
                    }

                    Log::info("Cliente rechazó bonificación", [
                        'bonificacion_id' => $bonificacion->bonificacionID,
                        'reparacion_id' => $reparacion->reparacionID,
                        'decision' => 'cancelar',
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error("Error al procesar decisión del cliente", [
                'bonificacion_id' => $event->bonificacion->bonificacionID,
                'decision' => $event->decision,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
