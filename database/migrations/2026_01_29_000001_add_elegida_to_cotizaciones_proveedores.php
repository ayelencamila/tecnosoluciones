<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Agregar campo 'elegida' a cotizaciones_proveedores (CU-20)
 * 
 * Permite marcar una cotización como ganadora para generar la orden de compra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            $table->boolean('elegida')->default(false)->after('motivo_rechazo')
                  ->comment('Indica si esta cotización fue elegida como ganadora');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            $table->dropColumn('elegida');
        });
    }
};
