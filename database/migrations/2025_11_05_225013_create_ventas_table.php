<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('venta_id');

            $table->unsignedBigInteger('clienteID');
            $table->foreign('clienteID')->references('clienteID')->on('clientes');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('estado_venta_id')->constrained('estados_venta', 'estadoVentaID');
            
            // Relación con Medio de Pago (Para saber cómo se pagó o se va a pagar)
            $table->foreignId('medio_pago_id')->constrained('medios_pago', 'medioPagoID');
            // -----------------------------------

            $table->string('numero_comprobante', 100)->unique();
            $table->dateTime('fecha_venta');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_descuentos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('motivo_anulacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
