<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Cambiar PK compuesta a Surrogate Key en detalle_ordenes_compra
 * 
 * Motivo: Laravel/Eloquent no soporta nativamente PKs compuestas.
 * Solución: Usar id autoincremental + unique constraint (sigue cumpliendo 3FN)
 * 
 * Alineación con: detalle_ventas, detalle_reparaciones (mismo patrón)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: Eliminar la tabla actual (está vacía)
        Schema::dropIfExists('detalle_ordenes_compra');

        // Paso 2: Recrear con surrogate key
        Schema::create('detalle_ordenes_compra', function (Blueprint $table) {
            // Surrogate Key (patrón consistente con detalle_ventas)
            $table->id()->comment('PK autoincremental - Surrogate Key');

            // FK a ORDENES_COMPRA (ON DELETE CASCADE - Entidad Débil)
            $table->foreignId('orden_compra_id')
                  ->constrained('ordenes_compra')
                  ->onDelete('cascade')
                  ->comment('FK a orden de compra padre');

            // FK a PRODUCTOS (ON DELETE RESTRICT)
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('restrict')
                  ->comment('FK a producto');

            // Constraint UNIQUE para garantizar integridad (reemplaza PK compuesta)
            // Un producto solo puede aparecer una vez por orden
            $table->unique(['orden_compra_id', 'producto_id'], 'uq_orden_producto');

            // Control de cantidades (CU-23: Recepción Parcial)
            $table->integer('cantidad_pedida')
                  ->comment('Cantidad solicitada en la OC');

            $table->integer('cantidad_recibida')->default(0)
                  ->comment('Cantidad efectivamente recibida (CU-23)');

            // Precio pactado (snapshot del momento de la orden)
            $table->decimal('precio_unitario', 10, 2)
                  ->comment('Precio unitario acordado con el proveedor');

            $table->timestamps();

            // Índices
            $table->index('orden_compra_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ordenes_compra');

        // Recrear con PK compuesta original (rollback)
        Schema::create('detalle_ordenes_compra', function (Blueprint $table) {
            $table->unsignedBigInteger('orden_compra_id');
            $table->unsignedBigInteger('producto_id');
            $table->primary(['orden_compra_id', 'producto_id'], 'pk_detalle_orden');

            $table->foreign('orden_compra_id', 'fk_detalle_orden_compra')
                  ->references('id')->on('ordenes_compra')
                  ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('producto_id', 'fk_detalle_producto')
                  ->references('id')->on('productos')
                  ->onDelete('restrict')->onUpdate('cascade');

            $table->integer('cantidad_pedida');
            $table->integer('cantidad_recibida')->default(0);
            $table->decimal('precio_unitario', 10, 2);
            $table->timestamps();
        });
    }
};
