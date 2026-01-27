<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Tabla ESTADOS_ORDEN_COMPRA (Paramétrica)
 * 
 * Lineamientos aplicados:
 * - Elmasri: Tabla paramétrica para evitar hardcodeo
 * - Kendall: Estados del ciclo de vida de la orden de compra
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_orden_compra', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique()
                  ->comment('Nombre del estado: Borrador, Enviada, Recibida Parcial, Recibida Total, Cancelada');
            $table->text('descripcion')->nullable()
                  ->comment('Descripción detallada del estado');
            $table->boolean('activo')->default(true)
                  ->comment('Si el estado está disponible para uso');
            $table->integer('orden')->default(0)
                  ->comment('Orden de visualización');
            $table->timestamps();
        });

        // Insertar estados iniciales
        DB::table('estados_orden_compra')->insert([
            ['nombre' => 'Borrador', 'descripcion' => 'Orden de compra creada pero no enviada', 'activo' => true, 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Enviada', 'descripcion' => 'Orden enviada al proveedor, esperando confirmación', 'activo' => true, 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Confirmada', 'descripcion' => 'Proveedor confirmó la orden', 'activo' => true, 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Recibida Parcial', 'descripcion' => 'Recepción parcial de productos', 'activo' => true, 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Recibida Total', 'descripcion' => 'Todos los productos fueron recibidos', 'activo' => true, 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Cancelada', 'descripcion' => 'Orden de compra cancelada', 'activo' => true, 'orden' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_orden_compra');
    }
};
