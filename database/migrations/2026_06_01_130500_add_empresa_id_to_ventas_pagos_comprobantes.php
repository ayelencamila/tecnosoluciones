<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Particiona las tablas transaccionales `ventas`, `pagos` y `comprobantes`
 * por empresa_id.
 *
 * Cada empresa lleva su propia numeracion correlativa (numero_comprobante,
 * numero_recibo, numero_correlativo). Por eso el UNIQUE deja de ser global
 * y pasa a `(empresa_id, X)`: Empresa A puede emitir su venta 0001 y
 * Empresa B tambien la suya, sin colision.
 *
 * No se aplica `empresa_id` a `detalle_ventas` ni a `pago_venta_imputacion`
 * porque la pertenencia tenant se hereda transitivamente desde la venta o
 * pago padre (Elmasri: relacion identificadora de composicion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('venta_id');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['numero_comprobante']);
            $table->unique(['empresa_id', 'numero_comprobante'], 'ventas_empresa_numero_comprobante_unique');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('pagoID');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropUnique(['numero_recibo']);
            $table->unique(['empresa_id', 'numero_recibo'], 'pagos_empresa_numero_recibo_unique');
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('comprobante_id');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropUnique(['numero_correlativo']);
            $table->unique(['empresa_id', 'numero_correlativo'], 'comprobantes_empresa_numero_correlativo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropUnique('comprobantes_empresa_numero_correlativo_unique');
            $table->unique('numero_correlativo');
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropUnique('pagos_empresa_numero_recibo_unique');
            $table->unique('numero_recibo');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_empresa_numero_comprobante_unique');
            $table->unique('numero_comprobante');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
