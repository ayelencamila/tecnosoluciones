<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Tabla ESTADOS_SOLICITUD (Paramétrica)
 * 
 * Lineamientos aplicados:
 * - Elmasri: Tabla paramétrica para evitar hardcodeo
 * - Kendall: Estados configurables del flujo de negocio
 * - Consistencia: Sigue el patrón de estados_producto, estados_cliente, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_solicitud', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique()
                  ->comment('Nombre del estado: Abierta, Cerrada, Cancelada, Vencida');
            $table->text('descripcion')->nullable()
                  ->comment('Descripción detallada del estado');
            $table->boolean('activo')->default(true)
                  ->comment('Si el estado está disponible para uso');
            $table->integer('orden')->default(0)
                  ->comment('Orden de visualización');
            $table->timestamps();
        });

        // Insertar estados iniciales (NO hardcodeo en código de aplicación, SÍ en datos maestros)
        DB::table('estados_solicitud')->insert([
            ['nombre' => 'Abierta', 'descripcion' => 'Solicitud creada y lista para enviar a proveedores', 'activo' => true, 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Enviada', 'descripcion' => 'Solicitud enviada a proveedores, esperando respuestas', 'activo' => true, 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Cerrada', 'descripcion' => 'Solicitud cerrada, ya se eligió una oferta', 'activo' => true, 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Vencida', 'descripcion' => 'Solicitud vencida por tiempo límite', 'activo' => true, 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Cancelada', 'descripcion' => 'Solicitud cancelada manualmente', 'activo' => true, 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_solicitud');
    }
};
