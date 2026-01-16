<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla RESPUESTAS_COTIZACION (CU-20)
 * 
 * Almacena los precios y disponibilidad que cada proveedor responde
 * para cada producto de la solicitud de cotización.
 * 
 * Lineamientos aplicados:
 * - Elmasri 1FN: Un registro por cada producto-proveedor-solicitud
 * - Elmasri 2FN: No hay dependencias parciales
 * - Elmasri 3FN: No hay dependencias transitivas
 * - Kendall: Trazabilidad completa de ofertas
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas_cotizacion', function (Blueprint $table) {
            $table->id()->comment('PK - ID de la respuesta');
            
            // FK a cotizaciones_proveedores
            $table->foreignId('cotizacion_proveedor_id')
                  ->constrained('cotizaciones_proveedores')
                  ->onDelete('cascade')
                  ->comment('FK a la cotización del proveedor');
            
            // FK a productos
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('restrict')
                  ->comment('FK al producto cotizado');
            
            // Datos de la cotización
            $table->decimal('precio_unitario', 12, 2)
                  ->comment('Precio unitario ofrecido por el proveedor');
            
            $table->integer('cantidad_disponible')
                  ->comment('Cantidad que el proveedor puede proveer');
            
            $table->integer('plazo_entrega_dias')
                  ->comment('Días hábiles estimados para entrega');
            
            $table->text('observaciones')->nullable()
                  ->comment('Notas del proveedor sobre este producto');
            
            $table->timestamps();
            
            // Índices
            $table->index('cotizacion_proveedor_id');
            $table->index('producto_id');
            
            // Un proveedor solo puede cotizar una vez cada producto por solicitud
            $table->unique(
                ['cotizacion_proveedor_id', 'producto_id'], 
                'unique_cotizacion_producto'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_cotizacion');
    }
};
