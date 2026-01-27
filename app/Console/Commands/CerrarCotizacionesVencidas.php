<?php

namespace App\Console\Commands;

use App\Models\SolicitudCotizacion;
use App\Models\EstadoSolicitud;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command: Cerrar Cotizaciones Vencidas
 * 
 * Cierra automáticamente las solicitudes de cotización que superaron
 * su fecha de vencimiento sin recibir respuestas.
 * 
 * Lineamientos:
 * - Kendall: Automatización de gestión de cotizaciones
 * - Sommerville: Tareas programadas para mantenimiento del sistema
 * 
 * Se ejecuta diariamente a las 00:00 via cron
 */
class CerrarCotizacionesVencidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotizaciones:cerrar-vencidas 
                            {--dias-gracia=0 : Días de gracia después del vencimiento}
                            {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra automáticamente las solicitudes de cotización vencidas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Buscando solicitudes de cotización vencidas...');

        $diasGracia = (int) $this->option('dias-gracia');
        $fechaLimite = Carbon::now()->subDays($diasGracia);

        $estadoCerrada = EstadoSolicitud::where('nombre', 'Cerrada')->first();
        
        if (!$estadoCerrada) {
            $this->error('❌ No se encontró el estado "Cerrada"');
            return self::FAILURE;
        }

        // Buscar solicitudes vencidas que aún están abiertas
        $solicitudesVencidas = SolicitudCotizacion::with(['cotizacionesProveedores'])
            ->whereHas('estado', function ($query) {
                $query->where('nombre', 'Abierta');
            })
            ->where('fecha_vencimiento', '<', $fechaLimite)
            ->get();

        if ($solicitudesVencidas->isEmpty()) {
            $this->info('✅ No hay solicitudes vencidas para cerrar');
            return self::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$solicitudesVencidas->count()} solicitudes vencidas:");
        
        foreach ($solicitudesVencidas as $solicitud) {
            $this->line("  - #{$solicitud->codigo_solicitud} (vencida el {$solicitud->fecha_vencimiento->format('d/m/Y')})");
        }

        if (!$this->option('force') && !$this->confirm('¿Desea cerrar estas solicitudes?')) {
            $this->info('❌ Operación cancelada');
            return self::FAILURE;
        }

        $cerradas = 0;
        $errores = 0;

        foreach ($solicitudesVencidas as $solicitud) {
            DB::beginTransaction();
            
            try {
                // Cerrar la solicitud
                $solicitud->update([
                    'estado_id' => $estadoCerrada->id,
                    'fecha_cierre' => now(),
                    'motivo_cierre' => 'Cerrada automáticamente por vencimiento',
                ]);

                // Marcar cotizaciones pendientes como "No Respondió"
                $solicitud->cotizacionesProveedores()
                    ->where('estado_envio', 'Pendiente')
                    ->orWhere('estado_envio', 'Enviado')
                    ->update(['estado_envio' => 'No Respondió']);

                DB::commit();
                
                $this->info("✅ Cerrada: #{$solicitud->codigo_solicitud}");
                $cerradas++;

                Log::info("Solicitud de cotización #{$solicitud->codigo_solicitud} cerrada automáticamente por vencimiento");

            } catch (\Exception $e) {
                DB::rollBack();
                
                $this->error("❌ Error al cerrar #{$solicitud->codigo_solicitud}: " . $e->getMessage());
                $errores++;

                Log::error("Error cerrando solicitud #{$solicitud->codigo_solicitud}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->info("  ✅ Cerradas: {$cerradas}");
        
        if ($errores > 0) {
            $this->error("  ❌ Errores: {$errores}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
