<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Particiona `auditorias` por empresa_id (atributo de particion tenant).
 *
 * El log de auditoria es una entidad transversal: cada registro pertenece a
 * la empresa en cuyo contexto ocurrio la accion. Sin este scope, un admin de
 * una empresa podria leer el rastro de auditoria de otra (fuga de datos,
 * RNF4).
 *
 * Se usa el patron canonico del proyecto (ver
 * `add_empresa_id_to_clientes_y_proveedores`):
 *   - `default(1)` backfillea las filas historicas hacia la empresa por defecto.
 *   - `onDelete('restrict')` protege el historico: borrar una empresa NO puede
 *     destruir su rastro de auditoria (inmutabilidad / append-only, Sommerville).
 *
 * NOTA: el scoping en si NO se hace via Global Scope (decision de arquitectura
 * del proyecto). Se aplica manualmente al escribir (`Auditoria::registrar`) y
 * al leer (`AuditoriaController`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('auditorias', 'empresa_id')) {
            Schema::table('auditorias', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->default(1)->after('auditoriaID');
                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
                $table->index('empresa_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('auditorias', 'empresa_id')) {
            Schema::table('auditorias', function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }
};
