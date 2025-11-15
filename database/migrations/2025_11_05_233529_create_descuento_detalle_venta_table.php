<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivote N:M para descuentos POR ITEM
        Schema::create('descuento_detalle_venta', function (Blueprint $table) {

            $table->foreignId('detalle_venta_id')->constrained('detalle_ventas', 'detalle_venta_id')->onDelete('cascade');
            $table->foreignId('descuento_id')->constrained('descuentos', 'descuento_id')->onDelete('cascade');

            $table->primary(['detalle_venta_id', 'descuento_id']);

            // "Congelamos" el monto que se descontó en ESE item
            $table->decimal('monto_aplicado_item', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuento_detalle_venta');
    }
};
