<?php

namespace App\Console\Commands;

use App\Models\SolicitudCotizacion;
use App\Models\CotizacionProveedor;
use App\Jobs\EnviarSolicitudCotizacionWhatsApp;
use App\Notifications\SolicitudCotizacionProveedor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Comando: Enviar Recordatorios de Cotización (CU-20)
 * 
 * Envía recordatorios automáticos a proveedores que:
 * - Recibieron una solicitud pero NO han respondido
 * - La solicitud aún NO ha vencido (hay tiempo)
 * - Han pasado al menos X días desde el envío original
 * 
 * Uso: php artisan cotizaciones:enviar-recordatorios
 * Cron: Se ejecuta diariamente a las 9:00 AM
 * 
 * Lineamientos aplicados:
 * - Kendall: Automatización de procesos de negocio
 * - Profesor: "Se debe poder mandar uno o varios mails o whatsapps"
 */
class EnviarRecordatoriosCotizacionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cotizaciones:enviar-recordatorios 
                            {--dias=2 : Días desde el envío para considerar recordatorio}
                            {--canal=whatsapp : Canal de envío (whatsapp, email, ambos)}';

    /**
     * The console command description.
     */
    protected $description = 'Envía recordatorios a proveedores que no han respondido solicitudes de cotización';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $diasDesdeEnvio = (int) $this->option('dias');
        $canal = $this->option('canal');

        $this->info("📬 Buscando proveedores sin respuesta (enviados hace más de {$diasDesdeEnvio} días)...");
        Log::info('Comando cotizaciones:enviar-recordatorios ejecutado', [
            'dias' => $diasDesdeEnvio,
            'canal' => $canal
        ]);

        try {
            // Buscar cotizaciones enviadas sin respuesta
            $cotizacionesSinRespuesta = CotizacionProveedor::with(['proveedor', 'solicitud'])
                ->where('estado_envio', 'Enviado')
                ->whereNull('fecha_respuesta')
                ->whereNull('motivo_rechazo')
                ->where('recordatorios_enviados', '<', 3) // Máximo 3 recordatorios
                ->whereHas('solicitud', function ($query) {
                    // Solo solicitudes que NO están vencidas ni cerradas
                    $query->where('fecha_vencimiento', '>', now())
                          ->whereHas('estado', fn($q) => $q->where('nombre', 'Enviada'));
                })
                ->where(function ($query) use ($diasDesdeEnvio) {
                    // Envío original hace más de X días
                    $query->where('fecha_envio', '<=', now()->subDays($diasDesdeEnvio));
                    // Y último recordatorio hace más de X días (o nunca se envió recordatorio)
                    $query->where(function ($q) use ($diasDesdeEnvio) {
                        $q->whereNull('ultimo_recordatorio')
                          ->orWhere('ultimo_recordatorio', '<=', now()->subDays($diasDesdeEnvio));
                    });
                })
                ->get();

            if ($cotizacionesSinRespuesta->isEmpty()) {
                $this->info('✅ No hay proveedores pendientes de recordatorio');
                return Command::SUCCESS;
            }

            $this->warn("⚠️ Se encontraron {$cotizacionesSinRespuesta->count()} proveedor(es) sin respuesta");

            $enviados = 0;
            $errores = [];

            foreach ($cotizacionesSinRespuesta as $cotizacion) {
                try {
                    $proveedor = $cotizacion->proveedor;
                    $solicitud = $cotizacion->solicitud;
                    $diasRestantes = now()->diffInDays($solicitud->fecha_vencimiento, false);

                    $this->line("  📤 Enviando recordatorio a: {$proveedor->razon_social}");
                    $this->line("     Solicitud: {$solicitud->codigo_solicitud} - Vence en {$diasRestantes} días");

                    // Enviar por el canal seleccionado
                    if ($canal === 'whatsapp' || $canal === 'ambos') {
                        EnviarSolicitudCotizacionWhatsApp::dispatch($cotizacion, true); // true = es recordatorio
                    }

                    if ($canal === 'email' || $canal === 'ambos') {
                        $proveedor->notify(new SolicitudCotizacionProveedor($cotizacion, true)); // true = es recordatorio
                    }

                    // Actualizar contadores
                    $cotizacion->update([
                        'recordatorios_enviados' => $cotizacion->recordatorios_enviados + 1,
                        'ultimo_recordatorio' => now(),
                    ]);

                    $enviados++;

                } catch (\Exception $e) {
                    $this->error("  ❌ Error con {$cotizacion->proveedor->razon_social}: " . $e->getMessage());
                    $errores[] = [
                        'proveedor' => $cotizacion->proveedor->razon_social,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->newLine();
            $this->info("✅ Recordatorios enviados: {$enviados}");
            
            if (count($errores) > 0) {
                $this->warn("⚠️ Errores: " . count($errores));
            }

            Log::info("Recordatorios de cotización enviados", [
                'enviados' => $enviados,
                'errores' => count($errores)
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar recordatorios: ' . $e->getMessage());
            Log::error('Error en cotizaciones:enviar-recordatorios: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
