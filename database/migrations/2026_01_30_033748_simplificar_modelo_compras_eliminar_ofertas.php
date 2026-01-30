<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Simplificar modelo de compras eliminando tabla ofertas_compra
 * 
 * Justificación (análisis de normalización):
 * - La tabla ofertas_compra es redundante con cotizaciones_proveedores
 * - Ambas representan lo mismo: la respuesta de un proveedor a una solicitud
 * - La relación era 1:1 en el 99% de los casos
 * - Eliminarla reduce complejidad y número de JOINs
 * 
 * Cambios:
 * 1. Agregar campos de ofertas_compra a cotizaciones_proveedores
 * 2. Migrar FK de ordenes_compra: oferta_id → cotizacion_proveedor_id
 * 3. Eliminar tablas ofertas_compra y detalle_ofertas_compra
 * 
 * Nueva cadena: SolicitudCotizacion → CotizacionProveedor → OrdenCompra
 */
return new class extends Migration
{
    public function up(): void
    {
        // PASO 1: Agregar campos a cotizaciones_proveedores
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            // Campos que venían de ofertas_compra
            $table->decimal('total_estimado', 12, 2)->nullable()->after('motivo_rechazo');
            $table->date('validez_hasta')->nullable()->after('total_estimado');
            $table->string('archivo_adjunto')->nullable()->after('validez_hasta');
            $table->text('observaciones_respuesta')->nullable()->after('archivo_adjunto');
        });

        // PASO 2: Migrar datos de ofertas_compra a cotizaciones_proveedores
        $ofertas = DB::table('ofertas_compra')
            ->whereNotNull('cotizacion_proveedor_id')
            ->get();

        foreach ($ofertas as $oferta) {
            DB::table('cotizaciones_proveedores')
                ->where('id', $oferta->cotizacion_proveedor_id)
                ->update([
                    'total_estimado' => $oferta->total_estimado,
                    'validez_hasta' => $oferta->validez_hasta,
                    'archivo_adjunto' => $oferta->archivo_adjunto,
                    'observaciones_respuesta' => $oferta->observaciones,
                ]);
        }

        // PASO 3: Agregar nueva FK a ordenes_compra
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreignId('cotizacion_proveedor_id')
                ->nullable()
                ->after('oferta_id')
                ->constrained('cotizaciones_proveedores')
                ->nullOnDelete();
        });

        // PASO 4: Migrar datos de oferta_id a cotizacion_proveedor_id
        $ordenes = DB::table('ordenes_compra')
            ->whereNotNull('oferta_id')
            ->get();

        foreach ($ordenes as $orden) {
            $oferta = DB::table('ofertas_compra')
                ->where('id', $orden->oferta_id)
                ->first();

            if ($oferta && $oferta->cotizacion_proveedor_id) {
                DB::table('ordenes_compra')
                    ->where('id', $orden->id)
                    ->update(['cotizacion_proveedor_id' => $oferta->cotizacion_proveedor_id]);
            }
        }

        // PASO 5: Eliminar FK antigua y columna oferta_id
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['oferta_id']);
            $table->dropColumn('oferta_id');
        });

        // PASO 6: Eliminar tablas de ofertas (en orden correcto por FKs)
        Schema::dropIfExists('detalle_ofertas_compra');
        Schema::dropIfExists('ofertas_compra');
        Schema::dropIfExists('estados_oferta');
    }

    public function down(): void
    {
        // Recrear tabla estados_oferta
        Schema::create('estados_oferta', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('descripcion')->nullable();
            $table->string('color', 20)->default('gray');
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // Recrear tabla ofertas_compra
        Schema::create('ofertas_compra', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_oferta', 20)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes_cotizacion')->nullOnDelete();
            $table->foreignId('cotizacion_proveedor_id')->nullable()->constrained('cotizaciones_proveedores')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('fecha_recepcion');
            $table->date('validez_hasta')->nullable();
            $table->string('archivo_adjunto')->nullable();
            $table->foreignId('estado_id')->constrained('estados_oferta');
            $table->text('observaciones')->nullable();
            $table->decimal('total_estimado', 12, 2)->nullable();
            $table->timestamps();
        });

        // Recrear tabla detalle_ofertas_compra
        Schema::create('detalle_ofertas_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')->constrained('ofertas_compra')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad_ofrecida');
            $table->decimal('precio_unitario', 10, 2);
            $table->integer('plazo_entrega_dias')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // Restaurar columna oferta_id en ordenes_compra
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreignId('oferta_id')
                ->nullable()
                ->after('proveedor_id')
                ->constrained('ofertas_compra')
                ->nullOnDelete();
        });

        // Eliminar nueva FK y columna
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['cotizacion_proveedor_id']);
            $table->dropColumn('cotizacion_proveedor_id');
        });

        // Eliminar nuevos campos de cotizaciones_proveedores
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'total_estimado',
                'validez_hasta',
                'archivo_adjunto',
                'observaciones_respuesta',
            ]);
        });
    }
};
