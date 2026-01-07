<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla SOLICITUDES_COTIZACION (CU-20)
 * 
 * Lineamientos aplicados:
 * - Kendall: Control de estados del flujo de cotización
 * - Elmasri: Integridad Referencial con ON DELETE RESTRICT
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_cotizacion', function (Blueprint $table) {
            $table->id();
            
            // Relación con PROVEEDORES (Elmasri: ON DELETE RESTRICT)
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict')
                  ->comment('Proveedor al que se solicita la cotización');
            
            $table->dateTime('fecha_emision')->useCurrent()
                  ->comment('Fecha y hora de envío de la solicitud');
            
            // Kendall: Estados del flujo
            $table->enum('estado', ['Pendiente', 'Respondida', 'Vencida', 'Cancelada'])
                  ->default('Pendiente')
                  ->comment('Estado de la solicitud');
            
            $table->string('hash_whatsapp', 255)->nullable()
                  ->comment('ID de sesión de WhatsApp para rastreo CU-20');
            
            $table->text('detalle_productos')->nullable()
                  ->comment('JSON con productos solicitados');
            
            $table->timestamps();
            
            $table->index('proveedor_id');
            $table->index('estado');
            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_cotizacion');
    }
};
