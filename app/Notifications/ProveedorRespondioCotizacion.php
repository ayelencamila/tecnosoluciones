<?php

namespace App\Notifications;

use App\Models\CotizacionProveedor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProveedorRespondioCotizacion extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CotizacionProveedor $cotizacion
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
        $solicitud = $this->cotizacion->solicitud;
        $proveedor = $this->cotizacion->proveedor;
        
        return [
            'type' => 'cotizacion_respondida',
            'title' => 'Nueva respuesta de cotización',
            'message' => "{$proveedor->razon_social} respondió a la solicitud {$solicitud->codigo_solicitud}",
            'icon' => '📋',
            'action_url' => route('compras.solicitudes-cotizacion.show', $solicitud->id),
            'solicitud_id' => $solicitud->id,
            'solicitud_codigo' => $solicitud->codigo_solicitud,
            'proveedor_id' => $proveedor->id,
            'proveedor_nombre' => $proveedor->razon_social,
            'cotizacion_id' => $this->cotizacion->id,
            'fecha_respuesta' => $this->cotizacion->fecha_respuesta?->toIso8601String(),
        ];
    }
}
