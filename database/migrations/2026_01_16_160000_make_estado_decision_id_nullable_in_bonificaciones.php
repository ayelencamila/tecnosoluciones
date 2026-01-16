<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Hacer nullable estado_decision_id en bonificaciones_reparacion
 * 
 * Justificación: Cuando se crea una bonificación, está en estado "pendiente"
 * de aprobación del admin. El cliente aún no ha tomado ninguna decisión,
 * por lo que estado_decision_id debe ser nullable hasta que el cliente responda.
 * 
 * Flujo:
 * 1. Técnico responde alerta → Se crea bonificación (estado_decision_id = NULL)
 * 2. Admin aprueba → Se envía link al cliente
 * 3. Cliente decide → Se actualiza estado_decision_id con su decisión
 * 
 * Mantiene 3FN: El campo sigue siendo FK a estados_decision_cliente (tabla paramétrica)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            // Eliminar FK existente
            $table->dropForeign(['estado_decision_id']);
        });

        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            // Modificar columna a nullable
            $table->unsignedBigInteger('estado_decision_id')->nullable()->change();
        });

        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            // Recrear FK con ON DELETE SET NULL
            $table->foreign('estado_decision_id')
                  ->references('estado_id')
                  ->on('estados_decision_cliente')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->dropForeign(['estado_decision_id']);
        });

        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            // Volver a NOT NULL (solo si no hay valores null)
            $table->unsignedBigInteger('estado_decision_id')->nullable(false)->change();
        });

        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->foreign('estado_decision_id')
                  ->references('estado_id')
                  ->on('estados_decision_cliente')
                  ->onDelete('restrict');
        });
    }
};
