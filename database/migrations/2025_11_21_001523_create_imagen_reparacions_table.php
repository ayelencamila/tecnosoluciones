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
        // Nombre de tabla en plural correcto
        Schema::create('imagenes_reparacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reparacion_id')
                  ->constrained('reparaciones', 'reparacionID')
                  ->onDelete('cascade');

            // Ruta relativa dentro de storage/app/public
            $table->string('ruta_archivo'); 

            // Clasificación de la foto para orden
            // 'ingreso': Estado inicial (golpes, rayones)
            // 'proceso': Evidencia del trabajo técnico
            // 'salida': Estado final entregado
            $table->enum('etapa', ['ingreso', 'proceso', 'salida'])->default('ingreso');

            // Nombre original del archivo (útil para descargas)
            $table->string('nombre_original')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagenes_reparacion');
    }
};