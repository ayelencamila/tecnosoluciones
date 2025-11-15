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
            $table->unsignedBigInteger('usuarioID')->nullable(); // Clave foránea al ID de la tabla 'users' (nullable para acciones del sistema)
            $table->string('accion', 255);
            $table->string('tabla_afectada', 100)->nullable(); // Tabla que fue modificada
            $table->unsignedBigInteger('registro_id')->nullable(); // ID del registro afectado
            $table->json('datos_anteriores')->nullable(); // Datos antes del cambio
            $table->json('datos_nuevos')->nullable(); // Datos después del cambio
            $table->text('motivo')->nullable(); // Motivo de la operación
            $table->text('detalles')->nullable(); // Detalles adicionales de la acción
            $table->timestamps(); // Columnas created_at y updated_at

            // Definición de la clave foránea
            $table->foreign('usuarioID')->references('id')->on('users')->onDelete('set null');
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
