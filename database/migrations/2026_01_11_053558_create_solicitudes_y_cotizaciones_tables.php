<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas SOLICITUDES_COTIZACION y COTIZACIONES_PROVEEDORES (CU-20)
 * 
 * Lineamientos aplicados:
 * - Elmasri 1FN: No hay atributos multivaluados (detalle en tabla separada)
 * - Elmasri 2FN: No hay dependencias parciales (PK correctamente definidas)
 * - Elmasri 3FN: No hay dependencias transitivas (estado_id referencia tabla paramétrica)
 * - Sin hardcodeo: Estados en tabla paramétrica (estados_solicitud)
 * - Magic Links: Token único (UUID) en cotizaciones_proveedores
 * - Kendall: Trazabilidad completa del flujo de cotización
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabla: SOLICITUDES_COTIZACION (Cabecera/Entidad Fuerte)
        Schema::create('solicitudes_cotizacion', function (Blueprint $table) {
            $table->id()->comment('PK - ID único de la solicitud');
            
            // Código único para identificación humana
            $table->string('codigo_solicitud', 30)->unique()
                  ->comment('Código único: SOL-YYYYMMDD-XXX para trazabilidad (Kendall)');
            
            // Fechas del proceso
            $table->dateTime('fecha_emision')
                  ->comment('Fecha y hora de creación de la solicitud');
            $table->dateTime('fecha_vencimiento')
                  ->comment('Fecha límite para recibir cotizaciones de proveedores');
            
            // Relación con ESTADOS_SOLICITUD (3FN: evita hardcodeo)
            $table->foreignId('estado_id')
                  ->constrained('estados_solicitud')
                  ->onDelete('restrict')
                  ->comment('Estado actual de la solicitud (FK a tabla paramétrica)');
            
            // Auditoría: quién creó la solicitud (null si es automático)
            $table->foreignId('user_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que creó la solicitud (null si es proceso automático)');
            
            $table->text('observaciones')->nullable()
                  ->comment('Notas adicionales sobre la solicitud');
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index('codigo_solicitud');
            $table->index('estado_id');
            $table->index('fecha_emision');
            $table->index('fecha_vencimiento');
        });

        // Tabla: DETALLE_SOLICITUDES_COTIZACION (Entidad Débil - 1FN)
        // Elmasri 1FN: Un atributo = un valor atómico (no JSON con múltiples productos)
        Schema::create('detalle_solicitudes_cotizacion', function (Blueprint $table) {
            $table->id()->comment('PK - ID del detalle');
            
            // FK a SOLICITUDES_COTIZACION (Relación 1:N Identificada)
            $table->foreignId('solicitud_id')
                  ->constrained('solicitudes_cotizacion')
                  ->onDelete('cascade')
                  ->comment('FK a solicitud padre (eliminación en cascada)');
            
            // FK a PRODUCTOS (Relación con productos del catálogo)
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('restrict')
                  ->comment('FK a producto solicitado');
            
            // Cantidad requerida
            $table->integer('cantidad_sugerida')
                  ->comment('Cantidad del producto que se solicita cotizar');
            
            $table->text('observaciones')->nullable()
                  ->comment('Observaciones específicas para este producto');
            
            $table->timestamps();
            
            // Índices
            $table->index('solicitud_id');
            $table->index('producto_id');
            
            // Restricción única: no duplicar producto en la misma solicitud (2FN)
            $table->unique(['solicitud_id', 'producto_id'], 'unique_solicitud_producto');
        });

        // Tabla: COTIZACIONES_PROVEEDORES (Relación N:M entre Solicitudes y Proveedores)
        // Esta tabla implementa el MAGIC LINK para CU-20 futuro
        Schema::create('cotizaciones_proveedores', function (Blueprint $table) {
            $table->id()->comment('PK - ID de la cotización proveedor');
            
            // FK a SOLICITUDES_COTIZACION
            $table->foreignId('solicitud_id')
                  ->constrained('solicitudes_cotizacion')
                  ->onDelete('cascade')
                  ->comment('FK a solicitud de cotización');
            
            // FK a PROVEEDORES
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict')
                  ->comment('FK a proveedor invitado a cotizar');
            
            // ★ MAGIC LINK: Token único (UUID) para acceso sin autenticación
            $table->string('token_unico', 64)->unique()
                  ->comment('Token único (UUID) para generar link de cotización sin login');
            
            // Control de envío
            $table->string('estado_envio', 20)->default('Pendiente')
                  ->comment('Estado del envío: Pendiente, Enviado, Fallido');
            $table->dateTime('fecha_envio')->nullable()
                  ->comment('Fecha y hora del envío del link al proveedor');
            
            // Control de respuesta
            $table->dateTime('fecha_respuesta')->nullable()
                  ->comment('Fecha y hora en que el proveedor respondió (null = sin respuesta)');
            $table->text('motivo_rechazo')->nullable()
                  ->comment('Si el proveedor rechazó, motivo del rechazo');
            
            $table->timestamps();
            
            // Índices
            $table->index('solicitud_id');
            $table->index('proveedor_id');
            $table->index('token_unico');
            $table->index('estado_envio');
            
            // Restricción única: un proveedor solo una cotización por solicitud (2FN)
            $table->unique(['solicitud_id', 'proveedor_id'], 'unique_solicitud_proveedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones_proveedores');
        Schema::dropIfExists('detalle_solicitudes_cotizacion');
        Schema::dropIfExists('solicitudes_cotizacion');
    }
};