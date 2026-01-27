<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->foreignId('estado_decision_id')->nullable()->after('decision_cliente')->constrained('estados_decision_cliente', 'estado_id')->onDelete('restrict');
        });
        DB::statement("UPDATE reparaciones r JOIN estados_decision_cliente edc ON r.decision_cliente = edc.nombre AND edc.contexto = 'reparacion' SET r.estado_decision_id = edc.estado_id WHERE r.decision_cliente IS NOT NULL");
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropColumn('decision_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->enum('decision_cliente', ['continuar', 'cancelar', 'pendiente'])->nullable();
        });
        DB::statement("UPDATE reparaciones r JOIN estados_decision_cliente edc ON r.estado_decision_id = edc.estado_id SET r.decision_cliente = edc.nombre");
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropForeign(['estado_decision_id']);
            $table->dropColumn('estado_decision_id');
        });
    }
};
