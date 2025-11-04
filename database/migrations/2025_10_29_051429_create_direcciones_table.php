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
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id('direccionID'); // Clave primaria personalizada
            $table->string('calle', 255);
            $table->string('altura', 10);
            $table->string('pisoDepto', 20)->nullable(); // Puede ser nulo
            $table->string('barrio', 255)->nullable(); // Puede ser nulo
            $table->string('codigoPostal', 10);
            $table->unsignedBigInteger('localidadID'); // Clave foránea a localidades
            $table->timestamps(); // Columnas created_at y updated_at
            $table->softDeletes(); // Columna deleted_at para soft deletes

            // Definición de la clave foránea
            $table->foreign('localidadID')->references('localidadID')->on('localidades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};