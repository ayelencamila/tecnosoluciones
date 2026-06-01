<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega empresa_id a la tabla `users` como atributo de particion del
 * tenant. La columna se crea como NOT NULL DEFAULT 1 para que:
 *  - Los usuarios existentes queden asignados a la empresa default (Elmasri:
 *    integridad de entidad sin NULL en identificadores).
 *  - Los INSERT desde controllers que aun no son tenant-aware sigan
 *    funcionando (Strangler Fig). El DEFAULT se removera en Fase 2 cuando
 *    todo el codigo setee empresa_id explicitamente desde auth().
 *
 * `users.email` se mantiene UNIQUE global por decision del usuario: una
 * persona = un mail en todo el sistema, no puede repetirse entre empresas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('id')
                ->comment('FK a empresas. DEFAULT 1 = empresa default durante migracion Strangler Fig');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
