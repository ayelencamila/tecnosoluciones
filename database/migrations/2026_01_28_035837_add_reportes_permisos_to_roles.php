<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para agregar permisos de Reportes a los roles existentes
 */
return new class extends Migration
{
    /**
     * Nuevos permisos de reportes
     */
    private array $permisosReportes = [
        'reportes.ver',
        'reportes.ventas',
        'reportes.stock',
        'reportes.reparaciones',
        'reportes.compras',
        'reportes.exportar',
    ];

    public function up(): void
    {
        // Administrador: todos los permisos de reportes
        $this->agregarPermisosARol('administrador', $this->permisosReportes);
        
        // Vendedor: reportes de ventas y stock
        $this->agregarPermisosARol('vendedor', [
            'reportes.ver',
            'reportes.ventas',
            'reportes.stock',
            'reportes.exportar',
        ]);
        
        // Técnico: reportes de reparaciones
        $this->agregarPermisosARol('tecnico', [
            'reportes.ver',
            'reportes.reparaciones',
        ]);
    }

    private function agregarPermisosARol(string $nombreRol, array $nuevosPermisos): void
    {
        $rol = DB::table('roles')->where('nombre', $nombreRol)->first();
        
        if (!$rol) {
            return;
        }

        $permisosActuales = json_decode($rol->permisos ?? '[]', true);
        $permisosActualizados = array_unique(array_merge($permisosActuales, $nuevosPermisos));
        
        DB::table('roles')->where('nombre', $nombreRol)->update([
            'permisos' => json_encode(array_values($permisosActualizados)),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Revertir: quitar permisos de reportes de todos los roles
        $roles = DB::table('roles')->get();
        
        foreach ($roles as $rol) {
            $permisos = json_decode($rol->permisos ?? '[]', true);
            $permisosFiltrados = array_filter($permisos, fn($p) => !str_starts_with($p, 'reportes.'));
            
            DB::table('roles')->where('rol_id', $rol->rol_id)->update([
                'permisos' => json_encode(array_values($permisosFiltrados)),
                'updated_at' => now(),
            ]);
        }
    }
};