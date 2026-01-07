<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla COMPROBANTES (CU-32)
 * 
 * Lineamientos aplicados:
 * - Larman: Relación Polimórfica (bajo acoplamiento)
 * - Kendall: Motivo obligatorio para anulaciones
 * - Elmasri: Self-reference para cadena de historial (reemisiones)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id('comprobante_id');
            
            // Relación polimórfica (Larman: BCE - Bajo acoplamiento)
            $table->string('tipo_entidad', 50)
                  ->comment('Ej: App\\Models\\Venta, App\\Models\\Reparacion');
            
            $table->unsignedBigInteger('entidad_id')
                  ->comment('ID de la venta/reparación/orden de compra');
            
            // Relación con USERS (quién emitió)
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Usuario que generó el comprobante');
            
            // Tipo de comprobante según contexto
            $table->enum('tipo_comprobante', [
                'TICKET',
                'FACTURA_A',
                'FACTURA_B',
                'ORDEN_SERVICIO',
                'RECIBO_PAGO',
                'ORDEN_COMPRA'
            ])->comment('Tipo de comprobante emitido');
            
            // Numeración correlativa única (Kendall: auditoría)
            $table->string('numero_correlativo', 20)->unique()
                  ->comment('Ej: V0001-000045, R0001-000012');
            
            $table->dateTime('fecha_emision')->useCurrent()
                  ->comment('Fecha de emisión del comprobante');
            
            $table->string('ruta_archivo', 255)->nullable()
                  ->comment('Path al PDF en storage');
            
            // Kendall: Estados y motivos
            $table->enum('estado', ['EMITIDO', 'ANULADO', 'REEMPLAZADO'])
                  ->default('EMITIDO')
                  ->comment('Estado del comprobante');
            
            $table->text('motivo_estado')->nullable()
                  ->comment('Motivo de anulación/reemplazo (Kendall)');
            
            // Self-reference para reemisiones (Elmasri: trazabilidad histórica)
            $table->unsignedBigInteger('original_id')->nullable()
                  ->comment('ID del comprobante original si es una reemisión');
            
            $table->foreign('original_id')
                  ->references('comprobante_id')
                  ->on('comprobantes')
                  ->onDelete('set null');
            
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index(['tipo_entidad', 'entidad_id']);
            $table->index('usuario_id');
            $table->index('estado');
            $table->index('numero_correlativo');
            $table->index('original_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
