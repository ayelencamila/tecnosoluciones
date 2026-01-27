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
        Schema::create('etapas_imagen_reparacion', function (Blueprint $table) {
            $table->id('etapa_id');
            $table->string('nombre', 50)->unique()->comment('ingreso, proceso, salida');
            $table->string('descripcion', 255)->nullable();
            $table->integer('orden')->comment('Orden cronológico de la etapa');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });

        // Insertar etapas iniciales
        DB::table('etapas_imagen_reparacion')->insert([
            ['nombre' => 'ingreso', 'descripcion' => 'Imágenes al ingresar el dispositivo', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'proceso', 'descripcion' => 'Imágenes durante el proceso de reparación', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'salida', 'descripcion' => 'Imágenes al finalizar la reparación', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etapas_imagen_reparacion');
    }
};
