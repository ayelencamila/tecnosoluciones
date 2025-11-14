<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id('detalle_venta_id'); // Tu PK

            // FK a ventas (si se borra la venta, se borra el detalle)
            $table->foreignId('venta_id')->constrained('ventas', 'venta_id')->onDelete('cascade');

            // FK a productos (asumiendo PK 'productoID' en 'productos')
            $table->foreignId('productoID')->constrained('productos');

            // FK a precios_producto (asumiendo PK 'id' en 'precios_producto')
            // Es nullable() por si vendes un "servicio" o algo con precio manual
            $table->foreignId('precio_producto_id')->nullable()->constrained('precios_producto');

            $table->decimal('cantidad', 15, 4);

            // --- Precios "Congelados" ---
            $table->decimal('precio_unitario', 15, 2); // Precio original
            $table->decimal('subtotal', 15, 2); // (cantidad * precio_unitario)

            // --- Descuentos por Item ---
            // Guardamos el total de descuentos aplicados SOLO a este item
            $table->decimal('descuento_item', 15, 2)->default(0);
            // El subtotal real del item
            $table->decimal('subtotal_neto', 15, 2); // (subtotal - descuento_item)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
