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
            $table->id('clienteID'); // Clave primaria subrogada 
            
            // Atributos obligatorios (Integridad de Dominio - RF1)
            $table->string('nombre', 100); 
            $table->string('apellido', 100);
            $table->string('DNI', 20)->unique(); 
            
            // Atributos opcionales (Regla de negocio específica definida basada en requisitos del cliente)
            $table->string('mail', 255)->nullable();
            $table->string('whatsapp', 20)->nullable(); 
            $table->string('telefono', 20)->nullable(); 

            // Claves foráneas
            $table->unsignedBigInteger('tipoClienteID');
            $table->foreign('tipoClienteID')->references('tipoClienteID')->on('tipos_cliente');

            $table->unsignedBigInteger('estadoClienteID');
            $table->foreign('estadoClienteID')->references('estadoClienteID')->on('estados_cliente');

            $table->unsignedBigInteger('direccionID')->nullable();
            $table->foreign('direccionID')->references('direccionID')->on('direcciones');

            $table->unsignedBigInteger('cuentaCorrienteID')->nullable();
            $table->foreign('cuentaCorrienteID')->references('cuentaCorrienteID')->on('cuentas_corriente');

            $table->timestamps();
            $table->softDeletes();
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