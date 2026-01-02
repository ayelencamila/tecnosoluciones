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
            
            // 1. Relaciones Maestras (Marcas y Unidades)
            $table->foreignId('marca_id')
                  ->nullable()
                  ->constrained('marcas') 
                  ->onDelete('restrict'); 

            $table->foreignId('unidad_medida_id')
                  ->constrained('unidades_medida') 
                  ->onDelete('restrict');

            $table->unsignedBigInteger('categoriaProductoID');
            
            $table->foreign('categoriaProductoID')
                  ->references('id') 
                  ->on('categorias_producto')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('estadoProductoID');
            
            $table->foreign('estadoProductoID')
                  ->references('id') 
                  ->on('estados_producto')
                  ->onDelete('restrict');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};