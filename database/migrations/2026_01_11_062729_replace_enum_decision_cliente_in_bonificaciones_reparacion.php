<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Reemplaza ENUM 'decision_cliente' con FK a estados_decision_cliente
 * Cumple con Elmasri: No hardcodeo, datos atómicos en tabla paramétrica
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar nueva columna con FK
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->foreignId('estado_decision_id')
                ->nullable()
                ->after('observaciones_aprobacion')
                ->constrained('estados_decision_cliente', 'estado_id')
                ->onDelete('restrict')
                ->comment('FK a estados_decision_cliente (contexto: bonificacion)');
        });

        // 2. Migrar datos existentes (ENUM → FK)
        $mapeo = [
            'pendiente' => DB::table('estados_decision_cliente')->where('nombre', 'pendiente')->where('contexto', 'bonificacion')->value('estado_id'),
            'aceptar' => DB::table('estados_decision_cliente')->where('nombre', 'aceptar')->where('contexto', 'bonificacion')->value('estado_id'),
            'cancelar' => DB::table('estados_decision_cliente')->where('nombre', 'cancelar')->where('contexto', 'bonificacion')->value('estado_id'),
        ];

        foreach ($mapeo as $valorEnum => $estadoId) {
            DB::table('bonificaciones_reparacion')
                ->where('decision_cliente', $valorEnum)
                ->update(['estado_decision_id' => $estadoId]);
        }

        // 3. Eliminar columna ENUM antigua
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->dropColumn('decision_cliente');
        });

        // 4. Hacer NOT NULL ahora que tiene datos
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->unsignedBigInteger('estado_decision_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->enum('decision_cliente', ['pendiente', 'aceptar', 'cancelar'])
                ->default('pendiente')
                ->after('observaciones_aprobacion');
        });

        // Migrar datos de vuelta
        DB::statement("
            UPDATE bonificaciones_reparacion br
            JOIN estados_decision_cliente edc ON br.estado_decision_id = edc.estado_id
            SET br.decision_cliente = edc.nombre
        ");

        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->dropForeign(['estado_decision_id']);
            $table->dropColumn('estado_decision_id');
        });
    }
};
