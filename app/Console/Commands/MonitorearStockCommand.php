<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Compras\MonitoreoStockService;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Log;

/**
 * Comando: Monitoreo Automático de Stock (CU-20)
 * 
 * Este comando se ejecuta desde el Scheduler y:
 * 1. Detecta productos con stock bajo o alta rotación
 * 2. Si automatización está ACTIVADA: genera solicitudes y las ENVÍA automáticamente
 * 3. Si automatización está DESACTIVADA: solo muestra info (usuario crea manualmente)
 * 
 * Opciones:
 * --solo-detectar: Solo muestra productos detectados sin crear nada
 * --forzar-envio: Ignora configuración y ejecuta con envío automático
 */
class MonitorearStockCommand extends Command
{
    protected $signature = 'stock:monitorear 
                            {--solo-detectar : Solo detecta y muestra, sin crear solicitudes}
                            {--forzar-envio : Forzar envío automático (ignora configuración)}';

    protected $description = 'Monitorea el stock y genera/envía solicitudes según configuración del sistema';

    public function handle(MonitoreoStockService $monitoreoService)
    {
        $this->info('🔍 Iniciando monitoreo de stock...');
        $this->newLine();

        try {
            // 1. DETECCIÓN - Siempre se ejecuta
            $productos = $monitoreoService->detectarProductosNecesitanReposicion();
            
            if ($productos->isEmpty()) {
                $this->info('✅ Todo en orden. No hay productos que necesiten reposición.');
                return 0;
            }

            // Mostrar productos detectados
            $this->warn("⚠️  Se detectaron {$productos->count()} producto(s) que necesitan reposición:");
            $this->mostrarTablaProductos($productos);

            // Si solo detectar, terminar aquí
            if ($this->option('solo-detectar')) {
                $this->comment('ℹ️  Modo solo detección. No se crearon solicitudes.');
                return 0;
            }

            // 2. Verificar configuración de automatización
            $automatizacionActiva = Configuracion::get('compras_generacion_automatica', 'false') === 'true';
            $forzarEnvio = $this->option('forzar-envio');

            if (!$automatizacionActiva && !$forzarEnvio) {
                $this->newLine();
                $this->comment('⚙️  Automatización DESACTIVADA en configuración del sistema.');
                $this->comment('👉 Las solicitudes deben crearse manualmente desde el panel web.');
                $this->comment('💡 Para activar: Configuración → Compras → Generar solicitudes automáticamente');
                return 0;
            }

            // 3. GENERACIÓN + ENVÍO AUTOMÁTICO
            $this->newLine();
            if ($forzarEnvio) {
                $this->warn('🔧 Modo forzado: se ejecutará con envío automático');
            } else {
                $this->info('⚙️  Automatización ACTIVADA. Generando y enviando solicitudes...');
            }

            $diasVencimiento = (int) Configuracion::get('compras_dias_vencimiento', 7);
            
            $resultado = $monitoreoService->generarSolicitudesAutomaticas(
                userId: null,
                diasVencimiento: $diasVencimiento,
                incluirAltaRotacion: true,
                enviarAutomaticamente: true // Siempre envía cuando llega aquí
            );

            // 4. Mostrar resultados
            $this->newLine();
            if ($resultado['solicitudes_creadas'] > 0) {
                $this->info("✅ {$resultado['mensaje']}");
                $this->table(
                    ['Métrica', 'Valor'],
                    [
                        ['Solicitudes creadas', $resultado['solicitudes_creadas']],
                        ['Solicitudes enviadas', $resultado['enviadas']],
                        ['Productos procesados', $resultado['productos_procesados']],
                    ]
                );
                
                if ($resultado['enviadas'] > 0) {
                    $this->info('📨 Los proveedores recibirán un Magic Link para responder.');
                }

                // Mostrar errores si los hay
                if (!empty($resultado['errores'])) {
                    $this->newLine();
                    $this->warn('⚠️  Algunos errores:');
                    foreach ($resultado['errores'] as $error) {
                        $this->error("   - {$error}");
                    }
                }
            } else {
                $this->info('ℹ️  ' . $resultado['mensaje']);
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Fallo en monitoreo stock: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('✅ Proceso finalizado.');
        return 0;
    }

    /**
     * Muestra tabla con productos detectados
     */
    protected function mostrarTablaProductos($productos): void
    {
        $headers = ['Producto', 'Stock Actual', 'Mínimo', 'Motivo', 'Proveedor'];
        $data = $productos->map(function ($item) {
            return [
                substr($item['producto']->nombre, 0, 30),
                $item['cantidad_actual'],
                $item['stock_minimo'],
                $item['motivo'] == 'stock_bajo' ? '🔴 Stock bajo' : '🟠 Alta rotación',
                $item['proveedor_habitual']?->razon_social ?? '⚠️ Sin proveedor',
            ];
        });

        $this->table($headers, $data);
    }
}