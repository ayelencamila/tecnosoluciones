<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_venta_imputacion', function (Blueprint $table) {
            $table->id();
            
            // El pago que se está distribuyendo
            $table->foreignId('pago_id')->constrained('pagos', 'pago_id')->onDelete('cascade');
            
            // La venta (deuda) que se está cancelando
            $table->foreignId('venta_id')->constrained('ventas', 'venta_id')->onDelete('restrict');
            
            // Cuánto dinero de este pago va a esta venta
            $table->decimal('monto_imputado', 15, 2);
            
            $table->timestamps();

            // Evitar duplicados: un pago no puede imputar dos veces a la misma venta
            $table->unique(['pago_id', 'venta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_venta_imputacion');
    }
};