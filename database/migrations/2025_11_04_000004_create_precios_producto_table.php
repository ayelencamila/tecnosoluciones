<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precios_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('productoID')->constrained('productos')->onDelete('cascade');
            $table->foreignId('tipoClienteID')->constrained('tipos_cliente', 'tipoClienteID');
            $table->decimal('precio', 10, 2);
            $table->date('fechaDesde');
            $table->date('fechaHasta')->nullable();
            $table->timestamps();
            
            // Índice único compuesto para evitar duplicados
            $table->unique(['productoID', 'tipoClienteID', 'fechaDesde'], 'precio_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_producto');
    }
};
