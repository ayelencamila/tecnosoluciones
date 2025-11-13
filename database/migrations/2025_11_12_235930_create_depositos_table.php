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
        Schema::create('depositos', function (Blueprint $table) {
            $table->id('deposito_id'); // PK personalizada
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->string('direccion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('esPrincipal')->default(false); // Solo uno debe ser true
            $table->timestamps();

            // Índice para mejorar búsquedas
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depositos');
    }
};
