<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Particiona `reparaciones` por empresa_id.
 *
 * Cada empresa lleva su propia secuencia de codigo_reparacion. Las tablas
 * dependientes (detalle_reparaciones, imagenes_reparacion, alertas, etc.)
 * heredan la pertenencia tenant via FK a la reparacion padre, por lo que
 * no necesitan empresa_id propio (Elmasri: relacion identificadora de
 * composicion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('reparacionID');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropUnique(['codigo_reparacion']);
            $table->unique(['empresa_id', 'codigo_reparacion'], 'reparaciones_empresa_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropUnique('reparaciones_empresa_codigo_unique');
            $table->unique('codigo_reparacion');
        });

        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
