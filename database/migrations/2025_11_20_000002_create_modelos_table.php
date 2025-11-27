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
        Schema::create('modelos', function (Blueprint $table) {
            $table->id();
            
            // Relación con Marca (Si se borra la marca, se borran sus modelos)
            $table->foreignId('marca_id')->constrained('marcas')->onDelete('cascade');
            
            // Nombre del modelo
            $table->string('nombre', 50); // Ej: Galaxy S20
            
            // Estado
            $table->boolean('activo')->default(true);
            
            // Evitar duplicados: No puede haber dos "S20" dentro de la misma marca
            $table->unique(['marca_id', 'nombre']); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelos');
    }
};
