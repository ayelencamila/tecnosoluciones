<?php

namespace App\Notifications;

use App\Models\CotizacionProveedor;
use Illuminate\Notifications\Notification;

class ProveedorRespondioCotizacion extends Notification
{
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
        $codigo = $solicitud->codigo_solicitud ?? 'N/A';
        $razon = $proveedor->razon_social ?? 'Proveedor';
        $mensaje = $razon . ' respondió a la solicitud ' . $codigo;
        return [
            'titulo' => 'Nueva respuesta de cotización',
            'mensaje' => $mensaje,
            'tipo' => 'cotizacion_respondida',
            'icono' => null,
            'url' => '/solicitudes-cotizacion/' . $solicitud->id,
            'solicitud_id' => $solicitud->id,
            'solicitud_codigo' => $codigo,
            'proveedor_id' => $proveedor->id,
            'proveedor_nombre' => $razon,
            'cotizacion_id' => $this->cotizacion->id,
            'fecha_respuesta' => $this->cotizacion->fecha_respuesta?->toIso8601String(),
        ];
    }
}
