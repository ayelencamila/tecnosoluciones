<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Agregar campo WhatsApp a proveedores (CU-20)
 * 
 * Permite diferenciar entre teléfono fijo y WhatsApp para
 * el envío inteligente de solicitudes de cotización.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Campo WhatsApp separado del teléfono fijo
            if (!Schema::hasColumn('proveedores', 'whatsapp')) {
                $table->string('whatsapp', 20)->nullable()->after('telefono')
                      ->comment('Número WhatsApp con formato internacional. Ej: +5493754123456');
            }
            
            // Campo para preferencia de canal de comunicación
            if (!Schema::hasColumn('proveedores', 'canal_preferido')) {
                $table->enum('canal_preferido', ['email', 'whatsapp', 'ambos'])->default('ambos')->after('whatsapp')
                      ->comment('Canal preferido para comunicaciones');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'canal_preferido']);
        });
    }
};
