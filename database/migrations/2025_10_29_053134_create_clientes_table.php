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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('clienteID'); // Clave primaria personalizada
            $table->string('nombre', 100)->nullable();
            $table->string('apellido', 100)->nullable();
            $table->string('DNI', 20)->unique();
            $table->string('mail', 255)->nullable();
            $table->string('whatsapp', 20);
            $table->string('telefono', 20);

            // Claves foráneas (TODAS unsignedBigInteger)
            $table->unsignedBigInteger('tipoClienteID');
            $table->foreign('tipoClienteID')->references('tipoClienteID')->on('tipos_cliente');

            $table->unsignedBigInteger('estadoClienteID');
            $table->foreign('estadoClienteID')->references('estadoClienteID')->on('estados_cliente');

            $table->unsignedBigInteger('direccionID');
            $table->foreign('direccionID')->references('direccionID')->on('direcciones');

            $table->unsignedBigInteger('cuentaCorrienteID')->nullable(); // Puede ser nulo
            $table->foreign('cuentaCorrienteID')->references('cuentaCorrienteID')->on('cuentas_corriente');

            $table->timestamps(); // Columnas created_at y updated_at
            $table->softDeletes(); // Columna deleted_at para soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
