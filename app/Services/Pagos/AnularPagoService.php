<?php

namespace App\Services\Pagos; // <-- CORREGIDO con '\'

use App\Models\Pago;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AnularPagoService
{
    /**
     * Anula un pago y revierte el crédito en la cuenta corriente.
     *
     * @param Pago $pago El pago a anular
     * @param int $userId El ID del usuario que realiza la anulación
     * @return Pago
     * @throws Exception
     */
    public function handle(Pago $pago, int $userId): Pago
    {
        if ($pago->anulado) {
            throw new Exception("El pago {$pago->numero_recibo} ya se encuentra anulado.");
        }

        return DB::transaction(function () use ($pago, $userId) {
            
            // 1. Marcar el Pago como anulado
            $pago->anulado = true;
            $pago->save();

            // 2. Revertir el movimiento en la Cuenta Corriente (si existe)
            $cliente = $pago->cliente()->with('cuentaCorriente')->first();

            if ($cliente && $cliente->cuentaCorriente) {
                $cuentaCorriente = $cliente->cuentaCorriente;
                $descripcionReverso = "ANULACIÓN Pago - Recibo: {$pago->numero_recibo}";

                // ¡Usamos registrarDebito! (Lo opuesto a registrarCredito)
                // Esto VUELVE a sumar la deuda que el pago había cancelado.
                $cuentaCorriente->registrarDebito(
                    $pago->monto,
                    $descripcionReverso,
                    now(), // La anulación genera una deuda HOY (simple)
                    $pago->pagoID,
                    'pagos (anulacion)',
                    $userId
                );
            }

            // 3. Registrar en Auditoría (Usando tu modelo)
            Auditoria::registrar(
                Auditoria::ACCION_ANULAR_PAGO,
                $pago->getTable(),
                $pago->pagoID,
                ['anulado' => false],
                ['anulado' => true],
                "Anulación de Recibo {$pago->numero_recibo}",
                "Monto anulado: {$pago->monto}",
                $userId
            );

            Log::info("Pago anulado exitosamente: ID {$pago->pagoID} por Usuario {$userId}");

            return $pago;
        });
    }
}