<?php

namespace App\Console\Commands;

use App\Console\Concerns\EjecutaPorEmpresa;
use Illuminate\Console\Command;
use App\Services\CuentasCorrientes\VerificarEstadoCuentaService;
use Illuminate\Support\Facades\Log;

class CheckCuentasCorrienteVencidas extends Command
{
    use EjecutaPorEmpresa;

    protected $signature = 'cc:check-vencimientos';
    protected $description = 'CU-09: Control automático de Cuentas Corrientes (Saldos y Vencimientos)';

    public function handle(VerificarEstadoCuentaService $service): int
    {
        return $this->porCadaEmpresa(fn () => $this->procesarEmpresa($service));
    }

    /**
     * Cuerpo original del comando, ahora acotado a una sola empresa: los
     * limites de credito y los dias de gracia son parametros de cada empresa.
     */
    private function procesarEmpresa(VerificarEstadoCuentaService $service): int
    {
        $this->info('⏳ Iniciando proceso CU-09: Control de Cuentas Corrientes...');
        Log::channel('daily')->info('--- Cron: cc:check-vencimientos INICIADO ---');
        
        try {
            $service->ejecutar();
            
            $this->info('✅ Proceso finalizado correctamente.');
            Log::channel('daily')->info('--- Cron: cc:check-vencimientos FINALIZADO EXITOSAMENTE ---');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error crítico en el proceso: ' . $e->getMessage());
            Log::channel('daily')->critical('--- Cron: cc:check-vencimientos FALLÓ: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}