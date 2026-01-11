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
        Schema::create('tipos_alerta_reparacion', function (Blueprint $table) {
            $table->id('tipo_id');
            $table->string('nombre', 50)->unique()->comment('sla_excedido, sin_respuesta, cliente_sin_decidir');
            $table->string('descripcion', 255)->nullable();
            $table->integer('prioridad')->default(1)->comment('1=Baja, 2=Media, 3=Alta');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });

        // Insertar tipos iniciales
        DB::table('tipos_alerta_reparacion')->insert([
            ['nombre' => 'sla_excedido', 'descripcion' => 'SLA de reparación excedido', 'prioridad' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'sin_respuesta', 'descripcion' => 'Técnico no ha respondido', 'prioridad' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'cliente_sin_decidir', 'descripcion' => 'Cliente no ha tomado decisión', 'prioridad' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_alerta_reparacion');
    }
};
