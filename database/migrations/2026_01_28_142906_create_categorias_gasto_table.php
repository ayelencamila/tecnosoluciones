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
        Schema::create('categorias_gasto', function (Blueprint $table) {
            $table->id('categoria_gasto_id');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->enum('tipo', ['gasto', 'perdida'])->default('gasto'); // Para diferenciar gastos de pérdidas
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar categorías por defecto
        DB::table('categorias_gasto')->insert([
            ['nombre' => 'Alquiler', 'descripcion' => 'Alquiler del local', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Servicios', 'descripcion' => 'Luz, agua, gas, internet, teléfono', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Sueldos', 'descripcion' => 'Sueldos y cargas sociales', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Publicidad', 'descripcion' => 'Marketing y publicidad', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mantenimiento', 'descripcion' => 'Reparaciones y mantenimiento del local', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Impuestos', 'descripcion' => 'Impuestos y tasas', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Comisiones bancarias', 'descripcion' => 'Comisiones y gastos bancarios', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Viáticos', 'descripcion' => 'Viáticos y movilidad', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Otros gastos', 'descripcion' => 'Gastos varios', 'tipo' => 'gasto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mercadería dañada', 'descripcion' => 'Productos dañados o vencidos', 'tipo' => 'perdida', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Robo/Faltante', 'descripcion' => 'Pérdidas por robo o faltantes', 'tipo' => 'perdida', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Otras pérdidas', 'descripcion' => 'Otras pérdidas no categorizadas', 'tipo' => 'perdida', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_gasto');
    }
};
