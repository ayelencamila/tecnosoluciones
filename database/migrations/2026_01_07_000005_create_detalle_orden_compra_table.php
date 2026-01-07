<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla DETALLE_ORDEN_COMPRA (CU-23)
 * 
 * Lineamientos aplicados:
 * - Elmasri: Entidad Débil (relación 1:N Identificada)
 * - CU-23: Soporte para Recepción Parcial
 * - Elmasri: PK compuesta (orden_compra_id, producto_id)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_orden_compra', function (Blueprint $table) {
            // Elmasri: Entidad Débil con PK compuesta (orden_compra_id, producto_id)
            // Relación 1:N Identificada
            $table->unsignedBigInteger('orden_compra_id')
                  ->comment('Parte de la PK compuesta - Orden de compra');
            
            $table->unsignedBigInteger('producto_id')
                  ->comment('Parte de la PK compuesta - Producto');
            
            // PK Compuesta según Elmasri (Entidad Débil)
            $table->primary(['orden_compra_id', 'producto_id']);
            
            // Relación con ORDENES_COMPRA (ON DELETE CASCADE)
            $table->foreign('orden_compra_id')
                  ->references('id')
                  ->on('ordenes_compra')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            // Relación con PRODUCTOS (ON DELETE RESTRICT)
            $table->foreign('producto_id')
                  ->references('id')
                  ->on('productos')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            
            // Control de cantidades (CU-23: Recepción Parcial)
            $table->integer('cantidad_pedida')
                  ->comment('Cantidad solicitada en la OC');
            
            $table->integer('cantidad_recibida')->default(0)
                  ->comment('Cantidad efectivamente recibida');
            
            $table->decimal('precio_unitario_pactado', 10, 2)
                  ->comment('Precio unitario acordado con el proveedor');
            
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index('orden_compra_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_orden_compra');
    }
};
