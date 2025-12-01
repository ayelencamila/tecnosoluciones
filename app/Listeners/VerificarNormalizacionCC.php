<?php

namespace App\Listeners;

use App\Events\PagoRegistrado;
use Illuminate\Support\Facades\Log;

/**
 * CU-09 Paso 7: Normalización automática de cuenta corriente
 * 
 * Al registrarse pagos que dejan vencido = 0 y total ≤ límite, 
 * levantar el bloqueo (automático o manual con motivo).
 */
class VerificarNormalizacionCC
{
    public function handle(PagoRegistrado $event): void
    {
        $pago = $event->pago;
        
        // Obtener el cliente asociado al pago
        $cliente = $pago->cliente;
        
        if (!$cliente || !$cliente->cuentaCorriente) {
            Log::info("Pago ID {$pago->pagoID}: Cliente sin cuenta corriente. No se verifica normalización.");
            return;
        }

        $cc = $cliente->cuentaCorriente;
        $estadoActual = $cc->estadoCuentaCorriente?->nombreEstado ?? 'Desconocido';

        // Solo procesar si está bloqueada o pendiente
        if (!in_array($estadoActual, ['Bloqueada', 'Pendiente de Aprobación'])) {
            Log::info("Pago ID {$pago->pagoID}: CC ya está activa. No requiere normalización.");
            return;
        }

        // CU-09 Paso 2: Recalcular saldo vencido y límite
        $saldoVencido = $cc->calcularSaldoVencido();
        $saldoTotal = $cc->saldo;
        $limiteCredito = $cc->getLimiteCreditoAplicable();

        // CU-09 Paso 7: Condiciones de normalización
        $cumpleCondiciones = ($saldoVencido == 0) && ($saldoTotal <= $limiteCredito);

        if ($cumpleCondiciones) {
            $motivoNormalizacion = "Automático: Pago ID {$pago->pagoID} normaliza situación. " .
                                   "Saldo vencido: $0, Saldo total: \${$saldoTotal} ≤ Límite: \${$limiteCredito}";
            
            $cc->desbloquear($motivoNormalizacion, $event->userID);
            
            Log::info("✅ [CU-09 Paso 7] CC {$cc->cuentaCorrienteID} NORMALIZADA automáticamente después del pago.");
            
            // Opcional: Notificar al cliente que su cuenta fue reactivada
            // NotificarIncumplimientoCC::dispatch($cc, "Su cuenta ha sido habilitada", 'habilitacion');
        } else {
            Log::info("⚠️ Pago ID {$pago->pagoID}: Aún no cumple condiciones para normalización. " .
                     "Saldo vencido: \${$saldoVencido}, Saldo total: \${$saldoTotal}, Límite: \${$limiteCredito}");
        }
    }
}
