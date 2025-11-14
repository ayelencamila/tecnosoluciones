<?php

// app/Listeners/RegistrarVentaEnAuditoria.php

namespace App\Listeners;

use App\Events\VentaRegistrada;
use App\Models\Auditoria;
use Illuminate\Support\Facades\Log;

class RegistrarVentaEnAuditoria
{
    public function handle(VentaRegistrada $event): void
    {
        try {
            // ¡Usamos TU método helper!
            Auditoria::registrar(
                // ¡Usamos TU constante!
                Auditoria::ACCION_CREAR_VENTA,

                'ventas', // El nombre de la tabla
                $event->venta->venta_id, // El ID del registro afectado
                null, // datos_anteriores (es un 'create', no hay)
                $event->venta->toArray(), // datos_nuevos (guardamos la venta)
                'Registro automático por CU-05', // motivo
                'Se registró la venta '.$event->venta->numero_comprobante.' por un total de '.$event->venta->total,
                $event->venta->user_id // El usuario que hizo la venta
            );

        } catch (\Exception $e) {
            Log::critical('FALLO DE AUDITORÍA al registrar venta: '.$e->getMessage());
        }
    }
}
