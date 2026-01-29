<?php

namespace App\Notifications;

use App\Models\Reparacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Notificación de campanita para Admin y Técnico cuando una reparación excede el SLA (CU-14)
 */
class ReparacionDemoradaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reparacion $reparacion,
        public int $diasExcedidos,
        public string $tipoDestinatario = 'tecnico', // 'tecnico' o 'admin'
        public ?int $alertaId = null // ID de la alerta para el técnico
    ) {}

    /**
     * Canales de notificación - solo database (campanita)
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
        $esAdmin = $this->tipoDestinatario === 'admin';
        
        // URL inteligente: técnico va a responder alerta, admin va a ver alertas
        $url = $esAdmin 
            ? route('alertas.index')
            : ($this->alertaId ? route('alertas.show', $this->alertaId) : route('alertas.index'));
        
        return [
            'titulo' => $esAdmin 
                ? 'Reparación con SLA Excedido' 
                : 'Tu reparación excedió el SLA',
            'mensaje' => sprintf(
                'La reparación %s del cliente %s %s excedió el SLA por %d día(s). Equipo: %s %s',
                $this->reparacion->codigo_reparacion,
                $this->reparacion->cliente->nombre,
                $this->reparacion->cliente->apellido,
                $this->diasExcedidos,
                $this->reparacion->modelo?->marca?->nombre ?? 'N/A',
                $this->reparacion->modelo?->nombre ?? 'N/A'
            ),
            'tipo' => 'sla_excedido',
            'icono' => 'exclamation-triangle',
            'color' => 'danger',
            'url' => $url,
            'reparacion_id' => $this->reparacion->reparacionID,
            'reparacion_codigo' => $this->reparacion->codigo_reparacion,
            'dias_excedidos' => $this->diasExcedidos,
            'cliente_nombre' => $this->reparacion->cliente->nombreCompleto,
            'alerta_id' => $this->alertaId,
        ];
    }
}
