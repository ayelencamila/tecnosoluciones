<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Particiona `recepciones_mercaderia` con su propia `empresa_id`.
 *
 * El resto de las tablas hijas resuelve el tenant exigiendo la existencia
 * del padre (ver EmpresaPorRelacionScope), pero esa tecnica necesita una FK
 * obligatoria. Aca no se cumple: desde la migracion que permitio recepciones
 * sin orden de compra, `orden_compra_id` y `proveedor_id` son ambas
 * nullable, asi que una recepcion podria quedar sin padre por el cual
 * resolver la empresa y volverse invisible para todos los tenants.
 *
 * Al ser un documento de negocio con identidad propia -como `ordenes_compra`,
 * que ya tiene `empresa_id`- corresponde que lleve el atributo de particion
 * directamente. `detalle_recepciones` sigue colgando de esta tabla, cuya FK
 * si es obligatoria.
 *
 * El backfill toma la empresa de la orden de compra y, si no hay orden, del
 * proveedor; si tampoco hubiera, queda la empresa default por el DEFAULT 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recepciones_mercaderia', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')
                ->default(1)
                ->after('id')
                ->comment('FK a empresas: atributo de particion del tenant');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('restrict')
                ->onDelete('restrict');

            $table->index('empresa_id');
        });

        DB::statement('
            UPDATE recepciones_mercaderia r
            JOIN ordenes_compra o ON o.id = r.orden_compra_id
            SET r.empresa_id = o.empresa_id
            WHERE r.orden_compra_id IS NOT NULL
        ');

        DB::statement('
            UPDATE recepciones_mercaderia r
            JOIN proveedores p ON p.id = r.proveedor_id
            SET r.empresa_id = p.empresa_id
            WHERE r.orden_compra_id IS NULL AND r.proveedor_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('recepciones_mercaderia', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
