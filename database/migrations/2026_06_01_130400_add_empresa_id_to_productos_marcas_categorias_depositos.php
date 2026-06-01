<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Particiona el catalogo operacional por empresa: productos, marcas,
 * categorias_producto y depositos pasan a ser por-tenant.
 *
 * Decision del usuario: cada empresa administra sus propios catalogos
 * (marcas, categorias), no se comparten globalmente. Esto permite que
 * Empresa A use sus propias marcas/categorias sin contaminar el catalogo
 * de Empresa B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('id');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->unique(['empresa_id', 'codigo'], 'productos_empresa_codigo_unique');
        });

        Schema::table('marcas', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('id');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('marcas', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unique(['empresa_id', 'nombre'], 'marcas_empresa_nombre_unique');
        });

        Schema::table('categorias_producto', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('id');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('categorias_producto', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unique(['empresa_id', 'nombre'], 'categorias_producto_empresa_nombre_unique');
        });

        Schema::table('depositos', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('deposito_id');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('depositos', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unique(['empresa_id', 'nombre'], 'depositos_empresa_nombre_unique');
        });
    }

    public function down(): void
    {
        foreach (['depositos', 'categorias_producto', 'marcas', 'productos'] as $tabla) {
            $uniqueIndex = "{$tabla}_empresa_" . ($tabla === 'productos' ? 'codigo' : 'nombre') . '_unique';
            $col         = $tabla === 'productos' ? 'codigo' : 'nombre';

            Schema::table($tabla, function (Blueprint $table) use ($uniqueIndex, $col) {
                $table->dropUnique($uniqueIndex);
                $table->unique($col);
            });

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }
};
