<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estados_decision_cliente', function (Blueprint $table) {
            $table->id('estado_id');
            $table->string('nombre', 50)->comment('pendiente, aceptar, cancelar, continuar');
            $table->string('descripcion', 255)->nullable();
            $table->string('contexto', 50)->comment('bonificacion o reparacion');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Unique compuesto: nombre + contexto
            $table->unique(['contexto', 'nombre'], 'unique_contexto_nombre');
            $table->index(['contexto', 'nombre']);
        });

        // Insertar estados iniciales
        DB::table('estados_decision_cliente')->insert([
            // Para bonificaciones
            ['nombre' => 'pendiente', 'descripcion' => 'Esperando decisión del cliente', 'contexto' => 'bonificacion', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'aceptar', 'descripcion' => 'Cliente acepta bonificación', 'contexto' => 'bonificacion', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'cancelar', 'descripcion' => 'Cliente cancela bonificación', 'contexto' => 'bonificacion', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            // Para reparaciones
            ['nombre' => 'pendiente', 'descripcion' => 'Esperando decisión del cliente', 'contexto' => 'reparacion', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'continuar', 'descripcion' => 'Cliente decide continuar con reparación', 'contexto' => 'reparacion', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'cancelar', 'descripcion' => 'Cliente cancela reparación', 'contexto' => 'reparacion', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_decision_cliente');
    }
};
