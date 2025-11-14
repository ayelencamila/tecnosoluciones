<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('venta_id'); // Mi PK

            // FK a clientes (cuya PK es 'clienteID')
            $table->unsignedBigInteger('clienteID');
            $table->foreign('clienteID')->references('clienteID')->on('clientes');

            // FK a users (para saber QUÉ VENDEDOR hizo la venta)
            $table->foreignId('user_id')->constrained('users');

            $table->string('numero_comprobante', 100)->unique();
            $table->dateTime('fecha_venta');

            // Totales (estos se calculan y guardan)
            $table->decimal('subtotal', 15, 2)->default(0); // Suma de items ANTES de descuentos
            $table->decimal('total_descuentos', 15, 2)->default(0); // Suma de todos los descuentos (item + global)
            $table->decimal('total', 15, 2)->default(0); // subtotal - total_descuentos

            // Anulación
            $table->boolean('anulada')->default(false);
            $table->text('motivo_anulacion')->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
