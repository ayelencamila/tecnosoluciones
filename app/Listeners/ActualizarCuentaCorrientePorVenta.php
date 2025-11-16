<?php


// app/Listeners/ActualizarCuentaCorrientePorVenta.php

namespace App\Listeners;

use App\Events\VentaRegistrada;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActualizarCuentaCorrientePorVenta
{
    public function handle(VentaRegistrada $event): void
    {
        if ($event->metodoPago !== 'cuenta_corriente') {
            return;
        }

        $venta = $event->venta;
        $cliente = $venta->cliente;

        if ($cliente->cuentaCorriente) {
            $cliente->cuentaCorriente->registrarDebito(
                $venta->total,
                'Venta (Comprobante '.$venta->numero_comprobante.')',
                Carbon::now()->addDays($cliente->cuentaCorriente->getDiasGraciaAplicables()),
                $venta->venta_id,
                'ventas',
                $event->userID
      );

            Log::info("CC actualizada para Venta ID: {$venta->venta_id}");
        } else {
            Log::warning("Venta {$venta->venta_id} a CC, pero el cliente {$cliente->clienteID} no tiene CC asignada.");
        }
    }
}
