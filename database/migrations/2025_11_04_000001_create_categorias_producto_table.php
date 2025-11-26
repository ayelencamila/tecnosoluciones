<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('categorias_producto', function (Blueprint $table) {
            $table->id();
            // CAMBIO AQUÍ: Usamos string para permitir cualquier nombre nuevo en el futuro
            $table->string('nombre')->unique(); 
            $table->text('descripcion')->nullable();
            // ¡Perfecto que agregaste el campo activo!
            $table->boolean('activo')->default(true); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_producto');
    }
};
