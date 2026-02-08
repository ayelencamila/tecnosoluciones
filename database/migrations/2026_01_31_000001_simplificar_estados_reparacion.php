<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migración para simplificar los estados de reparación
 * 
 * CAMBIOS:
 * - Elimina estados "Diagnóstico" (ID 2) y "Presupuestado" (ID 3)
 * - Re-mapea las reparaciones existentes a los nuevos estados
 * - Actualiza los IDs para la nueva estructura simplificada
 * 
 * NUEVO FLUJO:
 * 1 = Recibido (con presupuesto)
 * 2 = En Reparación
 * 3 = Espera de Repuesto [PAUSA SLA]
 * 4 = Reparado [PAUSA SLA]
 * 5 = Entregado (final)
 * 6 = Anulado (final)
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('=== INICIO: Migración de simplificación de estados de reparación ===');

        // Deshabilitar foreign keys temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::transaction(function () {
            // 1. Re-mapear reparaciones con estados obsoletos ANTES de eliminarlos
            // Diagnóstico (2) → Recibido (1) - Aún no se empezó a reparar
            $afectadasDiagnostico = DB::table('reparaciones')
                ->where('estado_reparacion_id', 2)
                ->update(['estado_reparacion_id' => 1]);
            Log::info("Reparaciones en 'Diagnóstico' remapeadas a 'Recibido': {$afectadasDiagnostico}");

            // Presupuestado (3) → Recibido (1) - El presupuesto ya se da al ingresar
            $afectadasPresupuestado = DB::table('reparaciones')
                ->where('estado_reparacion_id', 3)
                ->update(['estado_reparacion_id' => 1]);
            Log::info("Reparaciones en 'Presupuestado' remapeadas a 'Recibido': {$afectadasPresupuestado}");

            // 2. Actualizar historial de estados (si existe)
            if (DB::getSchemaBuilder()->hasTable('historial_estados_reparacion')) {
                // Diagnóstico → Recibido
                DB::table('historial_estados_reparacion')
                    ->where('estado_anterior_id', 2)
                    ->update(['estado_anterior_id' => 1]);
                DB::table('historial_estados_reparacion')
                    ->where('estado_nuevo_id', 2)
                    ->update(['estado_nuevo_id' => 1]);

                // Presupuestado → Recibido
                DB::table('historial_estados_reparacion')
                    ->where('estado_anterior_id', 3)
                    ->update(['estado_anterior_id' => 1]);
                DB::table('historial_estados_reparacion')
                    ->where('estado_nuevo_id', 3)
                    ->update(['estado_nuevo_id' => 1]);
            }

            // 3. Re-numerar los estados restantes para cerrar gaps
            // Mapa: ID_antiguo → ID_nuevo
            $mapeo = [
                4 => 2, // En Reparación: 4 → 2
                5 => 3, // Espera de Repuesto: 5 → 3
                6 => 4, // Reparado: 6 → 4
                7 => 5, // Entregado: 7 → 5
                8 => 6, // Anulado: 8 → 6
            ];

            // Primero usamos IDs temporales altos (100+) para evitar conflictos de unique
            // (no podemos usar negativos porque la columna es unsigned)
            foreach ($mapeo as $viejo => $nuevo) {
                $temp = 100 + $nuevo;
                DB::table('reparaciones')
                    ->where('estado_reparacion_id', $viejo)
                    ->update(['estado_reparacion_id' => $temp]);
                Log::info("Reparaciones estado {$viejo} → temporal {$temp}");
            }

            // Ahora convertimos los temporales a los definitivos
            foreach ($mapeo as $viejo => $nuevo) {
                $temp = 100 + $nuevo;
                DB::table('reparaciones')
                    ->where('estado_reparacion_id', $temp)
                    ->update(['estado_reparacion_id' => $nuevo]);
                Log::info("Reparaciones temporal {$temp} → definitivo {$nuevo}");
            }

            // 4. Actualizar historial con el mismo proceso
            if (DB::getSchemaBuilder()->hasTable('historial_estados_reparacion')) {
                foreach ($mapeo as $viejo => $nuevo) {
                    $temp = 100 + $nuevo;
                    DB::table('historial_estados_reparacion')
                        ->where('estado_anterior_id', $viejo)
                        ->update(['estado_anterior_id' => $temp]);
                    DB::table('historial_estados_reparacion')
                        ->where('estado_nuevo_id', $viejo)
                        ->update(['estado_nuevo_id' => $temp]);
                }
                foreach ($mapeo as $viejo => $nuevo) {
                    $temp = 100 + $nuevo;
                    DB::table('historial_estados_reparacion')
                        ->where('estado_anterior_id', $temp)
                        ->update(['estado_anterior_id' => $nuevo]);
                    DB::table('historial_estados_reparacion')
                        ->where('estado_nuevo_id', $temp)
                        ->update(['estado_nuevo_id' => $nuevo]);
                }
            }

            // 5. Eliminar estados obsoletos
            DB::table('estados_reparacion')
                ->whereIn('estadoReparacionID', [2, 3]) // Diagnóstico, Presupuestado originales
                ->delete();
            Log::info("Estados 'Diagnóstico' y 'Presupuestado' eliminados");

            // 6. Actualizar la tabla estados_reparacion con nuevos IDs
            // Usamos el mismo proceso de IDs temporales (100+)
            foreach ($mapeo as $viejo => $nuevo) {
                $temp = 100 + $nuevo;
                DB::table('estados_reparacion')
                    ->where('estadoReparacionID', $viejo)
                    ->update(['estadoReparacionID' => $temp]);
            }
            foreach ($mapeo as $viejo => $nuevo) {
                $temp = 100 + $nuevo;
                DB::table('estados_reparacion')
                    ->where('estadoReparacionID', $temp)
                    ->update(['estadoReparacionID' => $nuevo]);
            }

            // 7. Actualizar descripciones
            DB::table('estados_reparacion')
                ->where('estadoReparacionID', 1)
                ->update([
                    'nombreEstado' => 'Recibido',
                    'descripcion' => 'Equipo ingresado con presupuesto. Pendiente de reparación.'
                ]);
            DB::table('estados_reparacion')
                ->where('estadoReparacionID', 2)
                ->update([
                    'nombreEstado' => 'En Reparación',
                    'descripcion' => 'Reparación en curso por el técnico.'
                ]);
            DB::table('estados_reparacion')
                ->where('estadoReparacionID', 3)
                ->update([
                    'nombreEstado' => 'Espera de Repuesto',
                    'descripcion' => 'Pausado aguardando repuesto de proveedor. [Pausa SLA]'
                ]);
            DB::table('estados_reparacion')
                ->where('estadoReparacionID', 4)
                ->update([
                    'nombreEstado' => 'Reparado',
                    'descripcion' => 'Listo para retirar por el cliente. [Pausa SLA]'
                ]);
            DB::table('estados_reparacion')
                ->where('estadoReparacionID', 5)
                ->update([
                    'nombreEstado' => 'Entregado',
                    'descripcion' => 'Finalizado y entregado al cliente.'
                ]);
            DB::table('estados_reparacion')
                ->where('estadoReparacionID', 6)
                ->update([
                    'nombreEstado' => 'Anulado',
                    'descripcion' => 'Cancelado sin reparación.'
                ]);

            Log::info("Estados de reparación actualizados correctamente");

            // 8. Actualizar configuración de estados que pausan SLA
            DB::table('configuracion')
                ->where('clave', 'estados_pausa_sla')
                ->update([
                    'valor' => 'Espera de Repuesto,Reparado',
                    'descripcion' => '[Sistema - No editable] Estados que pausan el conteo de días efectivos para SLA.',
                    'updated_at' => now(),
                ]);
            Log::info("Configuración 'estados_pausa_sla' actualizada");
        });

        // Re-habilitar foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Log::info('=== FIN: Migración de simplificación de estados de reparación ===');
    }

    public function down(): void
    {
        // NOTA: Esta migración es destructiva, no se puede revertir automáticamente
        // Se requeriría restaurar desde backup o re-ejecutar seeders originales
        Log::warning('Reversión de simplificación de estados NO implementada - Requiere restauración manual');
    }
};
