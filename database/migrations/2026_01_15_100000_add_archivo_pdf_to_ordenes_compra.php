<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Agregar campos faltantes a ordenes_compra y estado "Envío Fallido"
 * 
 * CU-22: Soporte para PDF y manejo de errores de envío
 */
return new class extends Migration
{
    public function up(): void
    {
        // Agregar columna archivo_pdf si no existe
        if (!Schema::hasColumn('ordenes_compra', 'archivo_pdf')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                $table->string('archivo_pdf')->nullable()
                      ->after('observaciones')
                      ->comment('Ruta del PDF generado de la orden');
            });
        }

        // Agregar estado "Envío Fallido" si no existe
        $existe = DB::table('estados_orden_compra')
            ->where('nombre', 'Envío Fallido')
            ->exists();

        if (!$existe) {
            DB::table('estados_orden_compra')->insert([
                'nombre' => 'Envío Fallido',
                'descripcion' => 'Error al enviar la orden por WhatsApp/Email',
                'activo' => true,
                'orden' => 3, // Entre Enviada y Confirmada
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropColumn('archivo_pdf');
        });

        DB::table('estados_orden_compra')
            ->where('nombre', 'Envío Fallido')
            ->delete();
    }
};
