<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->integer('dias_excedidos')->nullable()->after('monto_bonificado');
            $table->text('justificacion_tecnico')->nullable()->after('dias_excedidos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->dropColumn(['dias_excedidos', 'justificacion_tecnico']);
        });
    }
};
