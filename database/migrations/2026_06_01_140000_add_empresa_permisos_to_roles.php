<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega los permisos `empresa.ver` y `empresa.editar` al rol administrador
 * de cada empresa (post-migracion multi-tenant).
 *
 * Estos permisos habilitan el modulo "Mi Empresa" donde el admin de la
 * empresa puede editar nombre, logo y datos de contacto (requerimiento
 * del profesor). Los permisos son granulares (Kendall: principio de menor
 * privilegio): vendedor y tecnico no los reciben.
 *
 * El cambio se aplica a todos los roles "administrador" existentes, sin
 * importar a que empresa pertenezcan, para que el modelo siga funcionando
 * cuando se agreguen nuevas empresas en el futuro.
 */
return new class extends Migration
{
    private array $nuevosPermisos = [
        'empresa.ver',
        'empresa.editar',
    ];

    public function up(): void
    {
        $rolesAdmin = DB::table('roles')->where('nombre', 'administrador')->get();

        foreach ($rolesAdmin as $rol) {
            $permisosActuales = json_decode($rol->permisos ?? '[]', true) ?: [];
            $permisosActualizados = array_values(array_unique(array_merge($permisosActuales, $this->nuevosPermisos)));

            DB::table('roles')
                ->where('rol_id', $rol->rol_id)
                ->update([
                    'permisos'   => json_encode($permisosActualizados),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $rolesAdmin = DB::table('roles')->where('nombre', 'administrador')->get();

        foreach ($rolesAdmin as $rol) {
            $permisosActuales = json_decode($rol->permisos ?? '[]', true) ?: [];
            $permisosFiltrados = array_values(array_filter(
                $permisosActuales,
                fn($p) => !in_array($p, $this->nuevosPermisos, true)
            ));

            DB::table('roles')
                ->where('rol_id', $rol->rol_id)
                ->update([
                    'permisos'   => json_encode($permisosFiltrados),
                    'updated_at' => now(),
                ]);
        }
    }
};
