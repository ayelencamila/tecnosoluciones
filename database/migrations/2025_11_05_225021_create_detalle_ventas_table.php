<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id('detalle_venta_id');

            // Relación de Composición (Venta -> Detalles): CASCADE.
            $table->foreignId('venta_id')
                  ->constrained('ventas', 'venta_id')
                  ->onDelete('cascade');

            // Relación de Catálogo (Producto): RESTRICT
            // No permitir borrar un producto que figura en facturas históricas.
            $table->foreignId('producto_id')
                  ->constrained('productos') 
                  ->onDelete('restrict');

            // Precio Lista Referencia: RESTRICT
            $table->foreignId('precio_producto_id')
                  ->nullable()
                  ->constrained('precios_producto')
                  ->onDelete('restrict');

            $table->decimal('cantidad', 15, 4);

            // --- Precios "Congelados" (Snapshot) ---
            $table->decimal('precio_unitario', 15, 2); 
            $table->decimal('subtotal', 15, 2); 

            // --- Descuentos por Item ---
            $table->decimal('descuento_item', 15, 2)->default(0);
            $table->decimal('subtotal_neto', 15, 2); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
