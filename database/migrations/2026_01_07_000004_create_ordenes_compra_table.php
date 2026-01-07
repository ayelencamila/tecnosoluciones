<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla ORDENES_COMPRA (CU-22)
 * 
 * Lineamientos aplicados:
 * - Larman: Trazabilidad completa desde oferta hasta orden
 * - Elmasri: Relación 1:1 No Identificada con OFERTAS_COMPRA
 * - Kendall: Número de OC único para auditoría
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            
            // Número correlativo único (Kendall: auditoría)
            $table->string('numero_oc', 20)->unique()
                  ->comment('Ej: OC-0001, OC-0002');
            
            // Relación con PROVEEDORES
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict')
                  ->comment('Proveedor de la orden');
            
            // Relación con OFERTAS_COMPRA (Larman: trazabilidad de decisión)
            // Relación 1:1 No Identificada: cada orden nace de UNA oferta elegida
            $table->foreignId('oferta_id')->unique()
                  ->constrained('ofertas_compra')
                  ->onDelete('restrict')
                  ->comment('Oferta que originó esta orden (trazabilidad 1:1)');
            
            // Relación con USERS (quién generó la orden)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Administrador que generó la OC');
            
            $table->decimal('total', 12, 2)
                  ->comment('Monto total de la orden');
            
            // Kendall: Estados del ciclo de vida de la OC
            $table->enum('estado', [
                'Borrador',
                'Enviada', 
                'Aceptada', 
                'Recibida Parcial', 
                'Recibida Total',
                'Cancelada'
            ])->default('Borrador')
              ->comment('Estado de la orden de compra');
            
            $table->dateTime('fecha_emision')->useCurrent()
                  ->comment('Fecha de generación de la OC');
            
            $table->dateTime('fecha_envio')->nullable()
                  ->comment('Fecha de envío al proveedor');
            
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            
            $table->index('proveedor_id');
            $table->index('oferta_id');
            $table->index('user_id');
            $table->index('estado');
            $table->index('numero_oc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
