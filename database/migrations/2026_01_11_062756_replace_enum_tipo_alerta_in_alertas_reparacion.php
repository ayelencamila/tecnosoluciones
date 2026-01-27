<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alertas_reparacion', function (Blueprint $table) {
            $table->foreignId('tipo_alerta_id')->nullable()->after('tecnicoID')
                ->constrained('tipos_alerta_reparacion', 'tipo_id')->onDelete('restrict');
        });
        DB::statement("UPDATE alertas_reparacion ar JOIN tipos_alerta_reparacion tar ON ar.tipo_alerta = tar.nombre SET ar.tipo_alerta_id = tar.tipo_id");
        Schema::table('alertas_reparacion', function (Blueprint $table) {
            $table->dropColumn(['tipo_alerta', 'respuesta_tecnico']);
            $table->unsignedBigInteger('tipo_alerta_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('alertas_reparacion', function (Blueprint $table) {
            $table->enum('tipo_alerta', ['sla_excedido', 'sin_respuesta', 'cliente_sin_decidir'])->default('sla_excedido');
            $table->json('respuesta_tecnico')->nullable();
        });
        DB::statement("UPDATE alertas_reparacion ar JOIN tipos_alerta_reparacion tar ON ar.tipo_alerta_id = tar.tipo_id SET ar.tipo_alerta = tar.nombre");
        Schema::table('alertas_reparacion', function (Blueprint $table) {
            $table->dropForeign(['tipo_alerta_id']);
            $table->dropColumn('tipo_alerta_id');
        });
    }
};
