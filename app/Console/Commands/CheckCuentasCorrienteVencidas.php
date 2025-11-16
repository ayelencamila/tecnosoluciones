<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\CuentaCorriente;
use App\Models\Configuracion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Auditoria;
use Illuminate\Support\Carbon;
use App\Jobs\NotificarIncumplimientoCC; 

class CheckCuentasCorrienteVencidas extends Command
{
    protected $signature = 'cuentas:check-vencidas {--force-block : Ignora la política y bloquea igualmente.}';
    protected $description = 'Verifica Cuentas Corrientes, aplica bloqueos y encola notificaciones (CU-09).';

    // IDs de Estados cacheados
    private $estadoBloqueadaID;
    private $estadoPendienteID;
    private $estadoActivaID;

    /**
     * Carga los IDs de los estados para evitar consultas en el bucle.
     */
    private function cacheEstados(): bool
    {
        $estadoBloqueada = EstadoCuentaCorriente::where('nombreEstado', 'Bloqueada')->first();
        $estadoPendiente = EstadoCuentaCorriente::where('nombreEstado', 'Pendiente de Aprobación')->first();
        $estadoActiva = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first();

        if (!$estadoBloqueada || !$estadoPendiente || !$estadoActiva) {
            $this->error('Error: Faltan estados ("Bloqueada", "Pendiente de Aprobación", "Activa") en la BD.');
            Log::error('CU-09 Job: Faltan estados de CC en la BD.');
            return false;
        }

        $this->estadoBloqueadaID = $estadoBloqueada->estadoCuentaCorrienteID;
        $this->estadoPendienteID = $estadoPendiente->estadoCuentaCorrienteID;
        $this->estadoActivaID = $estadoActiva->estadoCuentaCorrienteID;
        return true;
    }

    public function handle()
    {
        $this->info('Iniciando CU-09: Control automático de Cuentas Corrientes...');
        Log::info('CU-09 Job: Iniciado.');

        if (!$this->cacheEstados()) {
            return Command::FAILURE;
        }

        // 1. LEER PARAMETROS GLOBALES (Integración CU-31)
        $politicaAutoBlock = Configuracion::getBool('bloqueo_automatico_cc', false);
        
        if ($this->option('force-block')) {
            $politicaAutoBlock = true;
            $this->warn('Modo --force-block activo. Se ignoró la configuración global.');
        }
        $this->info("Política de Bloqueo Automático: " . ($politicaAutoBlock ? 'ACTIVADA' : 'DESACTIVADA'));

        $cuentasProcesadas = 0;
        $cuentasConIncumplimiento = 0;
        $cuentasBloqueadas = 0;
        $cuentasMarcadasPendientes = 0;
        $cuentasReactivadas = 0;

        // 2. PROCESAMIENTO POR LOTES
        Cliente::with('cuentaCorriente.estadoCuentaCorriente')
            ->has('cuentaCorriente')
            ->chunk(100, function ($clientes) use (
                &$cuentasProcesadas, 
                &$cuentasConIncumplimiento, 
                &$cuentasBloqueadas, 
                &$cuentasMarcadasPendientes, 
                &$cuentasReactivadas, 
                $politicaAutoBlock
            ) {
                
                foreach ($clientes as $cliente) {
                    $cuentasProcesadas++;
                    $cc = $cliente->cuentaCorriente;
                    $estadoActualID = $cc->estadoCuentaCorrienteID;
                    
                    $saldoVencido = $cc->calcularSaldoVencido(); 
                    $saldoTotal = $cc->saldo; // Corregido a 'saldo'
                    $limiteCredito = $cc->getLimiteCreditoAplicable();

                    $hayIncumplimiento = ($saldoVencido > 0 || $saldoTotal > $limiteCredito);

                    if ($hayIncumplimiento) {
                        $cuentasConIncumplimiento++;
                        $motivoDetallado = "Saldo Vencido: $saldoVencido | Total: $saldoTotal (Límite: $limiteCredito)";

                        // Notificación automática al administrador
                        NotificarIncumplimientoCC::dispatch($cliente, $motivoDetallado);

                        if ($politicaAutoBlock) {
                            // 5a: Bloqueo automático
                            if ($estadoActualID != $this->estadoBloqueadaID) {
                                // Usamos el método del Modelo
                                $cc->bloquear($motivoDetallado, null); 
                                $cuentasBloqueadas++;
                            }
                        } else {
                            // 5b: Marcar Pendiente
                            if ($estadoActualID != $this->estadoPendienteID && $estadoActualID != $this->estadoBloqueadaID) {
                                // Usamos el método del Modelo
                                $cc->ponerEnRevision($motivoDetallado, null);
                                $cuentasMarcadasPendientes++;
                            }
                        }

                    } else {
                        // Auto-sanación
                        if ($estadoActualID == $this->estadoPendienteID || $estadoActualID == $this->estadoBloqueadaID) {
                            // Usamos el método del Modelo
                            $cc->desbloquear('Deuda regularizada.', null);
                            $cuentasReactivadas++;
                        }
                    }
                }
            });

        $this->info("CU-09: Verificación completada.");
        $this->line("Cuentas Procesadas: $cuentasProcesadas");
        $this->line("Cuentas con Incumplimiento: $cuentasConIncumplimiento");
        $this->line("Cuentas Bloqueadas (Nuevas): $cuentasBloqueadas");
        $this->line("Cuentas Pendientes Aprobación (Nuevas): $cuentasMarcadasPendientes");
        $this->line("Cuentas Reactivadas: $cuentasReactivadas");
        Log::info('CU-09 Job: Finalizado.');

        return Command::SUCCESS;
    }

    // ¡ELIMINADO!
    // El método 'cambiarEstado()' se fue. 
    // Ahora usamos $cc->bloquear(), $cc->desbloquear(), etc.
    // Esto es mucho más limpio y respeta tu arquitectura de Modelo.
}