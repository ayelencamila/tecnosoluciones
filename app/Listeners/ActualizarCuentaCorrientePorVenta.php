<?php

// app/Listeners/ActualizarCuentaCorrientePorVenta.php

namespace App\Listeners;

use App\Events\VentaRegistrada;
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
            // ¡Ahora sí! Llamamos a nuestro nuevo método
            $cliente->cuentaCorriente->registrarDebito(
                $venta->total,
                'Venta (Comprobante '.$venta->numero_comprobante.')',
                $venta->venta_id
            );

            Log::info("CC actualizada para Venta ID: {$venta->venta_id}");
        } else {
            Log::warning("Venta {$venta->venta_id} a CC, pero el cliente {$cliente->clienteID} no tiene CC asignada.");
        }
    }
}
