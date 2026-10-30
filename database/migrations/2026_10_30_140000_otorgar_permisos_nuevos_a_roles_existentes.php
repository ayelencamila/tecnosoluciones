<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reparte los permisos nuevos entre los roles que ya existen, para que el
 * cambio no le quite a nadie un acceso que su trabajo necesita.
 *
 * Se agregaron tres permisos:
 *
 *  - clientes.ver_datos_contacto     (datos completos del cliente)
 *  - proveedores.ver_datos_contacto  (datos completos del proveedor)
 *  - reparaciones.ver_todas          (tablero completo de reparaciones)
 *
 * El administrador de empresa no aparece acá: pasa por el flag
 * `es_admin_empresa`, que le concede todo dentro de su empresa.
 *
 * Criterio de reparto, deducido de lo que cada rol ya podía hacer y no del
 * nombre del rol, que cada empresa escribe como quiere:
 *
 *  - `reparaciones.ver_todas` para quien tiene `reparaciones.crear`. Hacer el
 *    ingreso de un equipo significa atender al público, y quien atiende al
 *    público tiene que poder responder "¿cómo va mi equipo?" de cualquier
 *    cliente, no solo de los equipos que le tocaron. Un técnico no hace
 *    ingresos, así que trabaja sobre las suyas.
 *
 *  - Los datos de contacto NO se reparten: quien tiene `.editar` ya los
 *    recibe completos por regla (no se puede modificar un dato que no se ve),
 *    y para el resto el enmascarado es justamente lo que se quiere. Si una
 *    empresa necesita que un rol de solo lectura vea el contacto completo, lo
 *    tilda en la pantalla de roles.
 */
return new class extends Migration
{
    private const PERMISO = 'reparaciones.ver_todas';

    private const REQUISITO = 'reparaciones.crear';

    public function up(): void
    {
        $this->modificarPermisos(function (array $permisos): array {
            if (! in_array(self::REQUISITO, $permisos, true)) {
                return $permisos;
            }

            if (in_array(self::PERMISO, $permisos, true)) {
                return $permisos;
            }

            $permisos[] = self::PERMISO;

            return $permisos;
        });
    }

    public function down(): void
    {
        $this->modificarPermisos(fn (array $permisos): array => array_values(
            array_filter($permisos, fn (string $p) => $p !== self::PERMISO)
        ));
    }

    /**
     * Recorre todos los roles de todas las empresas aplicando $transformar.
     *
     * Se usa DB::table y no el modelo Rol a propósito: Rol tiene el global
     * scope de empresa y en una migración no hay usuario autenticado, así que
     * el scope no restringiría, pero depender de eso sería frágil. Acá se
     * quiere explícitamente TODAS las empresas.
     */
    private function modificarPermisos(callable $transformar): void
    {
        $roles = DB::table('roles')->select('rol_id', 'permisos')->get();

        foreach ($roles as $rol) {
            $permisos = json_decode($rol->permisos ?? '[]', true);

            if (! is_array($permisos)) {
                continue;
            }

            $nuevos = $transformar($permisos);

            if ($nuevos === $permisos) {
                continue;
            }

            DB::table('roles')
                ->where('rol_id', $rol->rol_id)
                ->update(['permisos' => json_encode(array_values($nuevos))]);
        }
    }
};
