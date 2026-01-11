<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Tabla ESTADOS_OFERTA (Paramétrica)
 * 
 * Lineamientos aplicados:
 * - Elmasri: Tabla paramétrica para evitar hardcodeo
 * - Kendall: Estados del flujo de evaluación de ofertas
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_oferta', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique()
                  ->comment('Nombre del estado: Pendiente, Pre-aprobada, Elegida, Procesada, Rechazada');
            $table->text('descripcion')->nullable()
                  ->comment('Descripción detallada del estado');
            $table->boolean('activo')->default(true)
                  ->comment('Si el estado está disponible para uso');
            $table->integer('orden')->default(0)
                  ->comment('Orden de visualización');
            $table->timestamps();
        });

        // Insertar estados iniciales
        DB::table('estados_oferta')->insert([
            ['nombre' => 'Pendiente', 'descripcion' => 'Oferta registrada, pendiente de evaluación', 'activo' => true, 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Pre-aprobada', 'descripcion' => 'Oferta revisada y lista para consideración', 'activo' => true, 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Elegida', 'descripcion' => 'Oferta seleccionada para generar orden de compra', 'activo' => true, 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Procesada', 'descripcion' => 'Oferta ya convertida en orden de compra', 'activo' => true, 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Rechazada', 'descripcion' => 'Oferta descartada por no cumplir requisitos', 'activo' => true, 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_oferta');
    }
};
