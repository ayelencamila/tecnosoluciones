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
        Schema::create('bonificaciones_reparacion', function (Blueprint $table) {
            $table->id('bonificacionID');
            
            // Relación con reparación
            $table->foreignId('reparacionID')
                  ->constrained('reparaciones', 'reparacionID')
                  ->onDelete('cascade');
            
            // Cálculos de bonificación
            $table->decimal('porcentaje_sugerido', 5, 2)->comment('% calculado por el sistema');
            $table->decimal('porcentaje_aprobado', 5, 2)->nullable()->comment('% finalmente aprobado');
            $table->decimal('monto_original', 10, 2);
            $table->decimal('monto_bonificado', 10, 2)->nullable();
            
            // Motivo de la demora asociado
            $table->foreignId('motivoDemoraID')
                  ->nullable()
                  ->constrained('motivos_demora_reparacion', 'motivoDemoraID')
                  ->onDelete('set null');
            
            // Flujo de aprobación
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->foreignId('aprobada_por')
                  ->nullable()
                  ->constrained('users', 'id')
                  ->onDelete('set null');
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('observaciones_aprobacion')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('reparacionID');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonificaciones_reparacion');
    }
};
