<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cierra los cuatro modulos que habian quedado afuera del particionado
 * multi-tenant y que, a diferencia de las tablas hijas (stock, detalle_*,
 * cuentas_corriente), NO heredan el tenant por FK: no cuelgan de ninguna
 * entidad que tenga `empresa_id`, por lo que hoy serian compartidos entre
 * todas las empresas.
 *
 *  - gastos / categorias_gasto : cada empresa lleva su propia contabilidad.
 *  - plantillas_whatsapp       : cada empresa redacta sus propios mensajes
 *                                a clientes y proveedores.
 *  - configuracion             : tabla EAV con parametros operativos (SLA de
 *                                reparaciones, politica de descuentos). Son
 *                                decisiones de negocio de cada empresa.
 *
 * Se mantiene el criterio de las migraciones de la Fase 1 (NOT NULL DEFAULT 1
 * + FK restrictiva) para que las filas existentes queden atribuidas a la
 * empresa default y los seeders sigan funcionando sin cambios.
 *
 * Ademas se reparan dos UNIQUE globales que, al volverse los datos por
 * empresa, pasarian a ser bloqueantes entre tenants:
 *
 *  - `configuracion.clave`            -> UNIQUE (empresa_id, clave)
 *  - `plantillas_whatsapp.tipo_evento`-> UNIQUE (empresa_id, tipo_evento)
 *
 * Sin este cambio, que la Empresa A tenga una plantilla para el evento
 * "demora_cliente" impediria a la Empresa B crear la suya. Es el mismo
 * tratamiento que recibio `roles.nombre` al volverse entidad debil.
 */
return new class extends Migration
{
    /**
     * Tablas a particionar y la columna tras la cual insertar empresa_id.
     */
    private array $tablas = [
        'gastos'              => 'gasto_id',
        'categorias_gasto'    => 'categoria_gasto_id',
        'plantillas_whatsapp' => 'plantilla_id',
        'configuracion'       => 'configuracionID',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla => $despuesDe) {
            Schema::table($tabla, function (Blueprint $table) use ($despuesDe) {
                $table->unsignedBigInteger('empresa_id')
                    ->default(1)
                    ->after($despuesDe)
                    ->comment('FK a empresas: atributo de particion del tenant');

                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');

                $table->index('empresa_id');
            });
        }

        // Los identificadores naturales pasan a ser unicos POR empresa.
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropUnique(['clave']);
            $table->unique(['empresa_id', 'clave'], 'configuracion_empresa_clave_unique');
        });

        Schema::table('plantillas_whatsapp', function (Blueprint $table) {
            $table->dropUnique(['tipo_evento']);
            $table->unique(['empresa_id', 'tipo_evento'], 'plantillas_empresa_evento_unique');
        });
    }

    public function down(): void
    {
        Schema::table('plantillas_whatsapp', function (Blueprint $table) {
            $table->dropUnique('plantillas_empresa_evento_unique');
            $table->unique('tipo_evento');
        });

        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropUnique('configuracion_empresa_clave_unique');
            $table->unique('clave');
        });

        foreach (array_keys($this->tablas) as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }
};
