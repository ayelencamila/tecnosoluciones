<?php

namespace App\Console\Commands;

use App\Services\Compras\SolicitudCotizacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando: Cerrar Solicitudes de Cotización Vencidas (CU-20)
 * 
 * Verifica solicitudes que pasaron su fecha de vencimiento y las marca
 * automáticamente como "Vencidas". Esto permite al sistema saber cuáles
 * proveedores respondieron a tiempo y cerrar el período de cotización.
 * 
 * Uso: php artisan cotizaciones:cerrar-vencidas
 * Cron: Se ejecuta cada hora automáticamente
 * 
 * Lineamientos aplicados:
 * - Kendall: Automatización de procesos de negocio
 * - Profesor: "Los pedidos deben tener un tiempo, debe estar cerrado el pedido de precios"
 */
class CerrarSolicitudesVencidasCommand extends Command
{
    use \App\Console\Concerns\EjecutaPorEmpresa;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cotizaciones:cerrar-vencidas';

    /**
     * The console command description.
     */
    protected $description = 'Marca como vencidas las solicitudes de cotización que superaron su fecha límite';

    protected SolicitudCotizacionService $solicitudService;

    public function __construct(SolicitudCotizacionService $solicitudService)
    {
        parent::__construct();
        $this->solicitudService = $solicitudService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->porCadaEmpresa(fn () => $this->procesarEmpresa());
    }

    /**
     * Cuerpo original del comando, ahora acotado a una sola empresa: cada una
     * cierra sus propias solicitudes de cotizacion.
     */
    private function procesarEmpresa(): int
    {
        $this->info('🕐 Verificando solicitudes de cotización vencidas...');
        Log::info('Comando cotizaciones:cerrar-vencidas ejecutado');

        try {
            $cantidadVencidas = $this->solicitudService->marcarSolicitudesVencidas();

            if ($cantidadVencidas > 0) {
                $this->warn("⏰ Se marcaron {$cantidadVencidas} solicitud(es) como vencidas");
                Log::info("Se marcaron {$cantidadVencidas} solicitudes como vencidas");
            } else {
                $this->info('✅ No hay solicitudes vencidas pendientes de marcar');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al verificar solicitudes vencidas: ' . $e->getMessage());
            Log::error('Error en cotizaciones:cerrar-vencidas: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
