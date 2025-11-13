<?php

namespace App\Listeners;

use App\Events\VentaAnulada;
use App\Models\Auditoria;
use Illuminate\Support\Facades\Log;

class RegistrarAnulacionEnAuditoria
{
    public function handle(VentaAnulada $event): void
    {
        try {
            Auditoria::registrar(
                Auditoria::ACCION_ANULAR_VENTA,
                'ventas',
                $event->venta->venta_id,
                $event->venta->getOriginal(),
                $event->venta->toArray(),
                $event->venta->motivo_anulacion,
                'Se anuló la venta '.$event->venta->numero_comprobante,
                $event->userID
            );

        } catch (\Exception $e) {
            Log::critical('FALLO DE AUDITORÍA al ANULAR venta: '.$e->getMessage());
        }
    }
}
