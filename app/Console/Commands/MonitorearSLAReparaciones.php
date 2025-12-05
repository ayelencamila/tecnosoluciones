<?php

namespace App\Console\Commands;

use App\Services\Reparaciones\MonitoreoSLAReparacionService;
use Illuminate\Console\Command;

/**
 * Comando para monitorear SLA de reparaciones (CU-14)
 * Se ejecuta automáticamente vía cron/scheduler
 */
class MonitorearSLAReparaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reparaciones:monitorear-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorea el SLA de reparaciones activas y genera alertas a técnicos';

    /**
     * Execute the console command.
     */
    public function handle(MonitoreoSLAReparacionService $service)
    {
        $this->info('===========================================');
        $this->info('  MONITOREO DE SLA - REPARACIONES (CU-14)  ');
        $this->info('===========================================');
        $this->newLine();

        $this->info('⏳ Verificando reparaciones activas...');

        try {
            $stats = $service->verificarYGenerarAlertas();

            $this->newLine();
            $this->info('✅ Monitoreo completado exitosamente');
            $this->newLine();

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total verificadas', $stats['total_verificadas']],
                    ['Exceden SLA', $stats['exceden_sla']],
                    ['Incumplen SLA (>3 días)', $stats['incumplen_sla']],
                    ['Alertas generadas', $stats['alertas_generadas']],
                ]
            );

            if ($stats['alertas_generadas'] > 0) {
                $this->warn("⚠️  Se generaron {$stats['alertas_generadas']} nuevas alertas para técnicos");
            } else {
                $this->comment('ℹ️  No se generaron nuevas alertas');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error durante el monitoreo: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
