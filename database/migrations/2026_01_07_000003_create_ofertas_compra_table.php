<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla OFERTAS_COMPRA (CU-21)
 * 
 * Lineamientos aplicados:
 * - Larman: Trazabilidad total de decisiones
 * - Elmasri: Relación 1:N con SOLICITUDES_COTIZACION
 * - Kendall: Estados para control del flujo
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas_compra', function (Blueprint $table) {
            $table->id();
            
            // Relación con PROVEEDORES
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict')
                  ->comment('Proveedor que presenta la oferta');
            
            // Relación con SOLICITUDES_COTIZACION (Nullable: puede ser oferta espontánea)
            $table->foreignId('solicitud_id')->nullable()
                  ->constrained('solicitudes_cotizacion')
                  ->onDelete('cascade')
                  ->comment('Solicitud que originó esta oferta (nullable)');
            
            $table->decimal('precio_total', 12, 2)
                  ->comment('Monto total de la oferta');
            
            $table->integer('plazo_entrega_real')->nullable()
                  ->comment('Días de entrega prometidos por el proveedor');
            
            // Kendall: Estados del flujo de selección
            $table->enum('estado', ['Pre-aprobada', 'Elegida', 'Procesada', 'Rechazada'])
                  ->default('Pre-aprobada')
                  ->comment('Estado de la oferta en el proceso de compra');
            
            $table->string('ruta_adjunto', 255)->nullable()
                  ->comment('Link al PDF o imagen de la cotización');
            
            $table->text('observaciones')->nullable()
                  ->comment('Notas del administrador sobre la oferta');
            
            $table->timestamps();
            
            $table->index('proveedor_id');
            $table->index('solicitud_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas_compra');
    }
};
