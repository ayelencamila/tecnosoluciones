<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega el campo respuesta_tecnico para almacenar la respuesta del técnico a la alerta
     */
    public function up(): void
    {
        Schema::table('alertas_reparacion', function (Blueprint $table) {
            $table->json('respuesta_tecnico')->nullable()->after('sla_vigente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alertas_reparacion', function (Blueprint $table) {
            $table->dropColumn('respuesta_tecnico');
        });
    }
};
