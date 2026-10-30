<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte en ENTIDADES DEBILES de `empresas` los cuatro catalogos que son
 * decisiones de negocio de cada cliente (Elmasri Cap.7):
 *
 *   - medios_pago              : cada empresa cobra como quiere.
 *   - tipos_cliente            : su propia segmentacion comercial.
 *   - unidades_medida          : depende de lo que venda.
 *   - motivos_demora_reparacion: su propia tipificacion de demoras.
 *
 * Hasta ahora eran globales y editables por el administrador de CUALQUIER
 * empresa: si un cliente borraba el medio de pago "Efectivo", se lo borraba
 * a todos los demas. No era una fuga de datos, pero si estado mutable
 * compartido entre clientes distintos, que es igual de inaceptable en un
 * modelo SaaS.
 *
 * Los catalogos geograficos (`provincias`, `localidades`) y los de estados
 * (`estados_*`) quedan GLOBALES a proposito: la geografia argentina es la
 * misma para todos, y los estados estan acoplados al codigo (el parametro
 * `estados_pausa_sla` referencia sus nombres). A esos se les quita la
 * escritura al administrador de empresa en lugar de particionarlos.
 *
 * Tratamiento del identificador debil: el UNIQUE global de la clave natural
 * se reemplaza por un UNIQUE compuesto con `empresa_id`, de modo que el
 * "Efectivo" de la Empresa A y el "Efectivo" de la Empresa B sean entidades
 * distintas que conviven. Es el mismo tratamiento que recibio `roles.nombre`.
 *
 * Integridad referencial (Elmasri Cap.6): donde la tabla que referencia
 * tambien lleva `empresa_id`, la FK simple se reemplaza por una FK COMPUESTA
 * (empresa_id, catalogo_id), que es la unica forma de que la base garantice
 * que una venta no pueda apuntar al medio de pago de otra empresa. Donde la
 * tabla que referencia es una entidad debil que hereda el tenant de su padre
 * (`precios_producto`, `bonificaciones_reparacion`, `respuestas_alertas`) no
 * existe la columna para componer la clave, y la restriccion se hace cumplir
 * en la aplicacion mediante reglas de validacion acotadas por empresa.
 */
return new class extends Migration
{
    /**
     * catalogo => [columna PK, [claves naturales que pasan a ser por empresa]]
     */
    private array $catalogos = [
        'medios_pago' => ['medioPagoID', ['nombre']],
        'tipos_cliente' => ['tipoClienteID', ['nombreTipo']],
        'unidades_medida' => ['id', ['nombre', 'abreviatura']],
        'motivos_demora_reparacion' => ['motivoDemoraID', ['codigo']],
    ];

    /**
     * FK compuestas a crear: tabla que referencia => [columna, catalogo, PK del catalogo]
     *
     * Solo figuran las tablas que ya tienen `empresa_id` propio. Las entidades
     * debiles quedan afuera por lo explicado en la cabecera.
     */
    private array $fksCompuestas = [
        'pagos' => ['medioPagoID', 'medios_pago', 'medioPagoID'],
        'ventas' => ['medio_pago_id', 'medios_pago', 'medioPagoID'],
        'reparaciones' => ['medio_pago_id', 'medios_pago', 'medioPagoID'],
        'clientes' => ['tipoClienteID', 'tipos_cliente', 'tipoClienteID'],
        'productos' => ['unidad_medida_id', 'unidades_medida', 'id'],
    ];

    public function up(): void
    {
        // 1. Atributo de particion en cada catalogo.
        foreach ($this->catalogos as $tabla => [$pk, $naturales]) {
            Schema::table($tabla, function (Blueprint $table) use ($pk) {
                $table->unsignedBigInteger('empresa_id')
                    ->default(1)
                    ->after($pk)
                    ->comment('FK identificadora a empresas: catalogo propio de cada empresa');

                $table->foreign('empresa_id')
                    ->references('id')->on('empresas')
                    ->onUpdate('restrict')
                    ->onDelete('restrict');
            });

            // 2. La clave natural pasa a ser unica DENTRO de la empresa.
            foreach ($naturales as $natural) {
                Schema::table($tabla, function (Blueprint $table) use ($natural) {
                    $table->dropUnique([$natural]);
                });

                Schema::table($tabla, function (Blueprint $table) use ($tabla, $natural) {
                    $table->unique(['empresa_id', $natural], $this->nombreUnique($tabla, $natural));
                });
            }

            // 3. Indice que habilita la FK compuesta desde las tablas hijas.
            Schema::table($tabla, function (Blueprint $table) use ($tabla, $pk) {
                $table->unique(['empresa_id', $pk], $this->nombreUnique($tabla, 'ref'));
            });
        }

        // 4. Cada empresa existente recibe su propia copia del catalogo.
        //    Se replica desde la empresa default en lugar de insertar una lista
        //    fija: asi cada empresa arranca con lo que el sistema ya tenia en
        //    produccion, sin perder filas que el usuario hubiera agregado.
        $this->replicarCatalogosAOtrasEmpresas();

        // 5. FK simples -> FK compuestas (empresa_id, catalogo_id).
        foreach ($this->fksCompuestas as $tabla => [$columna, $catalogo, $pkCatalogo]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna) {
                $table->dropForeign([$columna]);
            });

            DB::statement("
                ALTER TABLE `{$tabla}`
                ADD CONSTRAINT `{$tabla}_{$columna}_empresa_foreign`
                FOREIGN KEY (`empresa_id`, `{$columna}`)
                REFERENCES `{$catalogo}` (`empresa_id`, `{$pkCatalogo}`)
                ON UPDATE RESTRICT ON DELETE RESTRICT
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->fksCompuestas as $tabla => [$columna, $catalogo, $pkCatalogo]) {
            DB::statement("ALTER TABLE `{$tabla}` DROP FOREIGN KEY `{$tabla}_{$columna}_empresa_foreign`");

            Schema::table($tabla, function (Blueprint $table) use ($columna, $catalogo, $pkCatalogo) {
                $table->foreign($columna)
                    ->references($pkCatalogo)->on($catalogo)
                    ->onUpdate('restrict')
                    ->onDelete('restrict');
            });
        }

        // Las filas replicadas a otras empresas se eliminan antes de volver a
        // un UNIQUE global, que no las admitiria por nombre repetido.
        foreach (array_keys($this->catalogos) as $tabla) {
            DB::table($tabla)->where('empresa_id', '!=', 1)->delete();
        }

        foreach ($this->catalogos as $tabla => [$pk, $naturales]) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropUnique($this->nombreUnique($tabla, 'ref'));
            });

            foreach ($naturales as $natural) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla, $natural) {
                    $table->dropUnique($this->nombreUnique($tabla, $natural));
                });

                Schema::table($tabla, function (Blueprint $table) use ($natural) {
                    $table->unique($natural);
                });
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }

    /**
     * Nombres de indice acotados a los 64 caracteres que admite MySQL
     * (`motivos_demora_reparacion` genera identificadores largos).
     */
    private function nombreUnique(string $tabla, string $sufijo): string
    {
        $base = str_replace('_reparacion', '', $tabla);

        return substr("{$base}_empresa_{$sufijo}_unique", 0, 64);
    }

    /**
     * Copia el catalogo de la empresa default a todas las demas empresas.
     */
    private function replicarCatalogosAOtrasEmpresas(): void
    {
        $otrasEmpresas = DB::table('empresas')
            ->whereNull('deleted_at')
            ->where('id', '!=', 1)
            ->pluck('id');

        if ($otrasEmpresas->isEmpty()) {
            return;
        }

        foreach ($this->catalogos as $tabla => [$pk, $naturales]) {
            $filasBase = DB::table($tabla)->where('empresa_id', 1)->get();

            foreach ($otrasEmpresas as $empresaId) {
                $nuevas = [];

                foreach ($filasBase as $fila) {
                    $copia = (array) $fila;
                    unset($copia[$pk]);          // PK nueva por autoincremento
                    $copia['empresa_id'] = $empresaId;
                    $nuevas[] = $copia;
                }

                if ($nuevas !== []) {
                    DB::table($tabla)->insert($nuevas);
                }
            }
        }
    }
};
