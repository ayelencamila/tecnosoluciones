<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Particiona el modulo de compras y descuentos por empresa_id.
 *
 * Cada empresa lleva su propia numeracion de:
 *  - codigo_solicitud (solicitudes_cotizacion)
 *  - numero_oc (ordenes_compra)
 *  - codigo_oferta (ofertas_compra) [solo si la tabla existe]
 *  - codigo (descuentos)
 *
 * NOTA 1: la tabla `ofertas_compra` fue eliminada por la migracion
 * `simplificar_modelo_compras_eliminar_ofertas` (2026_01_30). En entornos
 * donde corrio esa simplificacion, no existe. Por eso se aplica el cambio
 * solo si la tabla esta presente.
 *
 * NOTA 2: la migracion es idempotente para tolerar estado parcial
 * (por si alguna ejecucion previa fallo en el medio).
 *
 * Las tablas de detalle y `cotizaciones_proveedores` heredan la pertenencia
 * tenant via FK al padre (Elmasri: relacion identificadora de composicion),
 * por lo que no necesitan empresa_id propio.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- SOLICITUDES_COTIZACION ----
        if (!Schema::hasColumn('solicitudes_cotizacion', 'empresa_id')) {
            Schema::table('solicitudes_cotizacion', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->default(1)->after('id');
                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
                $table->index('empresa_id');
            });
        }

        if (!$this->hasIndex('solicitudes_cotizacion', 'solicitudes_empresa_codigo_unique')) {
            Schema::table('solicitudes_cotizacion', function (Blueprint $table) {
                if ($this->hasIndex('solicitudes_cotizacion', 'solicitudes_cotizacion_codigo_solicitud_unique')) {
                    $table->dropUnique(['codigo_solicitud']);
                }
                $table->unique(['empresa_id', 'codigo_solicitud'], 'solicitudes_empresa_codigo_unique');
            });
        }

        // ---- ORDENES_COMPRA ----
        if (!Schema::hasColumn('ordenes_compra', 'empresa_id')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->default(1)->after('id');
                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
                $table->index('empresa_id');
            });
        }

        if (!$this->hasIndex('ordenes_compra', 'ordenes_compra_empresa_numero_unique')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                if ($this->hasIndex('ordenes_compra', 'ordenes_compra_numero_oc_unique')) {
                    $table->dropUnique(['numero_oc']);
                }
                $table->unique(['empresa_id', 'numero_oc'], 'ordenes_compra_empresa_numero_unique');
            });
        }

        // ---- OFERTAS_COMPRA (solo si existe) ----
        if (Schema::hasTable('ofertas_compra')) {
            if (!Schema::hasColumn('ofertas_compra', 'empresa_id')) {
                Schema::table('ofertas_compra', function (Blueprint $table) {
                    $table->unsignedBigInteger('empresa_id')->default(1)->after('id');
                    $table->foreign('empresa_id')
                        ->references('id')->on('empresas')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                    $table->index('empresa_id');
                });
            }

            if (!$this->hasIndex('ofertas_compra', 'ofertas_compra_empresa_codigo_unique')) {
                Schema::table('ofertas_compra', function (Blueprint $table) {
                    if ($this->hasIndex('ofertas_compra', 'ofertas_compra_codigo_oferta_unique')) {
                        $table->dropUnique(['codigo_oferta']);
                    }
                    $table->unique(['empresa_id', 'codigo_oferta'], 'ofertas_compra_empresa_codigo_unique');
                });
            }
        }

        // ---- DESCUENTOS ----
        if (!Schema::hasColumn('descuentos', 'empresa_id')) {
            Schema::table('descuentos', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->default(1)->after('descuento_id');
                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
                $table->index('empresa_id');
            });
        }

        if (!$this->hasIndex('descuentos', 'descuentos_empresa_codigo_unique')) {
            Schema::table('descuentos', function (Blueprint $table) {
                if ($this->hasIndex('descuentos', 'descuentos_codigo_unique')) {
                    $table->dropUnique(['codigo']);
                }
                $table->unique(['empresa_id', 'codigo'], 'descuentos_empresa_codigo_unique');
            });
        }
    }

    public function down(): void
    {
        $this->revertirTabla('descuentos', 'descuentos_empresa_codigo_unique', 'codigo');

        if (Schema::hasTable('ofertas_compra')) {
            $this->revertirTabla('ofertas_compra', 'ofertas_compra_empresa_codigo_unique', 'codigo_oferta');
        }

        $this->revertirTabla('ordenes_compra', 'ordenes_compra_empresa_numero_unique', 'numero_oc');
        $this->revertirTabla('solicitudes_cotizacion', 'solicitudes_empresa_codigo_unique', 'codigo_solicitud');
    }

    private function revertirTabla(string $tabla, string $uniqueCompuesto, string $columnaUnica): void
    {
        Schema::table($tabla, function (Blueprint $table) use ($tabla, $uniqueCompuesto, $columnaUnica) {
            if ($this->hasIndex($tabla, $uniqueCompuesto)) {
                $table->dropUnique($uniqueCompuesto);
                $table->unique($columnaUnica);
            }
        });

        Schema::table($tabla, function (Blueprint $table) use ($tabla) {
            if (Schema::hasColumn($tabla, 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();

        return !empty(DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1',
            [$database, $table, $indexName]
        ));
    }
};
