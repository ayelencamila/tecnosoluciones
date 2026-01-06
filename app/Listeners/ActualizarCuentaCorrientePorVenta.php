<?php


// app/Listeners/ActualizarCuentaCorrientePorVenta.php

namespace App\Listeners;

use App\Events\VentaRegistrada;
use App\Services\CuentasCorrientes\VerificarEstadoCuentaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActualizarCuentaCorrientePorVenta
{
    protected VerificarEstadoCuentaService $verificarEstadoService;

    public function __construct(VerificarEstadoCuentaService $verificarEstadoService)
    {
        $this->verificarEstadoService = $verificarEstadoService;
    }

    public function handle(VentaRegistrada $event): void
    {
        if ($event->metodoPago !== 'cuenta_corriente') {
            return;
        }

        $venta = $event->venta;
        $cliente = $venta->cliente;

        if ($cliente->cuentaCorriente) {
            // 1. Registrar débito
            $cliente->cuentaCorriente->registrarDebito(
                $venta->total,
                'Venta (Comprobante '.$venta->numero_comprobante.')',
                Carbon::now()->addDays($cliente->cuentaCorriente->getDiasGraciaAplicables()),
                $venta->venta_id,
                'ventas',
                $event->userID
            );

            Log::info("CC actualizada para Venta ID: {$venta->venta_id}");

            // 2. VALIDACIÓN REACTIVA: Verificar si esta venta causó incumplimiento
            // Esto bloquea INMEDIATAMENTE sin esperar al comando diario
            $cc = $cliente->cuentaCorriente->fresh(); // Recargar con saldo actualizado
            
            $datos = $this->verificarEstadoService->calcularDatosCuenta($cc);
            $incumplimiento = $this->verificarEstadoService->evaluarIncumplimiento($cc, $datos);

            if ($incumplimiento['hayIncumplimiento']) {
                Log::warning("Bloqueo reactivo disparado para cliente {$cliente->clienteID} después de venta {$venta->venta_id}");
                $this->verificarEstadoService->aplicarAccionCredito($cc, $incumplimiento);
            }

        } else {
            Log::warning("Venta {$venta->venta_id} a CC, pero el cliente {$cliente->clienteID} no tiene CC asignada.");
        }
    }
}
