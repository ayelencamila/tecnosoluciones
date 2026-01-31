<?php

namespace App\Notifications;

use App\Models\CotizacionProveedor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProveedorRespondioCotizacion extends Notification
{
    use Queueable;

    public function __construct(
        public CotizacionProveedor $cotizacion
    ) {}

    /**
     * Define los canales de notificación (Base de datos para la campanita)
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Estructura de datos para la tabla 'notifications'
     */
    public function toArray(object $notifiable): array
    {
        // Pre-cargamos relaciones por seguridad, aunque ya deberían venir
        $solicitud = $this->cotizacion->solicitud;
        $proveedor = $this->cotizacion->proveedor;
        
        $codigo = $solicitud->codigo_solicitud ?? 'S/N';
        $razonSocial = $proveedor->razon_social ?? 'Proveedor Desconocido';

        return [
            'titulo' => 'Nueva Cotización Recibida',
            'mensaje' => "El proveedor {$razonSocial} ha respondido a la solicitud #{$codigo}.",
            'tipo'    => 'cotizacion_respondida',
            // CORRECCIÓN CRÍTICA: Usamos el nombre real de la ruta que vimos en tu 'route:list'
            'url'     => route('solicitudes-cotizacion.show', $solicitud->id),
            'data_extra' => [
                'solicitud_id' => $solicitud->id,
                'proveedor_id' => $proveedor->id,
                'cotizacion_id' => $this->cotizacion->id,
            ]
        ];
    }
}
