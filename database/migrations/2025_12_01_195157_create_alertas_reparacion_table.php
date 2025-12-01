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
        Schema::create('alertas_reparacion', function (Blueprint $table) {
            $table->id('alertaReparacionID');
            
            // Relación con reparación
            $table->foreignId('reparacionID')
                  ->constrained('reparaciones', 'reparacionID')
                  ->onDelete('cascade');
            
            // Técnico responsable
            $table->foreignId('tecnicoID')
                  ->nullable()
                  ->constrained('users', 'id')
                  ->onDelete('set null');
            
            // Tipo de alerta
            $table->enum('tipo_alerta', ['sla_excedido', 'sin_respuesta', 'cliente_sin_decidir'])
                  ->default('sla_excedido');
            
            // Información de la demora
            $table->integer('dias_excedidos')->default(0);
            $table->integer('dias_efectivos')->default(0);
            $table->integer('sla_vigente')->default(0);
            
            // Estado de la alerta
            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_lectura')->nullable();
            
            // Respuesta del técnico (JSON)
            $table->json('respuesta_tecnico')->nullable()->comment('Contiene: motivo_id, factible, observaciones');
            
            $table->timestamps();
            
            // Índices para búsquedas comunes
            $table->index(['tecnicoID', 'leida']);
            $table->index('reparacionID');
            $table->index('tipo_alerta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas_reparacion');
    }
};
