<?php

namespace Database\Seeders;

use App\Models\Reparacion;
use App\Models\HistorialEstadoReparacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Seeder para migrar reparaciones existentes al nuevo sistema de historial
 * 
 * IMPORTANTE: Ejecutar solo UNA VEZ después de aplicar la migración
 * Este seeder crea el estado inicial en el historial para reparaciones
 * que ya existían antes de implementar el sistema de seguimiento
 */
class MigrarHistorialEstadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->info('   MIGRACIÓN DE HISTORIAL DE ESTADOS - INICIO     ');
        $this->command->info('════════════════════════════════════════════════════');
        $this->command->newLine();

        DB::beginTransaction();

        try {
            // Obtener todas las reparaciones que no tienen historial
            $reparaciones = Reparacion::whereDoesntHave('historialEstados')->get();

            $this->command->info("📊 Reparaciones a migrar: {$reparaciones->count()}");
            $this->command->newLine();

            $migradas = 0;
            $errores = 0;

            foreach ($reparaciones as $reparacion) {
                try {
                    // Crear registro inicial de historial
                    HistorialEstadoReparacion::create([
                        'reparacion_id' => $reparacion->reparacionID,
                        'estado_anterior_id' => null,
                        'estado_nuevo_id' => $reparacion->estado_reparacion_id,
                        'fecha_cambio' => $reparacion->fecha_ingreso ?? $reparacion->created_at,
                        'usuario_id' => null, // Sistema automático
                        'observaciones' => 'Estado inicial - migrado automáticamente desde reparaciones existentes',
                    ]);

                    $migradas++;

                    if ($migradas % 50 === 0) {
                        $this->command->info("   Progreso: {$migradas} / {$reparaciones->count()}");
                    }

                } catch (\Exception $e) {
                    $errores++;
                    Log::error('Error al migrar historial de reparación', [
                        'reparacion_id' => $reparacion->reparacionID,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $this->command->newLine();
            $this->command->info('════════════════════════════════════════════════════');
            $this->command->info('   MIGRACIÓN COMPLETADA                            ');
            $this->command->info('════════════════════════════════════════════════════');
            $this->command->info("✅ Reparaciones migradas: {$migradas}");
            
            if ($errores > 0) {
                $this->command->warn("⚠️  Errores encontrados: {$errores}");
            }

            $this->command->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error crítico durante la migración: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }
}
