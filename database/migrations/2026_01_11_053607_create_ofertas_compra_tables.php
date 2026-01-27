<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas OFERTAS_COMPRA (CU-21)
 * 
 * Lineamientos aplicados:
 * - Elmasri 1FN: Detalle de productos en tabla separada (no arrays/JSON)
 * - Elmasri 2FN: No hay dependencias parciales
 * - Elmasri 3FN: Estado referencia tabla paramétrica (no hardcodeo)
 * - Larman: Trazabilidad de decisiones (quién registró, cuándo, por qué)
 * - Kendall: Control del flujo de evaluación de ofertas
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabla: OFERTAS_COMPRA (Cabecera/Entidad Fuerte)
        Schema::create('ofertas_compra', function (Blueprint $table) {
            $table->id()->comment('PK - ID único de la oferta');
            
            // Código único para identificación
            $table->string('codigo_oferta', 30)->unique()
                  ->comment('Código único: OF-ProvX-XXX para trazabilidad');
            
            // Relación con PROVEEDORES
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->onDelete('restrict')
                  ->comment('FK a proveedor que presenta la oferta');
            
            // Relación OPCIONAL con SOLICITUDES_COTIZACION
            // Nullable: CU-21 permite registrar ofertas espontáneas (sin solicitud previa)
            $table->foreignId('solicitud_id')->nullable()
                  ->constrained('solicitudes_cotizacion')
                  ->onDelete('set null')
                  ->comment('FK a solicitud origen (null = oferta espontánea)');
            
            // Relación con cotización_proveedor (si vino por Magic Link)
            $table->foreignId('cotizacion_proveedor_id')->nullable()
                  ->constrained('cotizaciones_proveedores')
                  ->onDelete('set null')
                  ->comment('FK a cotización proveedor si vino por Magic Link');
            
            // Auditoría: quién registró la oferta
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Usuario que registró la oferta en el sistema');
            
            // Datos del documento
            $table->dateTime('fecha_recepcion')
                  ->comment('Fecha y hora en que se recibió la oferta del proveedor');
            $table->dateTime('validez_hasta')->nullable()
                  ->comment('Fecha de vencimiento de la oferta');
            
            // Archivo adjunto (PDF/Imagen de la cotización)
            $table->string('archivo_adjunto', 255)->nullable()
                  ->comment('Ruta del archivo adjunto (PDF/imagen de cotización)');
            
            // Relación con ESTADOS_OFERTA (3FN: sin hardcodeo)
            $table->foreignId('estado_id')
                  ->constrained('estados_oferta')
                  ->onDelete('restrict')
                  ->comment('Estado de la oferta (FK a tabla paramétrica)');
            
            // Observaciones y motivos (Kendall: trazabilidad de decisiones)
            $table->text('observaciones')->nullable()
                  ->comment('Motivo del registro y notas del evaluador (CU-21 Paso 6)');
            
            // Total estimado (3FN: no guardamos subtotales calculables)
            // Este campo es solo para referencia rápida
            $table->decimal('total_estimado', 12, 2)->default(0)
                  ->comment('Total estimado de la oferta (suma de detalles)');
            
            $table->timestamps();
            
            // Índices
            $table->index('proveedor_id');
            $table->index('solicitud_id');
            $table->index('estado_id');
            $table->index('codigo_oferta');
            $table->index('fecha_recepcion');
        });

        // Tabla: DETALLE_OFERTAS_COMPRA (Entidad Débil - 1FN)
        Schema::create('detalle_ofertas_compra', function (Blueprint $table) {
            $table->id()->comment('PK - ID del detalle');
            
            // FK a OFERTAS_COMPRA (Relación 1:N Identificada)
            $table->foreignId('oferta_id')
                  ->constrained('ofertas_compra')
                  ->onDelete('cascade')
                  ->comment('FK a oferta padre (eliminación en cascada)');
            
            // FK a PRODUCTOS
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('restrict')
                  ->comment('FK a producto ofrecido');
            
            // Datos de la oferta por producto
            $table->integer('cantidad_ofrecida')
                  ->comment('Cantidad que el proveedor puede suministrar');
            
            $table->decimal('precio_unitario', 10, 2)
                  ->comment('Precio unitario ofrecido por el proveedor');
            
            // 1FN: NO guardamos subtotal (es calculable: cantidad * precio_unitario)
            
            // Condiciones de entrega
            $table->boolean('disponibilidad_inmediata')->default(true)
                  ->comment('Si el producto está disponible de inmediato');
            
            $table->integer('dias_entrega')->default(0)
                  ->comment('Días de plazo de entrega prometidos');
            
            $table->text('observaciones')->nullable()
                  ->comment('Observaciones específicas para este producto');
            
            $table->timestamps();
            
            // Índices
            $table->index('oferta_id');
            $table->index('producto_id');
            
            // Restricción única: no duplicar producto en la misma oferta (2FN)
            $table->unique(['oferta_id', 'producto_id'], 'unique_oferta_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ofertas_compra');
        Schema::dropIfExists('ofertas_compra');
    }
};