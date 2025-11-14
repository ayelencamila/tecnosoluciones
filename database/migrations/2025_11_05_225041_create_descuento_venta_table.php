<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivote N:M para descuentos GLOBALES (aplicados al TOTAL de la venta)
        Schema::create('descuento_venta', function (Blueprint $table) {

            $table->foreignId('venta_id')->constrained('ventas', 'venta_id')->onDelete('cascade');
            $table->foreignId('descuento_id')->constrained('descuentos', 'descuento_id')->onDelete('cascade');

            $table->primary(['venta_id', 'descuento_id']);

            // "Congelamos" el monto que se descontó en el total
            $table->decimal('monto_aplicado', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuento_venta');
    }
};
