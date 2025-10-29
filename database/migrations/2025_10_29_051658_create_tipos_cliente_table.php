<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cliente', function (Blueprint $table) {
            $table->id('tipoClienteID'); // Clave primaria personalizada
            $table->string('nombreTipo', 50)->unique(); // Nombre del tipo de cliente, único
            $table->string('descripcion', 255)->nullable(); // Descripción, puede ser nula
            $table->boolean('activo')->default(true); // Indica si el tipo de cliente está activo
            $table->timestamps(); // Columnas created_at y updated_at
            $table->softDeletes(); // Columna deleted_at para soft deletes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cliente');
    }
};