<?php

namespace App\Services\CuentasCorrientes;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\User;
use App\Jobs\NotificarIncumplimientoCC;
use App\Notifications\IncumplimientoCCNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class VerificarEstadoCuentaService
{
    public function ejecutar(): void
    {
        $inicioTiempo = microtime(true);
        
        Log::info('═══════════════════════════════════════════════════════════════');
        Log::info('>>> [CU-09] INICIO PROCESO AUTOMÁTICO DE CONTROL DE CUENTAS CORRIENTES <<<');
        Log::info("Fecha/Hora: " . now()->format('Y-m-d H:i:s'));
        Log::info('═══════════════════════════════════════════════════════════════');

        // 1. Obtener Parámetros de Configuración (Pre-Condición)
        $bloqueoAutomatico = Configuracion::getBool('bloqueo_automatico_cc', true);
        $limiteGlobal = Configuracion::get('limite_credito_global', 100000.00);
        $diasGraciaGlobal = Configuracion::get('dias_gracia_global', 30);
        
        Log::info("[CU-09 Config] Bloqueo automático: " . ($bloqueoAutomatico ? 'ACTIVADO' : 'DESACTIVADO'));
        Log::info("[CU-09 Config] Límite crédito global: $" . number_format($limiteGlobal, 2));
        Log::info("[CU-09 Config] Días de gracia: $diasGraciaGlobal días");
        
        // 2. Selección Diaria (Paso 1): Clientes Mayoristas con CC habilitada
        $cuentas = CuentaCorriente::whereHas('cliente', function ($q) {
                $q->whereHas('tipoCliente', fn($t) => $t->where('nombreTipo', 'Mayorista'));
            })
            ->with(['cliente', 'estadoCuentaCorriente'])
            ->get();

        $totalCuentas = $cuentas->count();
        Log::info("[CU-09 Paso 1] Total cuentas a evaluar: $totalCuentas");

        $procesadas = 0;
        $bloqueadas = 0;
        $enRevision = 0;
        $normalizadas = 0;
        $errores = 0;
        $notificacionesEnviadas = 0;

        foreach ($cuentas as $cc) {
            try {
                // Delegamos el procesamiento individual
                $resultado = $this->procesarCuenta($cc, $bloqueoAutomatico);
                
                if ($resultado === 'bloqueada') {
                    $bloqueadas++;
                    $notificacionesEnviadas++;
                }
                if ($resultado === 'revision') {
                    $enRevision++;
                    $notificacionesEnviadas++;
                }
                if ($resultado === 'normalizada') {
                    $normalizadas++;
                }
                
                $procesadas++;

            } catch (\Exception $e) {
                // Excepción 6a: Error al registrar/procesar. Se registra y continúa.
                $errores++;
                Log::error("[CU-09 ERROR] CC ID {$cc->cuentaCorrienteID} - Cliente: {$cc->cliente->nombreCliente}");
                Log::error("[CU-09 ERROR] Detalle: " . $e->getMessage());
                Log::error("[CU-09 ERROR] Trace: " . $e->getTraceAsString());
            }
        }

        $tiempoEjecucion = round(microtime(true) - $inicioTiempo, 2);
        
        Log::info('═══════════════════════════════════════════════════════════════');
        Log::info(">>> [CU-09] FIN PROCESO - Duración: {$tiempoEjecucion}s <<<");
        Log::info("📊 RESUMEN:");
        Log::info("   • Total evaluadas: $procesadas de $totalCuentas");
        Log::info("   • Bloqueadas: $bloqueadas");
        Log::info("   • En revisión: $enRevision");
        Log::info("   • Normalizadas: $normalizadas");
        Log::info("   • Notificaciones enviadas: $notificacionesEnviadas");
        Log::info("   • Errores: $errores");
        Log::info('═══════════════════════════════════════════════════════════════');
        
        if ($errores > 0) {
            Log::warning("[CU-09] ⚠️ El proceso finalizó con $errores error(es). Revisar logs anteriores.");
        }
    }

    /**
     * CU-09 Paso 4: Notificar al administrador/vendedor (Panel + WhatsApp)
     */
    private function notificarAdministradores(
        CuentaCorriente $cc, 
        string $motivo, 
        string $tipoAccion,
        float $saldoTotal,
        float $saldoVencido,
        float $limiteCredito
    ): void {
        // 1. Obtener administradores (usuarios con role 'admin')
        $administradores = User::where('role', 'admin')->get();
        
        if ($administradores->isEmpty()) {
            Log::warning("[CU-09 Paso 4] No hay administradores configurados para recibir notificaciones.");
            return;
        }

        // 2. Enviar notificación al panel del sistema (campanita)
        Notification::send($administradores, new IncumplimientoCCNotification(
            $cc->cliente,
            $motivo,
            $tipoAccion,
            $saldoTotal,
            $saldoVencido,
            $limiteCredito
        ));

        Log::info("🔔 [CU-09 Paso 4] Notificación enviada al panel de " . $administradores->count() . " administrador(es).");

        // 3. Enviar WhatsApp al administrador principal (si está configurado)
        $adminWhatsApp = Configuracion::get('whatsapp_admin_notificaciones');
        if ($adminWhatsApp) {
            // Usar el Job existente para WhatsApp
            NotificarIncumplimientoCC::dispatch($cc, $motivo, 'admin_alert');
            Log::info("📱 [CU-09 Paso 4] WhatsApp programado para administrador: {$adminWhatsApp}");
        }
    }

    /**
     * Evalúa una cuenta individual (Pasos 2 a 7)
     */
    private function procesarCuenta(CuentaCorriente $cc, bool $bloqueoAutomatico): string
    {
        // Paso 2: Cálculo de saldos
        $datosCalculados = $this->calcularDatosCuenta($cc);
        
        // Paso 3: Evaluación de incumplimiento
        $evaluacion = $this->evaluarIncumplimiento($datosCalculados);

        $estadoActual = $cc->estadoCuentaCorriente->nombreEstado;
        $accionTomada = 'ninguna';

        if ($evaluacion['incumplimiento']) {
            // Paso 4: Notificación Interna
            $this->notificarAdministradores(
                $cc, 
                $evaluacion['motivo'], 
                $bloqueoAutomatico ? 'bloqueo' : 'revision',
                $datosCalculados['saldoTotal'],
                $datosCalculados['saldoVencido'],
                $datosCalculados['limiteCredito']
            );
            
            // Paso 5: Acción sobre el crédito
            $accionTomada = $this->aplicarAccionCredito(
                $cc, 
                $evaluacion['motivo'], 
                $bloqueoAutomatico, 
                $estadoActual
            );

            // Paso 6: Comunicación al Cliente (Mora/Recordatorio)
            if ($accionTomada === 'ninguna') {
                 NotificarIncumplimientoCC::dispatch($cc, $evaluacion['motivo'], 'recordatorio');
            }

        } else {
            // Paso 7: Normalización automática
            $accionTomada = $this->normalizarCuentaSiCorresponde(
                $cc, 
                $estadoActual, 
                $datosCalculados['saldoTotal']
            );
        }

        return $accionTomada;
    }

    /**
     * Paso 2: Calcula saldos y límites (Responsabilidad: Cálculo)
     */
    private function calcularDatosCuenta(CuentaCorriente $cc): array
    {
        return [
            'saldoTotal' => $cc->saldo,
            'saldoVencido' => $cc->calcularSaldoVencido(),
            'limiteCredito' => $cc->getLimiteCreditoAplicable(),
        ];
    }

    /**
     * Paso 3: Evalúa si existe incumplimiento (Responsabilidad: Decisión de negocio)
     */
    private function evaluarIncumplimiento(array $datos): array
    {
        $superaLimite = $datos['saldoTotal'] > $datos['limiteCredito'];
        $tieneVencidos = $datos['saldoVencido'] > 0;
        $incumplimiento = $superaLimite || $tieneVencidos;

        // Construir motivo descriptivo
        $motivos = [];
        if ($superaLimite) {
            $motivos[] = sprintf(
                "Supera límite ($%.2f > $%.2f)", 
                $datos['saldoTotal'], 
                $datos['limiteCredito']
            );
        }
        if ($tieneVencidos) {
            $motivos[] = sprintf("Saldo vencido ($%.2f)", $datos['saldoVencido']);
        }

        return [
            'incumplimiento' => $incumplimiento,
            'motivo' => implode(', ', $motivos),
        ];
    }

    /**
     * Paso 5: Aplica acción sobre el crédito (Responsabilidad: Cambio de estado)
     */
    private function aplicarAccionCredito(
        CuentaCorriente $cc, 
        string $motivo, 
        bool $bloqueoAutomatico, 
        string $estadoActual
    ): string {
        if ($bloqueoAutomatico) {
            // Excepción 5a: Bloqueo Automático
            if ($estadoActual !== 'Bloqueada') {
                $cc->bloquear("Automático: $motivo", null); // null = Sistema automático
                Log::warning("[CU-09] CC {$cc->cuentaCorrienteID} BLOQUEADA. Motivo: $motivo");
                
                // Notificar cambio crítico al cliente
                NotificarIncumplimientoCC::dispatch($cc, $motivo, 'bloqueo');
                return 'bloqueada';
            }
        } else {
            // Excepción 5b: Pendiente de Aprobación
            if ($estadoActual === 'Activa') {
                $cc->ponerEnRevision("Automático: $motivo", null);
                Log::info("[CU-09] CC {$cc->cuentaCorrienteID} en REVISIÓN. Motivo: $motivo");
                
                // Notificar al cliente
                NotificarIncumplimientoCC::dispatch($cc, $motivo, 'revision');
                return 'revision';
            }
        }

        return 'ninguna';
    }

    /**
     * Paso 7: Normaliza cuenta si corresponde (Responsabilidad: Normalización)
     */
    private function normalizarCuentaSiCorresponde(
        CuentaCorriente $cc, 
        string $estadoActual, 
        float $saldoTotal
    ): string {
        // Si la cuenta estaba castigada y ya cumple las condiciones, la liberamos
        if (in_array($estadoActual, ['Bloqueada', 'Pendiente de Aprobación'])) {
            $cc->desbloquear("Automático: Condiciones normalizadas (Saldo: $$saldoTotal)", null);
            Log::info("[CU-09] CC {$cc->cuentaCorrienteID} NORMALIZADA automáticamente.");
            return 'normalizada';
        }

        return 'ninguna';
    }
}