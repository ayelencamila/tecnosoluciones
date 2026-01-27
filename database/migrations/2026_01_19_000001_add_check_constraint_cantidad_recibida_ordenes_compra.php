<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Constraint CHECK para validar cantidad_recibida (Elmasri)
 * 
 * Implementa integridad referencial a nivel de base de datos para garantizar que
 * cantidad_recibida nunca exceda cantidad_pedida en detalle_ordenes_compra.
 * 
 * Lineamientos aplicados:
 * - Elmasri: Integridad de dominio con CHECK constraint
 * - Sommerville: Validación en múltiples capas (Request + DB)
 * - Kendall: Prevención de errores en recepción de mercadería (CU-23)
 * 
 * Excepciones del caso de uso implementadas:
 * - CU-23 Excepción 5a: "Cantidad recibida excede la pendiente"
 * 
 * Nota: MySQL no soporta CHECK constraints hasta versión 8.0.16
 * Esta migración verifica la versión y usa TRIGGER como fallback en versiones anteriores
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL 8.0.16+ soporta CHECK constraints nativamente
            $version = DB::select('SELECT VERSION() as version')[0]->version;
            $versionNumero = (float) $version;
            
            if ($versionNumero >= 8.0) {
                // Usar CHECK constraint nativo (recomendado)
                DB::statement('
                    ALTER TABLE detalle_ordenes_compra 
                    ADD CONSTRAINT chk_cantidad_recibida_valida 
                    CHECK (cantidad_recibida >= 0 AND cantidad_recibida <= cantidad_pedida)
                ');
            } else {
                // Fallback: Trigger para MySQL < 8.0.16
                DB::unprepared('
                    CREATE TRIGGER trg_validar_cantidad_recibida_insert
                    BEFORE INSERT ON detalle_ordenes_compra
                    FOR EACH ROW
                    BEGIN
                        IF NEW.cantidad_recibida < 0 OR NEW.cantidad_recibida > NEW.cantidad_pedida THEN
                            SIGNAL SQLSTATE "45000"
                            SET MESSAGE_TEXT = "La cantidad recibida no puede ser negativa ni exceder la cantidad pedida";
                        END IF;
                    END
                ');

                DB::unprepared('
                    CREATE TRIGGER trg_validar_cantidad_recibida_update
                    BEFORE UPDATE ON detalle_ordenes_compra
                    FOR EACH ROW
                    BEGIN
                        IF NEW.cantidad_recibida < 0 OR NEW.cantidad_recibida > NEW.cantidad_pedida THEN
                            SIGNAL SQLSTATE "45000"
                            SET MESSAGE_TEXT = "La cantidad recibida no puede ser negativa ni exceder la cantidad pedida";
                        END IF;
                    END
                ');
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL soporta CHECK constraints nativamente
            DB::statement('
                ALTER TABLE detalle_ordenes_compra 
                ADD CONSTRAINT chk_cantidad_recibida_valida 
                CHECK (cantidad_recibida >= 0 AND cantidad_recibida <= cantidad_pedida)
            ');
        } elseif ($driver === 'sqlite') {
            // SQLite: Recrear tabla con CHECK constraint
            // Nota: SQLite no permite ALTER TABLE ADD CONSTRAINT
            Schema::table('detalle_ordenes_compra', function (Blueprint $table) {
                // En SQLite, el CHECK debe definirse en la creación original de la tabla
                // Esta migración documenta la intención, pero requeriría recrear la tabla
                // Para testing con SQLite, usar validación en capa de aplicación
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            $version = DB::select('SELECT VERSION() as version')[0]->version;
            $versionNumero = (float) $version;
            
            if ($versionNumero >= 8.0) {
                DB::statement('ALTER TABLE detalle_ordenes_compra DROP CHECK chk_cantidad_recibida_valida');
            } else {
                DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_cantidad_recibida_insert');
                DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_cantidad_recibida_update');
            }
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE detalle_ordenes_compra DROP CONSTRAINT chk_cantidad_recibida_valida');
        }
    }
};
