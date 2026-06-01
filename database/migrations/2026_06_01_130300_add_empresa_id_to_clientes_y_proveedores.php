<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Particiona `clientes` y `proveedores` por empresa_id (atributo de
 * particion tenant).
 *
 * Reemplaza los UNIQUE globales por UNIQUE compuestos `(empresa_id, X)`,
 * permitiendo que dos empresas distintas tengan clientes/proveedores con el
 * mismo DNI/CUIT/razon_social (Elmasri: integridad de entidad a nivel de
 * particion tenant).
 *
 * NOTA: esta migracion es idempotente para tolerar estado parcial. La
 * migracion historica `enhance_proveedores` contiene una condicion logica
 * invertida (`if (!Schema::hasColumn('razon_social'))`) que provoco que el
 * UNIQUE sobre `proveedores.razon_social` nunca se haya creado en la BD.
 * Por eso aqui se valida la existencia de cada indice antes de dropearlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- CLIENTES ----
        if (!Schema::hasColumn('clientes', 'empresa_id')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->default(1)->after('clienteID');
                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
                $table->index('empresa_id');
            });
        }

        if (!$this->hasIndex('clientes', 'clientes_empresa_dni_unique')) {
            Schema::table('clientes', function (Blueprint $table) {
                if ($this->hasIndex('clientes', 'clientes_DNI_unique')) {
                    $table->dropUnique(['DNI']);
                }
                $table->unique(['empresa_id', 'DNI'], 'clientes_empresa_dni_unique');
            });
        }

        // ---- PROVEEDORES ----
        if (!Schema::hasColumn('proveedores', 'empresa_id')) {
            Schema::table('proveedores', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->default(1)->after('id');
                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
                $table->index('empresa_id');
            });
        }

        if (!$this->hasIndex('proveedores', 'proveedores_empresa_cuit_unique')) {
            Schema::table('proveedores', function (Blueprint $table) {
                if ($this->hasIndex('proveedores', 'proveedores_cuit_unique')) {
                    $table->dropUnique(['cuit']);
                }
                $table->unique(['empresa_id', 'cuit'], 'proveedores_empresa_cuit_unique');
            });
        }

        if (!$this->hasIndex('proveedores', 'proveedores_empresa_razon_social_unique')) {
            Schema::table('proveedores', function (Blueprint $table) {
                if ($this->hasIndex('proveedores', 'proveedores_razon_social_unique')) {
                    $table->dropUnique(['razon_social']);
                }
                $table->unique(['empresa_id', 'razon_social'], 'proveedores_empresa_razon_social_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if ($this->hasIndex('proveedores', 'proveedores_empresa_cuit_unique')) {
                $table->dropUnique('proveedores_empresa_cuit_unique');
                $table->unique('cuit');
            }
            if ($this->hasIndex('proveedores', 'proveedores_empresa_razon_social_unique')) {
                $table->dropUnique('proveedores_empresa_razon_social_unique');
            }
        });

        Schema::table('proveedores', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });

        Schema::table('clientes', function (Blueprint $table) {
            if ($this->hasIndex('clientes', 'clientes_empresa_dni_unique')) {
                $table->dropUnique('clientes_empresa_dni_unique');
                $table->unique('DNI');
            }
        });

        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }

    /**
     * Verifica si un indice existe en MySQL via INFORMATION_SCHEMA.
     */
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
