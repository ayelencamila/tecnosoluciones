<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte `roles` en entidad debil de `empresas` (Elmasri Cap.7).
 *
 * Una entidad debil es aquella que no puede identificarse univocamente con
 * sus atributos propios y depende existencialmente de una entidad fuerte.
 * En este caso:
 *  - `empresas` es la entidad fuerte identificadora.
 *  - `roles` depende existencialmente: el rol "vendedor" de Empresa A es
 *    una entidad distinta del rol "vendedor" de Empresa B, aunque compartan
 *    nombre.
 *  - El identificador parcial (discriminador) es `nombre`, unico DENTRO
 *    del scope de una empresa.
 *
 * Implementacion en MySQL:
 *  - Conservamos `rol_id` como clave subrogada (admisible en Elmasri Cap.9
 *    como alternativa pragmatica al PK compuesto natural).
 *  - Reemplazamos el UNIQUE global de `nombre` por un UNIQUE compuesto
 *    `(empresa_id, nombre)` que materializa el identificador debil.
 *
 * Estrategia Strangler Fig: NOT NULL DEFAULT 1 para retro-compatibilidad
 * mientras dura la migracion del codigo de aplicacion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('rol_id')
                ->comment('FK identificadora a empresas. Roles modelados como entidad debil (Elmasri Cap.7)');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unique(['empresa_id', 'nombre'], 'roles_empresa_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_empresa_nombre_unique');
            $table->unique('nombre');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
