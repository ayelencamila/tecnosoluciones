<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Habilita `clientes.ver_datos_contacto` en los roles que atienden al
 * publico, y `proveedores.ver_datos_contacto` en los que compran.
 *
 * Por que se corrige el valor por defecto
 * ---------------------------------------
 * El enmascarado se habia dejado apagado para todos salvo quien puede
 * editar. Mirando la operatoria real de un local de reparaciones, ese
 * criterio esta invertido: la nota de recepcion que firma el cliente ya
 * incluye telefono y correo, y el personal del mostrador necesita esos
 * datos todo el tiempo, porque el cliente llama para preguntar por su saldo
 * o por su equipo y hay que devolverle el llamado.
 *
 * Ocultar el contacto tiene sentido en un taller grande, con muchos tecnicos
 * que nunca atienden publico. No lo tiene en un comercio de cuatro personas
 * donde todas atienden el mostrador.
 *
 * Asi que el mecanismo queda igual --el permiso existe y se puede quitar
 * desde la pantalla de roles-- pero pasa a estar PRENDIDO por defecto: una
 * empresa que quiera restringirlo lo destilda, en vez de tener que descubrir
 * que existe para poder trabajar.
 *
 * Los permisos se otorgan por lo que cada rol ya puede hacer, no por su
 * nombre, que cada empresa escribe como quiere.
 */
return new class extends Migration
{
    /** permiso a otorgar => permiso que indica que el rol lo necesita */
    private const OTORGAR = [
        'clientes.ver_datos_contacto' => 'clientes.ver',
        'proveedores.ver_datos_contacto' => 'proveedores.ver',
    ];

    public function up(): void
    {
        $this->modificarRoles(function (array $permisos): array {
            foreach (self::OTORGAR as $nuevo => $requisito) {
                if (in_array($requisito, $permisos, true) && ! in_array($nuevo, $permisos, true)) {
                    $permisos[] = $nuevo;
                }
            }

            return $permisos;
        });
    }

    public function down(): void
    {
        $this->modificarRoles(fn (array $permisos): array => array_values(
            array_diff($permisos, array_keys(self::OTORGAR))
        ));
    }

    /**
     * Se usa DB::table y no el modelo Rol porque Rol tiene el global scope de
     * empresa; aca se quieren explicitamente TODAS las empresas.
     */
    private function modificarRoles(callable $transformar): void
    {
        foreach (DB::table('roles')->select('rol_id', 'permisos')->get() as $rol) {
            $permisos = json_decode($rol->permisos ?? '[]', true);

            if (! is_array($permisos)) {
                continue;
            }

            $nuevos = $transformar($permisos);

            if ($nuevos === $permisos) {
                continue;
            }

            DB::table('roles')->where('rol_id', $rol->rol_id)
                ->update(['permisos' => json_encode(array_values($nuevos))]);
        }
    }
};
