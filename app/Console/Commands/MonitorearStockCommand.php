<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use App\Services\Compras\MonitoreoStockService;
use App\Services\Compras\SolicitudCotizacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando: Monitoreo Automático de Stock (CU-20)
 * 
 * Ejecuta el monitoreo de stock y genera solicitudes de cotización
 * automáticas para productos bajo el punto de reorden.
 * 
 * Uso: php artisan stock:monitorear
 * Cron: 0 8 * * * (diario a las 8:00)
 * 
 * Lineamientos aplicados:
 * - Kendall: Automatización de procesos de negocio
 * - Laravel: Artisan Command para tareas programadas
 */
class MonitorearStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stock:monitorear 
                            {--generar : Generar solicitudes de cotización automáticas}
                            {--enviar : Enviar solicitudes generadas a proveedores}
                            {--canal=inteligente : Canal de envío (email|whatsapp|ambos|inteligente)}
                            {--dias= : Días de vencimiento para las solicitudes (default: config global)}';

    /**
     * The console command description.
     */
    protected $description = 'Monitorea el stock y genera solicitudes de cotización para productos bajo mínimo';

    protected MonitoreoStockService $monitoreoService;
    protected SolicitudCotizacionService $solicitudService;

    public function __construct(
        MonitoreoStockService $monitoreoService,
        SolicitudCotizacionService $solicitudService
    ) {
        parent::__construct();
        $this->monitoreoService = $monitoreoService;
        $this->solicitudService = $solicitudService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Iniciando monitoreo de stock...');
        Log::info('Comando stock:monitorear ejecutado');

        // 1. Detectar productos bajo stock + alta rotación
        $productosBajoStock = $this->monitoreoService->detectarProductosBajoStock();
        $productosAltaRotacion = $this->monitoreoService->detectarProductosAltaRotacion();
        $todosProductos = $this->monitoreoService->detectarProductosNecesitanReposicion();
        
        if ($todosProductos->isEmpty()) {
            $this->info('✅ No hay productos que necesiten reposición.');
            $this->line('   • Stock bajo: 0');
            $this->line('   • Alta rotación: 0');
            return Command::SUCCESS;
        }

        $this->warn("⚠️ Se detectaron {$todosProductos->count()} producto(s) que necesitan reposición:");
        $this->line("   • Stock bajo: {$productosBajoStock->count()}");
        $this->line("   • Alta rotación con baja cobertura: {$productosAltaRotacion->count()}");
        
        // Mostrar tabla de productos
        $headers = ['Producto', 'Depósito', 'Stock Actual', 'Mínimo', 'Motivo', 'Ventas/mes'];
        $rows = $todosProductos->map(function ($item) {
            return [
                $item['producto']?->nombre ?? 'N/A',
                $item['deposito']?->nombre ?? 'Principal',
                $item['cantidad_actual'],
                $item['stock_minimo'] ?: '-',
                $item['motivo'] === 'stock_bajo' ? '🔴 Stock bajo' : '📈 Alta rotación',
                $item['ventas_mes'] ?? '-',
            ];
        })->toArray();
        
        $this->table($headers, $rows);

        // 2. Verificar si el proceso automático está habilitado (parámetro del sistema)
        $generacionAutomatica = Configuracion::get('compras_generacion_automatica', 'false') === 'true';
        $debeGenerar = $this->option('generar') || $generacionAutomatica;
        $debeEnviar = $this->option('enviar') || $generacionAutomatica;

        // 2. Generar solicitudes automáticas (si se solicitó o está habilitado en configuración)
        if ($debeGenerar) {
            $origen = $generacionAutomatica ? '(proceso automático habilitado)' : '(opción --generar)';
            $this->info("📋 Generando solicitudes de cotización automáticas {$origen}...");
            
            // Usar parámetro de comando o configuración global
            $diasVencimiento = $this->option('dias') 
                ? (int) $this->option('dias') 
                : (int) Configuracion::get('solicitud_cotizacion_dias_vencimiento', 7);
            
            try {
                $resultado = $this->monitoreoService->generarSolicitudesAutomaticas(
                    null, // Usuario null = proceso automático
                    $diasVencimiento
                );

                if ($resultado['solicitudes_creadas'] > 0) {
                    $this->info("✅ Se generaron {$resultado['solicitudes_creadas']} solicitud(es) de cotización");
                    
                    // 3. Enviar automáticamente si está habilitado o se solicitó
                    if ($debeEnviar && isset($resultado['solicitudes'])) {
                        $canal = $this->option('canal') ?? 'inteligente';
                        $this->info("📤 Enviando solicitudes a proveedores por {$canal}...");
                        
                        foreach ($resultado['solicitudes'] as $solicitud) {
                            try {
                                $envio = $this->solicitudService->enviarSolicitudAProveedores(
                                    $solicitud,
                                    $canal
                                );
                                $this->info("  → Solicitud {$solicitud->codigo_solicitud}: {$envio['mensaje']}");
                            } catch (\Exception $e) {
                                $this->error("  → Error enviando {$solicitud->codigo_solicitud}: {$e->getMessage()}");
                            }
                        }
                    }
                } else {
                    $this->warn("⚠️ {$resultado['mensaje']}");
                }

            } catch (\Exception $e) {
                $this->error("❌ Error: {$e->getMessage()}");
                Log::error('Error en comando stock:monitorear: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $this->line('');
            $this->info('💡 Use --generar para crear solicitudes automáticas');
            $this->info('💡 Use --generar --enviar para crear y enviar por WhatsApp');
            $this->info('💡 O active "Generación automática" en Configuración del Sistema');
        }

        // 4. Marcar solicitudes vencidas
        $this->info('⏰ Verificando solicitudes vencidas...');
        $vencidas = $this->solicitudService->marcarSolicitudesVencidas();
        
        if ($vencidas > 0) {
            $this->warn("⏰ Se marcaron {$vencidas} solicitud(es) como vencidas");
        }

        $this->info('✅ Monitoreo completado');
        return Command::SUCCESS;
    }
}
