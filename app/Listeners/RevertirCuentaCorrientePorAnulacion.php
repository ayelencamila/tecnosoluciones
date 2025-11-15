<?php

namespace App\Listeners;

use App\Events\VentaAnulada;
use Illuminate\Support\Facades\Log;

class RevertirCuentaCorrientePorAnulacion
{
    public function handle(VentaAnulada $event): void
    {
        $venta = $event->venta;

        // Asumimos que CUALQUIER anulación revierte la CC,
        // el método registrarCredito se encarga si no hay un saldo deudor.

        $cliente = $venta->cliente;
        if ($cliente->cuentaCorriente) {
            $cliente->cuentaCorriente->registrarCredito(
                $venta->total,
                'Anulación Venta (Comprobante '.$venta->numero_comprobante.')',
                null,
                $event->userID
            );

            Log::info("CC REVERTIDA para Venta ID: {$venta->venta_id}");
        } else {
            Log::warning("Venta {$venta->venta_id} anulada, pero el cliente {$cliente->clienteID} no tiene CC asignada. No se pudo revertir CC.");
        }
    }
}
