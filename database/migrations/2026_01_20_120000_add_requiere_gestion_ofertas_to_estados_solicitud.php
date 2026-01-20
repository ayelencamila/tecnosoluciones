<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Agregar campo parametrizable para CU-21
 * 
 * Lineamientos aplicados:
 * - Elmasri: Campo booleano para filtrar sin hardcodeo
 * - Kendall: Configuración parametrizable del flujo de negocio
 * 
 * Este campo indica qué estados de solicitud requieren gestión de ofertas
 * en el módulo CU-21 (Gestionar Ofertas de Compra).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estados_solicitud', function (Blueprint $table) {
            $table->boolean('requiere_gestion_ofertas')->default(false)->after('activo')
                  ->comment('Indica si las solicitudes en este estado aparecen en CU-21 para gestión de ofertas');
        });

        // Actualizar estados que requieren gestión de ofertas
        // (Abierta, Enviada, Pendiente son los que necesitan que se registren ofertas)
        DB::table('estados_solicitud')
            ->whereIn('nombre', ['Abierta', 'Enviada', 'Pendiente'])
            ->update(['requiere_gestion_ofertas' => true]);
    }

    public function down(): void
    {
        Schema::table('estados_solicitud', function (Blueprint $table) {
            $table->dropColumn('requiere_gestion_ofertas');
        });
    }
};
