<?php

namespace App\Console\Commands;

use App\Models\CotizacionProveedor;
use App\Jobs\EnviarSolicitudCotizacionEmail;
use App\Jobs\EnviarSolicitudCotizacionWhatsApp;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command: Enviar Recordatorios de Cotización
 * 
 * Envía recordatorios automáticos a proveedores que no han respondido:
 * - Día 3: Primer recordatorio (si vence en 7 días o menos)
 * - Día 5: Segundo recordatorio (si vence en 2 días o menos)
 * 
 * Lineamientos:
 * - Kendall: Automatización del seguimiento de proveedores
 * - Sommerville: Tareas programadas para gestión proactiva
 * 
 * Se ejecuta diariamente a las 09:00 via cron
 */
class EnviarRecordatoriosCotizacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotizaciones:enviar-recordatorios 
                            {--canal=ambos : Canal de envío (email|whatsapp|ambos)}
                            {--forzar-reenvio : Enviar aunque ya se haya enviado recordatorio}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios automáticos a proveedores que no han respondido cotizaciones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔔 Buscando cotizaciones para enviar recordatorios...');

        $canal = $this->option('canal');
        $forzarReenvio = $this->option('forzar-reenvio');

        // Buscar cotizaciones pendientes de respuesta
        $query = CotizacionProveedor::with(['solicitud.detalles.producto', 'proveedor'])
            ->whereIn('estado_envio', ['Enviado', 'Pendiente'])
            ->whereHas('solicitud', function ($q) {
                $q->whereHas('estado', function ($q2) {
                    $q2->where('nombre', 'Abierta');
                })
                ->where('fecha_vencimiento', '>', now()); // Que no esté vencida
            });

        // Si no se fuerza reenvío, filtrar las que aún no tienen recordatorio
        if (!$forzarReenvio) {
            $query->whereNull('fecha_recordatorio');
        }

        $cotizaciones = $query->get();

        if ($cotizaciones->isEmpty()) {
            $this->info('✅ No hay cotizaciones pendientes para enviar recordatorio');
            return self::SUCCESS;
        }

        $this->info("📋 Encontradas {$cotizaciones->count()} cotizaciones pendientes");

        $enviados = 0;
        $omitidos = 0;

        foreach ($cotizaciones as $cotizacion) {
            $solicitud = $cotizacion->solicitud;
            $diasParaVencer = now()->diffInDays($solicitud->fecha_vencimiento, false);

            // Reglas de recordatorio
            $debeEnviar = $this->debeEnviarRecordatorio($cotizacion, $diasParaVencer);

            if (!$debeEnviar) {
                $omitidos++;
                continue;
            }

            try {
                if ($canal === 'email' || $canal === 'ambos') {
                    EnviarSolicitudCotizacionEmail::dispatch($cotizacion, esRecordatorio: true);
                    $this->info("📧 Email recordatorio → {$cotizacion->proveedor->razon_social} (#{$solicitud->codigo_solicitud})");
                }

                if ($canal === 'whatsapp' || $canal === 'ambos') {
                    EnviarSolicitudCotizacionWhatsApp::dispatch($cotizacion, esRecordatorio: true);
                    $this->info("📱 WhatsApp recordatorio → {$cotizacion->proveedor->razon_social} (#{$solicitud->codigo_solicitud})");
                }

                // Actualizar fecha de recordatorio
                $cotizacion->update([
                    'fecha_recordatorio' => now(),
                ]);

                $enviados++;

                Log::info("Recordatorio enviado a {$cotizacion->proveedor->razon_social} para solicitud #{$solicitud->codigo_solicitud}");

            } catch (\Exception $e) {
                $this->error("❌ Error enviando recordatorio a {$cotizacion->proveedor->razon_social}: " . $e->getMessage());
                Log::error("Error en recordatorio: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->info("  ✅ Recordatorios enviados: {$enviados}");
        $this->info("  ⏭️  Omitidos (no cumplen reglas): {$omitidos}");

        return self::SUCCESS;
    }

    /**
     * Determina si debe enviar recordatorio según días para vencer
     * 
     * Reglas:
     * - Primer recordatorio: 3 días después del envío inicial Y 3-4 días para vencer
     * - Segundo recordatorio: 5 días después del envío inicial Y 1-2 días para vencer
     */
    private function debeEnviarRecordatorio(CotizacionProveedor $cotizacion, int $diasParaVencer): bool
    {
        // Si no hay fecha de envío, no se puede calcular
        if (!$cotizacion->fecha_envio) {
            return false;
        }

        $diasDesdeEnvio = now()->diffInDays($cotizacion->fecha_envio);

        // Primer recordatorio: 3 días después del envío, si quedan 3-4 días
        if ($diasDesdeEnvio >= 3 && $diasDesdeEnvio < 5 && $diasParaVencer >= 3 && $diasParaVencer <= 4) {
            return true;
        }

        // Segundo recordatorio: 5 días después del envío, si quedan 1-2 días
        if ($diasDesdeEnvio >= 5 && $diasParaVencer >= 1 && $diasParaVencer <= 2) {
            return true;
        }

        return false;
    }
}
