<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('unidadMedida', 20);
            $table->foreignId('categoriaProductoID')->constrained('categorias_producto');
            $table->foreignId('estadoProductoID')->constrained('estados_producto');
            $table->integer('stockActual')->default(0);
            $table->integer('stockMinimo')->default(0);
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index('codigo');
            $table->index('nombre');
            $table->index('categoriaProductoID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
