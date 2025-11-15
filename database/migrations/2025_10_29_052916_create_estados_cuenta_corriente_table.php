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
        Schema::create('estados_cuenta_corriente', function (Blueprint $table) {
            $table->id('estadoCuentaCorrienteID'); // Clave primaria personalizada
            $table->string('nombreEstado', 50)->unique(); // Nombre del estado de cuenta corriente, único
            $table->string('descripcion', 255)->nullable(); // Descripción, puede ser nula
            $table->timestamps(); // Columnas created_at y updated_at
            $table->softDeletes(); // Columna deleted_at para soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_cuenta_corriente');
    }
};
