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
        Schema::create('cuentas_corriente', function (Blueprint $table) {
            $table->id('cuentaCorrienteID'); // Clave primaria personalizada
            $table->decimal('saldo', 10, 2)->default(0.00); // Saldo actual de la cuenta
            $table->decimal('limiteCredito', 10, 2)->default(0.00); // Límite de crédito otorgado
            $table->integer('diasGracia')->default(0); // Días de gracia para pagos
            $table->unsignedBigInteger('estadoCuentaCorrienteID'); // Clave foránea a estados_cuenta_corriente
            $table->timestamps(); // Columnas created_at y updated_at
            $table->softDeletes(); // Columna deleted_at para soft deletes

            // Definición de la clave foránea
            $table->foreign('estadoCuentaCorrienteID')->references('estadoCuentaCorrienteID')->on('estados_cuenta_corriente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_corriente');
    }
};
