<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permitir recepciones de mercadería sin orden de compra
 * 
 * Caso de uso: Compras directas, ajustes de inventario, 
 * reposición de emergencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recepciones_mercaderia', function (Blueprint $table) {
            // Agregar proveedor para recepciones sin OC
            $table->foreignId('proveedor_id')->nullable()->after('orden_compra_id')
                  ->constrained('proveedores')->nullOnDelete();
            
            // Tipo de recepción extendido
            $table->string('origen', 20)->default('orden_compra')->after('tipo')
                  ->comment('orden_compra, compra_directa, ajuste_inventario');
        });

        // Permitir detalle_orden_id nullable y agregar producto_id directo
        Schema::table('detalle_recepciones', function (Blueprint $table) {
            // Agregar producto_id para recepciones directas (sin OC)
            $table->foreignId('producto_id')->nullable()->after('detalle_orden_id')
                  ->constrained('productos')->nullOnDelete();
            
            // Agregar precio_unitario para recepciones sin OC
            $table->decimal('precio_unitario', 12, 2)->nullable()->after('cantidad_recibida');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_recepciones', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn(['producto_id', 'precio_unitario']);
        });

        Schema::table('recepciones_mercaderia', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn(['proveedor_id', 'origen']);
        });
    }
};
