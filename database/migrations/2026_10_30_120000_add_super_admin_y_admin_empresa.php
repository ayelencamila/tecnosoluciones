<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce el NIVEL DE PLATAFORMA sobre el modelo multi-tenant.
 *
 * Hasta ahora el sistema tenia dos niveles de autoridad (administrador de
 * empresa y roles operativos) y el privilegio maximo se resolvia comparando
 * un string: `$user->role === 'administrador'`. Eso es fragil en ambos
 * sentidos: renombrar la fila quita todos los permisos, y cualquier empresa
 * que cree un rol llamado "administrador" obtiene bypass total.
 *
 * Esta migracion agrega las dos piezas que faltaban:
 *
 *  1. `users.es_super_admin` - operador de la plataforma (el dueño del
 *     software). NO pertenece a ninguna empresa: `empresa_id` es NULL.
 *     Es el mismo patron que `is_superuser` en Django o SUPERUSER_ID en
 *     Odoo: un flag por fuera del sistema de roles del tenant, nunca un
 *     rol adentro. El privilegio no se puede expresar como permiso porque
 *     los permisos son siempre relativos a UNA empresa.
 *
 *  2. `roles.es_admin_empresa` - reemplaza la comparacion por nombre, de
 *     modo que el privilegio deje de depender de un texto editable desde
 *     la UI de roles.
 *
 * Como consecuencia de (1), el super usuario tampoco tiene ROL: los roles
 * son entidad debil de una empresa, y el operador de plataforma no esta en
 * ninguna. Por eso `users.rol_id` pasa a NULLABLE junto con `empresa_id`:
 * ambas columnas son "coordenadas dentro de un tenant" y el super usuario
 * vive fuera de todo tenant.
 *
 * La restriccion CHECK materializa la regla semantica (Elmasri: restriccion
 * de integridad semantica) de que ambos niveles son mutuamente excluyentes:
 * o sos operador de plataforma (sin empresa y sin rol), o sos usuario de una
 * empresa (con empresa y con rol). No hay estados intermedios validos, y la
 * base de datos los rechaza sin depender de que la aplicacion se acuerde.
 *
 * Nota sobre `empresa_id`: pasa a NULLABLE pero conserva DEFAULT 1 porque
 * los seeders insertan via DB::table() sin especificarlo. El CHECK impide
 * que ese default produzca un estado incoherente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('es_super_admin')
                ->default(false)
                ->after('empresa_id')
                ->comment('Operador de la plataforma: acceso a todas las empresas. Exige empresa_id NULL');
        });

        // empresa_id nullable: el super usuario no pertenece a ninguna empresa.
        // Se usa SQL directo para conservar la FK, el indice y el DEFAULT en
        // una sola operacion (un ->change() de Laravel reescribiria la columna).
        DB::statement("
            ALTER TABLE users
            MODIFY empresa_id BIGINT UNSIGNED NULL DEFAULT 1
            COMMENT 'FK a empresas. NULL unicamente para el super usuario de plataforma'
        ");

        // MySQL 8 rechaza un CHECK sobre una columna que participa en una FK
        // con accion referencial (error 3823). La FK se recrea sin
        // ON UPDATE CASCADE: `empresas.id` es una clave subrogada
        // autoincremental que nunca se actualiza, por lo que el cascade no
        // protegia nada y su unico efecto era impedir el CHECK.
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        // rol_id nullable: los roles son entidad debil de una empresa, y el
        // super usuario no pertenece a ninguna, por lo que no puede tener rol.
        DB::statement("
            ALTER TABLE users
            MODIFY rol_id BIGINT UNSIGNED NULL
            COMMENT 'FK a roles. NULL unicamente para el super usuario de plataforma'
        ");

        DB::statement('
            ALTER TABLE users
            ADD CONSTRAINT chk_users_nivel_plataforma CHECK (
                (es_super_admin = 1 AND empresa_id IS NULL AND rol_id IS NULL)
                OR
                (es_super_admin = 0 AND empresa_id IS NOT NULL AND rol_id IS NOT NULL)
            )
        ');

        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('es_admin_empresa')
                ->default(false)
                ->after('nombre')
                ->comment('Administra su propia empresa: bypass de permisos dentro del tenant');
        });

        // Preserva el comportamiento actual: los roles que hoy obtienen bypass
        // por llamarse "administrador" lo siguen teniendo, ahora por flag.
        DB::table('roles')
            ->where('nombre', 'administrador')
            ->update(['es_admin_empresa' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('es_admin_empresa');
        });

        // Los super usuarios deben reasignarse antes de volver a NOT NULL,
        // porque dejarian de tener un tenant ni un rol valido al que pertenecer.
        DB::table('users')->whereNull('empresa_id')->update([
            'empresa_id' => 1,
            'rol_id'     => DB::table('roles')->where('empresa_id', 1)->where('nombre', 'administrador')->value('rol_id'),
        ]);

        DB::statement('ALTER TABLE users DROP CONSTRAINT chk_users_nivel_plataforma');

        DB::statement("
            ALTER TABLE users
            MODIFY rol_id BIGINT UNSIGNED NOT NULL
            COMMENT ''
        ");

        DB::statement("
            ALTER TABLE users
            MODIFY empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1
            COMMENT 'FK a empresas. DEFAULT 1 = empresa default durante migracion Strangler Fig'
        ");

        // Restaura la accion referencial original.
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('es_super_admin');
        });
    }
};
