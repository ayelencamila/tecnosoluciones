<?php

namespace App\Observers;

use App\Models\Reparacion;
use App\Models\HistorialEstadoReparacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Observer para el modelo Reparacion
 * 
 * Registra automáticamente los cambios de estado en el historial
 * para cálculos precisos de días efectivos de SLA
 */
class ReparacionObserver
{
    /**
     * Handle the Reparacion "created" event.
     * Registra el estado inicial de la reparación
     */
    public function created(Reparacion $reparacion): void
    {
        if ($reparacion->estado_reparacion_id) {
            HistorialEstadoReparacion::create([
                'reparacion_id' => $reparacion->reparacionID,
                'estado_anterior_id' => null, // Es el primer estado
                'estado_nuevo_id' => $reparacion->estado_reparacion_id,
                'fecha_cambio' => $reparacion->fecha_ingreso ?? now(),
                'usuario_id' => Auth::id(),
                'observaciones' => 'Estado inicial de la reparación',
            ]);

            Log::info('Estado inicial registrado en historial', [
                'reparacion_id' => $reparacion->reparacionID,
                'estado_id' => $reparacion->estado_reparacion_id,
            ]);
        }
    }

    /**
     * Handle the Reparacion "updated" event.
     * Registra los cambios de estado cuando ocurren
     */
    public function updated(Reparacion $reparacion): void
    {
        // Solo registrar si cambió el estado
        if ($reparacion->isDirty('estado_reparacion_id')) {
            $estadoAnterior = $reparacion->getOriginal('estado_reparacion_id');
            $estadoNuevo = $reparacion->estado_reparacion_id;

            HistorialEstadoReparacion::create([
                'reparacion_id' => $reparacion->reparacionID,
                'estado_anterior_id' => $estadoAnterior,
                'estado_nuevo_id' => $estadoNuevo,
                'fecha_cambio' => now(),
                'usuario_id' => Auth::id(),
                'observaciones' => 'Cambio de estado',
            ]);

            Log::info('Cambio de estado registrado en historial', [
                'reparacion_id' => $reparacion->reparacionID,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
            ]);
        }
    }

    /**
     * Handle the Reparacion "deleting" event.
     * Los registros de historial se eliminan automáticamente por ON DELETE CASCADE
     */
    public function deleting(Reparacion $reparacion): void
    {
        Log::info('Reparación eliminada, historial de estados se eliminará por CASCADE', [
            'reparacion_id' => $reparacion->reparacionID,
        ]);
    }
}
