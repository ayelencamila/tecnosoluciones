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
        Schema::create('stock', function (Blueprint $table) {
            $table->id('stock_id'); // PK personalizada

            // FK a productos
            $table->foreignId('productoID')->constrained('productos', 'id')->onDelete('cascade');

            // FK a depositos
            $table->foreignId('deposito_id')->constrained('depositos', 'deposito_id')->onDelete('restrict');

            // Cantidad disponible en este depósito
            $table->integer('cantidad_disponible')->default(0);

            // Stock mínimo para alertas
            $table->integer('stock_minimo')->default(0);

            $table->timestamps();

            // UNIQUE compuesto: Un producto solo tiene 1 registro por depósito
            $table->unique(['productoID', 'deposito_id'], 'producto_deposito_unico');

            // Índices para mejorar consultas
            $table->index('productoID');
            $table->index('deposito_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
