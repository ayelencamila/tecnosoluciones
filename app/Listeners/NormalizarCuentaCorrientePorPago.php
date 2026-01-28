<?php

namespace App\Listeners;

use App\Events\PagoRegistrado;
use App\Services\CuentasCorrientes\VerificarEstadoCuentaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener que normaliza automáticamente una cuenta corriente al registrar un pago
 * 
 * Este listener se dispara cuando se registra un pago y:
 * 1. Verifica si el cliente tiene cuenta corriente
 * 2. Ejecuta el servicio de verificación para evaluar si debe normalizarse
 * 3. Si el cliente estaba bloqueado y ahora cumple condiciones, lo desbloquea
 * 
 * Relacionado con: CU-09 (Normalización automática), Paso 7
 */
class NormalizarCuentaCorrientePorPago implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private VerificarEstadoCuentaService $verificarEstadoService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(PagoRegistrado $event): void
    {
        $pago = $event->pago;
        $cliente = $pago->cliente;

        // Solo procesar si el cliente tiene cuenta corriente
        if (!$cliente->cuentaCorriente) {
            return;
        }

        Log::info("[Normalización CC] Evaluando cuenta corriente después del pago", [
            'pagoID' => $pago->pagoID,
            'clienteID' => $cliente->clienteID,
            'monto' => $pago->monto,
        ]);

        try {
            // Ejecutar servicio de verificación para esa cuenta específica
            $cc = $cliente->cuentaCorriente;
            
            // Recalcular saldo vencido
            $saldoVencido = $cc->calcularSaldoVencido();
            $saldoTotal = $cc->saldo;
            
            // El bloqueo es por MORA (saldo vencido > 0)
            // Se desbloquea cuando saldo vencido = 0 (deuda saldada)
            // Nota: El límite de crédito ya se valida al momento de la venta
            $sinMora = ($saldoVencido == 0);
            
            if ($sinMora) {
                $estadoActual = $cc->estadoCuentaCorriente->nombreEstado ?? 'Desconocido';
                
                // Si estaba bloqueada o en otro estado problemático, normalizar
                if (in_array($estadoActual, ['Bloqueada', 'Vencida', 'Pendiente de Aprobación'])) {
                    $cc->desbloquear("Automático: Pago cancela mora - Ref: {$pago->referencia}", null);
                    
                    Log::info("✅ [Normalización CC] Cuenta corriente normalizada automáticamente", [
                        'cuentaCorrienteID' => $cc->cuentaCorrienteID,
                        'clienteID' => $cliente->clienteID,
                        'estadoAnterior' => $estadoActual,
                        'saldoActual' => $saldoTotal,
                        'pagoID' => $pago->pagoID,
                    ]);
                }
            } else {
                Log::info("⚠️ [Normalización CC] Aún tiene mora pendiente", [
                    'clienteID' => $cliente->clienteID,
                    'saldoVencido' => $saldoVencido,
                    'saldoTotal' => $saldoTotal,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error("❌ [Normalización CC] Error al evaluar normalización", [
                'clienteID' => $cliente->clienteID,
                'pagoID' => $pago->pagoID,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
