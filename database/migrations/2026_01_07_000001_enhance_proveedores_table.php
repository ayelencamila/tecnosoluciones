<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Mejoras a la tabla PROVEEDORES (CU-16)
 * 
 * Lineamientos aplicados:
 * - Elmasri: Integridad de Dominio con CHAR(11) para CUIT (longitud fija)
 * - Elmasri: Integridad de Entidad con UNIQUE en razon_social
 * - Kendall: Campos para calificación y ranking (
 * - Relación con DIRECCIONES (1:N No Identificada)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Agregar email si no existe
            if (!Schema::hasColumn('proveedores', 'email')) {
                $table->string('email', 100)->nullable()->after('telefono');
            }
            
            // Agregar campos nuevos según CU-16
            $table->string('forma_pago_preferida', 50)->nullable()->after('email')
                  ->comment('Ej: Transferencia, Efectivo, Cheque');
            
            $table->integer('plazo_entrega_estimado')->nullable()->after('forma_pago_preferida')
                  ->comment('Días promedio de entrega');
            
            $table->decimal('calificacion', 2, 1)->nullable()->default(0)->after('plazo_entrega_estimado')
                  ->comment('Para ranking CU-21. Rango: 0.0 a 5.0');
            
            // Relación con DIRECCIONES (Elmasri: 1:N No Identificada)
            // Una dirección puede pertenecer a muchos proveedores
            $table->unsignedBigInteger('direccion_id')->nullable()->after('calificacion')
                  ->comment('Dirección principal del proveedor');
            
            $table->foreign('direccion_id')
                  ->references('direccionID')
                  ->on('direcciones')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
            
            
            // Modificar CUIT según Elmasri (CHAR fijo, no VARCHAR)
            // Laravel no tiene char() nativo, pero string con longitud fija equivale
            $table->string('cuit', 11)->nullable(false)->change()
                  ->comment('CUIT sin guiones. Ej: 20123456789');
            
            // Agregar UNIQUE a razon_social (Elmasri: evita duplicidad)
            if (!Schema::hasColumn('proveedores', 'razon_social')) {
                $table->unique('razon_social');
            }
            
            $table->index('calificacion');
            $table->index('direccion_id');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropForeign(['direccion_id']);
            $table->dropColumn([
                'email',
                'forma_pago_preferida',
                'plazo_entrega_estimado',
                'calificacion',
                'direccion_id'
            ]);
        });
    }
};
