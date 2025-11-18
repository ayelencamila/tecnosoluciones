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
        // Este bloque SOLO CREA la tabla
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('unidadMedida', 20);
            $table->foreignId('categoriaProductoID')->constrained('categorias_producto');
            $table->foreignId('estadoProductoID')->constrained('estados_producto');
            
            // ¡Ni 'stock' ni 'proveedor' van aquí!
            
            $table->timestamps();

            // Índices
            $table->index('codigo');
            $table->index('nombre');
            $table->index('categoriaProductoID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};