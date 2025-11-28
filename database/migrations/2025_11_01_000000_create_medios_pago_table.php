<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('medios_pago', function (Blueprint $table) {
        $table->id('medioPagoID'); // PK personalizada
        $table->string('nombre', 50)->unique(); // Ej: Efectivo, Tarjeta Visa
        $table->decimal('recargo_porcentaje', 5, 2)->default(0); // Ej: 10.00 para 10%
        $table->boolean('activo')->default(true);
        $table->text('instrucciones')->nullable(); // Ej: "Pedir comprobante al cliente"
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medio_pagos');
    }
};
