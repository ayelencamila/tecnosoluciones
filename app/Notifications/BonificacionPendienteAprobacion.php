<?php

namespace App\Notifications;

use App\Models\BonificacionReparacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class BonificacionPendienteAprobacion extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BonificacionReparacion $bonificacion
    ) {}

    /**
     * Canales de notificación
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Datos para la base de datos (campanita)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Nueva Bonificación Pendiente de Aprobación',
            'mensaje' => sprintf(
                'Bonificación del %s%% para la reparación %s. Monto: $%s',
                $this->bonificacion->porcentaje_sugerido,
                $this->bonificacion->reparacion->codigo_reparacion,
                number_format($this->bonificacion->monto_bonificado, 2, ',', '.')
            ),
            'tipo' => 'bonificacion',
            'icono' => 'gift',
            'color' => 'warning',
            'url' => '/bonificaciones/' . $this->bonificacion->bonificacionID,
            'bonificacion_id' => $this->bonificacion->bonificacionID,
            'reparacion_codigo' => $this->bonificacion->reparacion->codigo_reparacion,
        ];
    }
}
