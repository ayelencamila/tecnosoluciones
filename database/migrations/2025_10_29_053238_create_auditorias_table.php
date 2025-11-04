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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id('auditoriaID'); // Clave primaria personalizada
            $table->unsignedBigInteger('usuarioID'); // Clave foránea al ID de la tabla 'users'
            $table->string('accion', 255);
            $table->text('detalles')->nullable(); // Detalles de la acción, puede ser nulo
            $table->timestamps(); // Columnas created_at y updated_at

            // Definición de la clave foránea
            $table->foreign('usuarioID')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
