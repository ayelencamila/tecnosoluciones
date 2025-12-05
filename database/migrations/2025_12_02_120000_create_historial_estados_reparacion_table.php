<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla para rastrear cambios de estado en reparaciones
     * Permite calcular días efectivos correctamente pausando según estados
     */
    public function up(): void
    {
        Schema::create('historial_estados_reparacion', function (Blueprint $table) {
            $table->id('historial_id');
            
            // Relación con reparación
            $table->foreignId('reparacion_id')
                  ->constrained('reparaciones', 'reparacionID')
                  ->onDelete('cascade');
            
            // Estados involucrados
            $table->foreignId('estado_anterior_id')
                  ->nullable()
                  ->constrained('estados_reparacion', 'estadoReparacionID')
                  ->onDelete('set null')
                  ->comment('NULL si es el primer estado (ingreso)');
            
            $table->foreignId('estado_nuevo_id')
                  ->constrained('estados_reparacion', 'estadoReparacionID')
                  ->onDelete('cascade');
            
            // Metadatos del cambio
            $table->timestamp('fecha_cambio')->default(DB::raw('CURRENT_TIMESTAMP'));
            
            $table->foreignId('usuario_id')
                  ->nullable()
                  ->constrained('users', 'id')
                  ->onDelete('set null')
                  ->comment('Usuario que realizó el cambio, NULL si fue automático');
            
            $table->text('observaciones')->nullable()->comment('Motivo o notas del cambio de estado');
            
            $table->timestamps();
            
            // Índices para consultas de cálculo de SLA
            $table->index(['reparacion_id', 'fecha_cambio']);
            $table->index('estado_nuevo_id');
            $table->index('fecha_cambio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estados_reparacion');
    }
};
