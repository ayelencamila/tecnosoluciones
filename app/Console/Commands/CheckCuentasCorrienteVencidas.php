<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CuentasCorrientes\VerificarEstadoCuentaService;
use Illuminate\Support\Facades\Log;

class CheckCuentasCorrienteVencidas extends Command
{
    protected $signature = 'cc:check-vencimientos';
    protected $description = 'CU-09: Control automático de Cuentas Corrientes (Saldos y Vencimientos)';

    public function handle(VerificarEstadoCuentaService $service)
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