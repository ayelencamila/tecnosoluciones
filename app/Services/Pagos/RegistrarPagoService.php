<?php

namespace App\Services\Pagos;

use App\Events\PagoRegistrado;
use App\Models\Pago;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RegistrarPagoService
{
    public function handle(array $data, int $userId): Pago
    {
        return DB::transaction(function () use ($data, $userId) {
            
            $cliente = Cliente::with('cuentaCorriente')->findOrFail($data['clienteID']);

            // CORRECCIÓN: Usamos medioPagoID
            $pago = Pago::create([
                'clienteID'   => $cliente->clienteID,
                'user_id'     => $userId,
                'monto'       => $data['monto'],
                'medioPagoID' => $data['medioPagoID'], // <--- Cambio clave
                'fecha_pago'  => now(),
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            if ($cliente->cuentaCorriente) {
                $descripcion = "Pago Recibido - Recibo: {$pago->numero_recibo}";
                
                $cliente->cuentaCorriente->registrarCredito(
                    $pago->monto,
                    $descripcion,
                    $pago->pagoID,
                    'pagos',
                    $userId
                );
            }

            $this->imputarPagoAutomaticamente($pago, $cliente);

            // CU-09 Paso 7: Disparar evento para verificar normalización
            event(new PagoRegistrado($pago, $userId));

            Log::info("Pago registrado e imputado: ID {$pago->pagoID}");

            return $pago;
        });
    }

    private function imputarPagoAutomaticamente(Pago $pago, Cliente $cliente): void
    {
        $montoDisponible = $pago->monto;

        // Solo buscamos ventas activas (no anuladas)
        $ventasCliente = Venta::where('clienteID', $cliente->clienteID)
            ->whereHas('estado', fn($q) => $q->where('nombreEstado', '!=', 'Anulada'))
            ->orderBy('fecha_venta', 'asc')
            ->get();

        foreach ($ventasCliente as $venta) {
            if ($montoDisponible <= 0) break;

            $saldoPendiente = $venta->saldo_pendiente;

            if ($saldoPendiente > 0) {
                $montoAImputar = min($montoDisponible, $saldoPendiente);

                $pago->ventasImputadas()->attach($venta->venta_id, [
                    'monto_imputado' => $montoAImputar
                ]);

                $montoDisponible -= $montoAImputar;
            }
        }
    }
}