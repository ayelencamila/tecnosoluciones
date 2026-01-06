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

            // CU-10 Paso 7: Imputación manual o automática
            if (isset($data['imputaciones']) && is_array($data['imputaciones'])) {
                $this->imputarPagoManualmente($pago, $data['imputaciones']);
            } else {
                $this->imputarPagoAutomaticamente($pago, $cliente);
            }

            // CU-09 Paso 7: Disparar evento para verificar normalización
            event(new PagoRegistrado($pago, $userId));

            // CU-10 Paso 13: Registrar en historial de operaciones
            \App\Models\Auditoria::registrar(
                \App\Models\Auditoria::ACCION_REGISTRAR_PAGO,
                'pagos',
                $pago->pagoID,
                null,
                [
                    'numero_recibo' => $pago->numero_recibo,
                    'monto' => $pago->monto,
                    'clienteID' => $pago->clienteID,
                    'medioPagoID' => $pago->medioPagoID,
                ],
                "Pago recibido de cliente ID {$cliente->clienteID}",
                "Monto: \${$pago->monto} - Recibo: {$pago->numero_recibo}",
                $userId
            );

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

    /**
     * Imputa el pago manualmente según las instrucciones del usuario (CU-10 Paso 7)
     */
    private function imputarPagoManualmente(Pago $pago, array $imputaciones): void
    {
        foreach ($imputaciones as $imputacion) {
            $pago->ventasImputadas()->attach($imputacion['venta_id'], [
                'monto_imputado' => $imputacion['monto_imputado']
            ]);
        }
    }
}