<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imagenes_reparacion', function (Blueprint $table) {
            $table->foreignId('etapa_id')->nullable()->after('reparacion_id')
                ->constrained('etapas_imagen_reparacion', 'etapa_id')->onDelete('restrict');
        });
        DB::statement("UPDATE imagenes_reparacion ir JOIN etapas_imagen_reparacion eir ON ir.etapa = eir.nombre SET ir.etapa_id = eir.etapa_id");
        Schema::table('imagenes_reparacion', function (Blueprint $table) {
            $table->dropColumn('etapa');
            $table->unsignedBigInteger('etapa_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('imagenes_reparacion', function (Blueprint $table) {
            $table->enum('etapa', ['ingreso', 'proceso', 'salida'])->default('ingreso');
        });
        DB::statement("UPDATE imagenes_reparacion ir JOIN etapas_imagen_reparacion eir ON ir.etapa_id = eir.etapa_id SET ir.etapa = eir.nombre");
        Schema::table('imagenes_reparacion', function (Blueprint $table) {
            $table->dropForeign(['etapa_id']);
            $table->dropColumn('etapa_id');
        });
    }
};
