<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deja los roles administradores de empresa con el juego COMPLETO de permisos
 * del config.
 *
 * Por que hace falta
 * ------------------
 * AltaEmpresaService le asigna al rol administrador todos los permisos que
 * existen en config/permisos.php al momento del alta. Pero cada vez que se
 * agrega un permiso nuevo --y en estos dias se agregaron cuatro:
 * clientes.ver_datos_contacto, proveedores.ver_datos_contacto,
 * reparaciones.ver_todas y reportes.mensual-- los roles que ya existian
 * quedan desactualizados.
 *
 * En el funcionamiento no se nota, porque `es_admin_empresa` hace que
 * User::hasPermission() devuelva true para todo. Pero la pantalla de roles
 * lee el arreglo guardado, asi que el administrador ve casillas SIN tildar de
 * cosas que en realidad si puede hacer. Es confuso y, peor, invita a creer
 * que el permiso esta denegado cuando no lo esta.
 *
 * Esta migracion sincroniza lo guardado con lo que realmente ocurre.
 */
return new class extends Migration
{
    public function up(): void
    {
        $todos = $this->todosLosPermisos();

        DB::table('roles')
            ->where('es_admin_empresa', true)
            ->update(['permisos' => json_encode($todos)]);
    }

    /**
     * No hay vuelta atras util: no se guarda cual era el subconjunto anterior
     * de cada rol, y de todas formas el flag `es_admin_empresa` concede todo,
     * asi que revertirlo no cambiaria ningun comportamiento.
     */
    public function down(): void
    {
        // Intencionalmente vacio.
    }

    /** @return list<string> */
    private function todosLosPermisos(): array
    {
        $permisos = [];

        foreach (config('permisos.modulos', []) as $modulo) {
            foreach (array_keys($modulo['permisos'] ?? []) as $permiso) {
                $permisos[] = $permiso;
            }
        }

        return $permisos;
    }
};
