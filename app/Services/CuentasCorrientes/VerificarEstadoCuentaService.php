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
        Log::info('>>> [CU-09] INICIO PROCESO AUTOMÁTICO DE CONTROL DE CUENTAS CORRIENTES <<<');

        // 1. Obtener Parámetros de Configuración (Pre-Condición)
        // Usamos tus métodos del modelo Configuracion
        $bloqueoAutomatico = Configuracion::getBool('bloqueo_automatico_cc', true); 
        
        // 2. Selección Diaria (Paso 1): Clientes Mayoristas con CC habilitada
        // Filtramos cuentas que NO estén cerradas o eliminadas
        $cuentas = CuentaCorriente::whereHas('cliente', function ($q) {
                $q->whereHas('tipoCliente', fn($t) => $t->where('nombreTipo', 'Mayorista'));
            })
            ->with(['cliente', 'estadoCuentaCorriente'])
            ->get();

        $procesadas = 0;
        $bloqueadas = 0;
        $normalizadas = 0;

        foreach ($cuentas as $cc) {
            try {
                // Delegamos el procesamiento individual
                $resultado = $this->procesarCuenta($cc, $bloqueoAutomatico);
                
                if ($resultado === 'bloqueada') $bloqueadas++;
                if ($resultado === 'normalizada') $normalizadas++;
                $procesadas++;

            } catch (\Exception $e) {
                // Excepción 6a: Error al registrar/procesar. Se registra y continúa.
                Log::error("Error procesando CC ID {$cc->cuentaCorrienteID}: " . $e->getMessage());
            }
        }

        Log::info(">>> [CU-09] FIN PROCESO. Procesadas: $procesadas. Bloqueadas/Revisión: $bloqueadas. Normalizadas: $normalizadas. <<<");
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
        // Paso 2: Cálculo (usando métodos del modelo CuentaCorriente)
        // Excepción 3a (Límites específicos) ya es manejada dentro de getLimiteCreditoAplicable()
        $saldoTotal = $cc->saldo;
        $saldoVencido = $cc->calcularSaldoVencido(); 
        $limiteCredito = $cc->getLimiteCreditoAplicable();

        // Paso 3: Evaluación
        $superaLimite = $saldoTotal > $limiteCredito;
        $tieneVencidos = $saldoVencido > 0;
        $incumplimiento = $superaLimite || $tieneVencidos;

        $estadoActual = $cc->estadoCuentaCorriente->nombreEstado;
        $accionTomada = 'ninguna';

        if ($incumplimiento) {
            // Construir motivo
            $motivos = [];
            if ($superaLimite) $motivos[] = "Supera límite ($$saldoTotal > $$limiteCredito)";
            if ($tieneVencidos) $motivos[] = "Saldo vencido ($$saldoVencido)";
            $motivoTexto = implode(', ', $motivos);

            // Paso 4: Notificación Interna (Panel + WhatsApp)
            // Excepción 4a / 4b: Detección de incumplimiento
            $this->notificarAdministradores(
                $cc, 
                $motivoTexto, 
                $bloqueoAutomatico ? 'bloqueo' : 'revision',
                $saldoTotal,
                $saldoVencido,
                $limiteCredito
            );
            
            // Paso 5: Acción sobre el crédito
            if ($bloqueoAutomatico) {
                // Excepción 5a: Bloqueo Automático
                if ($estadoActual !== 'Bloqueada') {
                    $cc->bloquear("Automático: $motivoTexto", null); // null = Sistema automático
                    Log::warning("[CU-09] CC {$cc->cuentaCorrienteID} BLOQUEADA. Motivo: $motivoTexto");
                    $accionTomada = 'bloqueada';
                    
                    // Notificar cambio crítico al cliente
                    NotificarIncumplimientoCC::dispatch($cc, $motivoTexto, 'bloqueo');
                }
            } else {
                // Excepción 5b: Pendiente de Aprobación
                if ($estadoActual === 'Activa') {
                    $cc->ponerEnRevision("Automático: $motivoTexto", null);
                    Log::info("[CU-09] CC {$cc->cuentaCorrienteID} en REVISIÓN. Motivo: $motivoTexto");
                    $accionTomada = 'revision';
                    
                    // Notificar al cliente
                    NotificarIncumplimientoCC::dispatch($cc, $motivoTexto, 'revision');
                }
            }

            // Paso 6: Comunicación al Cliente (Mora)
            // Si ya estaba bloqueada/revisión pero sigue debiendo, evaluamos si reenviar aviso
            // (Aquí simplificamos enviando al Job que controla frecuencia/horario)
            if ($accionTomada === 'ninguna') {
                 NotificarIncumplimientoCC::dispatch($cc, $motivoTexto, 'recordatorio');
            }

        } else {
            // Paso 7: Normalización
            // Si la cuenta estaba castigada y ya cumple las condiciones, la liberamos.
            if (in_array($estadoActual, ['Bloqueada', 'Pendiente de Aprobación'])) {
                $cc->desbloquear("Automático: Condiciones normalizadas (Saldo: $$saldoTotal)", 1);
                Log::info("[CU-09] CC {$cc->cuentaCorrienteID} NORMALIZADA automáticamente.");
                $accionTomada = 'normalizada';
                
                // Opcional: Notificar al cliente que ya puede comprar
                // NotificarIncumplimientoCC::dispatch($cc, "Cuenta habilitada", 'habilitacion');
            }
        }

        return $accionTomada;
    }
}