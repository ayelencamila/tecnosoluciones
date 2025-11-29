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
            
            // --- CAMBIO: Relaciones Configurables ---
            $table->foreignId('marca_id')->nullable()->constrained('marcas'); 
            $table->foreignId('unidad_medida_id')->constrained('unidades_medida');
            // --------------------------------------

            // Mantenemos tus nombres existentes para no romper todo el historial
            $table->foreignId('categoriaProductoID')->constrained('categorias_producto');
            $table->foreignId('estadoProductoID')->constrained('estados_producto');

            $table->timestamps();
            
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};