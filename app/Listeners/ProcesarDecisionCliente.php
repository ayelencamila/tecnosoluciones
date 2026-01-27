<?php

namespace App\Listeners;

use App\Events\ClienteRespondioBonificacion;
use App\Models\Reparacion;
use App\Models\User;
use App\Notifications\ClienteRespondioBoificacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Listener que procesa la decisión del cliente sobre la bonificación
 * CU-14/CU-15: Actualiza el estado de la reparación según la decisión
 * 
 * Principio aplicado: Single Responsibility (SOLID)
 * - Este listener procesa la decisión del cliente y notifica a admins
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

                // La decisión ya fue guardada en registrarDecisionCliente()
                // Solo procesamos la lógica de negocio aquí

                // Actualizar estado de la reparación según decisión
                $reparacion = $bonificacion->reparacion;

                if ($decision === 'aceptar') {
                    // Cliente acepta la bonificación, continuar con la reparación
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
                        'decision' => 'aceptar',
                    ]);

                } else {
                    // Cliente rechaza/cancela, quiere retirar el equipo
                    $estadoListoRetiro = \App\Models\EstadoReparacion::where('nombreEstado', 'LIKE', '%Listo%')
                        ->orWhere('nombreEstado', 'LIKE', '%Retiro%')
                        ->first();

                    if (!$estadoListoRetiro) {
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

                // Refrescar la bonificación para obtener el estado actualizado
                $bonificacion->refresh();

                // Notificar a los administradores (rol_id = 1 es admin)
                $admins = User::where('rol_id', 1)->get();

                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new ClienteRespondioBoificacion($bonificacion));
                    
                    Log::info("Notificación enviada a administradores sobre decisión del cliente", [
                        'bonificacion_id' => $bonificacion->bonificacionID,
                        'admins_notificados' => $admins->count(),
                        'decision' => $decision,
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

