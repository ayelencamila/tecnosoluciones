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
        Schema::create('provincias', function (Blueprint $table) {
            $table->id('provinciaID'); // Clave primaria personalizada
            $table->string('nombre', 100)->unique(); // Nombre de la provincia, único
            $table->timestamps(); // Columnas created_at y updated_at
            $table->softDeletes(); // Columna deleted_at para soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provincias');
    }
};
