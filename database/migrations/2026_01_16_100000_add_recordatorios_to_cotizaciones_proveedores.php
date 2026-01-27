<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Agregar campos de recordatorio a cotizaciones_proveedores (CU-20)
 * 
 * Permite al sistema hacer seguimiento de recordatorios enviados a proveedores
 * que no han respondido a las solicitudes de cotización.
 * 
 * Lineamientos aplicados:
 * - Profesor: "Se debe poder mandar uno o varios mails o whatsapps"
 * - Kendall: Control de seguimiento en proceso de compras
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            // Cantidad de recordatorios enviados (máximo 3)
            $table->unsignedTinyInteger('recordatorios_enviados')
                  ->default(0)
                  ->after('motivo_rechazo')
                  ->comment('Cantidad de recordatorios enviados al proveedor');
            
            // Fecha del último recordatorio enviado
            $table->dateTime('ultimo_recordatorio')
                  ->nullable()
                  ->after('recordatorios_enviados')
                  ->comment('Fecha y hora del último recordatorio enviado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            $table->dropColumn(['recordatorios_enviados', 'ultimo_recordatorio']);
        });
    }
};
