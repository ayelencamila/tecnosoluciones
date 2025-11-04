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
        Schema::create('localidades', function (Blueprint $table) {
            $table->id('localidadID'); // Clave primaria personalizada
            $table->string('nombre', 100); // Nombre de la localidad
            $table->unsignedBigInteger('provinciaID'); // Clave foránea a provincias
            $table->timestamps(); // Columnas created_at y updated_at
            $table->softDeletes(); // Columna deleted_at para soft deletes

            // Definición de la clave foránea
            $table->foreign('provinciaID')->references('provinciaID')->on('provincias');

            // Asegura que la combinación nombre-provinciaID sea única
            $table->unique(['nombre', 'provinciaID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
