<?php

namespace App\Notifications;

use App\Models\BonificacionReparacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClienteRespondioBoificacion extends Notification
{
    use Queueable;

    public $bonificacion;

    /**
     * Create a new notification instance.
     */
    public function __construct(BonificacionReparacion $bonificacion)
    {
        $this->bonificacion = $bonificacion;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $decision = $this->bonificacion->decision_cliente === 'aceptar' ? 'aceptó' : 'canceló';
        $icon = $this->bonificacion->decision_cliente === 'aceptar' ? '✅' : '❌';
        
        return [
            'titulo' => "{$icon} Cliente {$decision} bonificación",
            'mensaje' => "El cliente {$this->bonificacion->reparacion->cliente->nombreCompleto} {$decision} la bonificación de la reparación #{$this->bonificacion->reparacion->codigo_reparacion}",
            'tipo' => $this->bonificacion->decision_cliente === 'aceptar' ? 'success' : 'warning',
            'url' => '/bonificaciones/' . $this->bonificacion->bonificacionID,
            'bonificacion_id' => $this->bonificacion->bonificacionID,
            'decision' => $this->bonificacion->decision_cliente,
        ];
    }
}