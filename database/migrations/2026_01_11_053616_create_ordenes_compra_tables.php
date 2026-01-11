<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas ORDENES_COMPRA (CU-22 y CU-23)
 * 
 * Lineamientos aplicados:
 * - Elmasri 1FN: Detalle de productos en tabla separada
 * - Elmasri 2FN: PK compuesta en detalle (orden_compra_id, producto_id) - Entidad Débil
 * - Elmasri 3FN: Estado referencia tabla paramétrica
 * - Larman: Trazabilidad completa (de oferta a orden)
 * - CU-23: Soporte para recepción parcial de productos
 * - Consistencia: Nomenclatura alineada con detalle_ventas, detalle_reparacions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabla: ORDENES_COMPRA (Cabecera/Entidad Fuerte)
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id()->comment('PK - ID único de la orden de compra');
            
            // Número correlativo único (Kendall: auditoría y trazabilidad)
            $table->string('numero_oc', 30)->unique()
                  ->comment('Número único de OC: OC-YYYYMMDD-XXX');
            
            // Relación con PROVEEDORES
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict')
                  ->comment('FK a proveedor de la orden');
            
            // Relación con OFERTAS_COMPRA (Trazabilidad: cada OC nace de una oferta)
            // Relación 1:1 No Identificada según Elmasri
            $table->foreignId('oferta_id')->unique()
                  ->constrained('ofertas_compra')
                  ->onDelete('restrict')
                  ->comment('FK a oferta que originó esta OC (trazabilidad 1:1)');
            
            // Auditoría: quién generó la orden
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Usuario que generó la orden de compra');
            
            // Fechas del proceso
            $table->dateTime('fecha_emision')
                  ->comment('Fecha y hora de creación de la OC');
            $table->dateTime('fecha_envio')->nullable()
                  ->comment('Fecha y hora de envío al proveedor');
            $table->dateTime('fecha_confirmacion')->nullable()
                  ->comment('Fecha en que el proveedor confirmó la orden');
            
            // Relación con ESTADOS_ORDEN_COMPRA (3FN: sin hardcodeo)
            $table->foreignId('estado_id')
                  ->constrained('estados_orden_compra')
                  ->onDelete('restrict')
                  ->comment('Estado de la orden (FK a tabla paramétrica)');
            
            // Total de la orden
            $table->decimal('total_final', 12, 2)
                  ->comment('Monto total de la orden de compra');
            
            // Observaciones y motivos
            $table->text('observaciones')->nullable()
                  ->comment('Motivo de generación y notas (CU-22 Paso 3)');
            
            $table->timestamps();
            
            // Índices
            $table->index('proveedor_id');
            $table->index('oferta_id');
            $table->index('user_id');
            $table->index('estado_id');
            $table->index('numero_oc');
            $table->index('fecha_emision');
        });

        // Tabla: DETALLE_ORDENES_COMPRA (Entidad Débil - Relación 1:N Identificada)
        // Consistencia de nomenclatura: plural en tabla padre (como detalle_ventas, detalle_reparacions)
        Schema::create('detalle_ordenes_compra', function (Blueprint $table) {
            // Elmasri: PK Compuesta para Entidad Débil (orden_compra_id, producto_id)
            $table->unsignedBigInteger('orden_compra_id')
                  ->comment('Parte 1 de PK compuesta - FK a orden de compra');
            
            $table->unsignedBigInteger('producto_id')
                  ->comment('Parte 2 de PK compuesta - FK a producto');
            
            // Definir PK compuesta según Elmasri (Entidad Débil)
            $table->primary(['orden_compra_id', 'producto_id'], 'pk_detalle_orden');
            
            // FK a ORDENES_COMPRA (ON DELETE CASCADE - Entidad Débil)
            $table->foreign('orden_compra_id', 'fk_detalle_orden_compra')
                  ->references('id')
                  ->on('ordenes_compra')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            // FK a PRODUCTOS (ON DELETE RESTRICT - No se puede borrar producto con órdenes)
            $table->foreign('producto_id', 'fk_detalle_producto')
                  ->references('id')
                  ->on('productos')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            
            // Control de cantidades (CU-23: Recepción Parcial)
            $table->integer('cantidad_pedida')
                  ->comment('Cantidad solicitada en la OC');
            
            $table->integer('cantidad_recibida')->default(0)
                  ->comment('Cantidad efectivamente recibida (actualizable - CU-23)');
            
            // Precio pactado (snapshot del momento de la orden)
            $table->decimal('precio_unitario', 10, 2)
                  ->comment('Precio unitario acordado con el proveedor');
            
            // 1FN: NO guardamos subtotal (es calculable: cantidad * precio_unitario)
            
            $table->timestamps();
            
            // Índices para consultas
            $table->index('orden_compra_id', 'idx_detalle_orden');
            $table->index('producto_id', 'idx_detalle_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ordenes_compra');
        Schema::dropIfExists('ordenes_compra');
    }
};