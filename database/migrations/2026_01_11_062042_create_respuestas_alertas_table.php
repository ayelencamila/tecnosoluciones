<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Normaliza el campo JSON 'respuesta_tecnico' de alertas_reparacion
     * para cumplir con 1FN de Elmasri (datos atómicos, no estructuras anidadas)
     */
    public function up(): void
    {
        Schema::create('respuestas_alertas', function (Blueprint $table) {
            $table->id('respuesta_id');
            $table->unsignedBigInteger('alerta_reparacion_id')->comment('FK a alertas_reparacion');
            $table->unsignedBigInteger('motivo_demora_id')->comment('FK a motivos_demora_reparacion');
            $table->boolean('factible')->comment('Si la reparación es factible');
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_respuesta')->useCurrent();
            $table->timestamps();

            // FK con integridad referencial
            $table->foreign('alerta_reparacion_id')
                ->references('alertaReparacionID')
                ->on('alertas_reparacion')
                ->onDelete('cascade');
            
            $table->foreign('motivo_demora_id')
                ->references('motivoDemoraID')
                ->on('motivos_demora_reparacion')
                ->onDelete('restrict');

            // Índices
            $table->index('alerta_reparacion_id');
            $table->index('motivo_demora_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_alertas');
    }
};
