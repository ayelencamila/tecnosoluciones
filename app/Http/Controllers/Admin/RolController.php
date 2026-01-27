<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RolRequest;
use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de roles del sistema.
 * 
 * Implementa los casos de uso:
 * - CU Registrar nuevo rol
 * - CU Modificar rol
 * - CU Dar de baja un Rol
 */
class RolController extends Controller
{
    /**
     * Obtiene los permisos disponibles desde la configuración.
     * Transforma el formato del config al formato esperado por la UI.
     */
    protected function getPermisosDisponibles(): array
    {
        $modulos = config('permisos.modulos', []);
        $resultado = [];
        
        foreach ($modulos as $modulo => $config) {
            $resultado[$config['label'] ?? $modulo] = $config['permisos'] ?? [];
        }
        
        return $resultado;
    }

    /**
     * Muestra el listado de roles.
     */
    public function index(): Response
    {
        $roles = Rol::withCount('users')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo rol.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Create', [
            'permisosDisponibles' => $this->getPermisosDisponibles(),
        ]);
    }

    /**
     * Almacena un nuevo rol en la base de datos.
     * CU: Registrar nuevo rol
     */
    public function store(RolRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $rol = Rol::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'permisos' => $request->permisos ?? [],
                'activo' => true,
            ]);

            Auditoria::registrar(
                Auditoria::ACCION_CREAR_ROL,
                'roles',
                $rol->rol_id,
                null,
                [
                    'nombre' => $rol->nombre,
                    'descripcion' => $rol->descripcion,
                    'permisos' => $rol->permisos,
                ],
                'Creación de nuevo rol'
            );

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', "Rol '{$rol->nombre}' creado exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el rol: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario de edición de un rol.
     */
    public function edit(Rol $role): Response
    {
        return Inertia::render('Admin/Roles/Edit', [
            'rol' => $role->loadCount('users'),
            'permisosDisponibles' => $this->getPermisosDisponibles(),
        ]);
    }

    /**
     * Actualiza un rol existente.
     * CU: Modificar rol
     */
    public function update(RolRequest $request, Rol $role): RedirectResponse
    {
        // Proteger el rol administrador de modificaciones críticas
        if ($role->nombre === 'administrador' && $request->nombre !== 'administrador') {
            return back()->with('error', 'No se puede cambiar el nombre del rol Administrador.');
        }

        try {
            DB::beginTransaction();

            $datosAnteriores = [
                'nombre' => $role->nombre,
                'descripcion' => $role->descripcion,
                'permisos' => $role->permisos,
            ];

            $role->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'permisos' => $request->permisos ?? [],
            ]);

            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_ROL,
                'roles',
                $role->rol_id,
                $datosAnteriores,
                [
                    'nombre' => $role->nombre,
                    'descripcion' => $role->descripcion,
                    'permisos' => $role->permisos,
                ],
                'Modificación de rol'
            );

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', "Rol '{$role->nombre}' actualizado exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el rol: ' . $e->getMessage());
        }
    }

    /**
     * Elimina un rol del sistema.
     * CU: Dar de baja un Rol
     */
    public function destroy(Request $request, Rol $role): RedirectResponse
    {
        // No permitir eliminar el rol administrador
        if ($role->nombre === 'administrador') {
            return back()->with('error', 'El rol Administrador no puede ser eliminado por seguridad.');
        }

        // Verificar si tiene usuarios asociados
        $usuariosAsociados = $role->users()->count();
        
        if ($usuariosAsociados > 0) {
            // Si tiene usuarios, necesitamos saber qué hacer con ellos
            $request->validate([
                'accion_usuarios' => ['required', 'in:reasignar,deshabilitar'],
                'rol_destino_id' => ['required_if:accion_usuarios,reasignar', 'exists:roles,rol_id'],
            ], [
                'accion_usuarios.required' => 'Debe indicar qué hacer con los usuarios asociados.',
                'rol_destino_id.required_if' => 'Debe seleccionar el rol de destino para reasignar usuarios.',
            ]);
        }

        try {
            DB::beginTransaction();

            // Gestionar usuarios asociados
            if ($usuariosAsociados > 0) {
                if ($request->accion_usuarios === 'reasignar') {
                    User::where('rol_id', $role->rol_id)
                        ->update(['rol_id' => $request->rol_destino_id]);
                } else {
                    User::where('rol_id', $role->rol_id)
                        ->update(['activo' => false]);
                }
            }

            $nombreRol = $role->nombre;

            Auditoria::registrar(
                Auditoria::ACCION_ELIMINAR_ROL,
                'roles',
                $role->rol_id,
                [
                    'nombre' => $role->nombre,
                    'descripcion' => $role->descripcion,
                    'permisos' => $role->permisos,
                    'usuarios_afectados' => $usuariosAsociados,
                ],
                null,
                'Eliminación de rol',
                $request->accion_usuarios ?? 'sin_usuarios'
            );

            $role->delete();

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', "Rol '{$nombreRol}' eliminado exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    /**
     * Activa o desactiva un rol.
     */
    public function toggleActivo(Rol $role): RedirectResponse
    {
        if ($role->nombre === 'administrador') {
            return back()->with('error', 'El rol Administrador no puede ser desactivado.');
        }

        try {
            DB::beginTransaction();

            $estadoAnterior = $role->activo;
            $role->activo = !$role->activo;
            $role->save();

            Auditoria::registrar(
                $role->activo ? Auditoria::ACCION_ACTIVAR_ROL : Auditoria::ACCION_DESACTIVAR_ROL,
                'roles',
                $role->rol_id,
                ['activo' => $estadoAnterior],
                ['activo' => $role->activo],
                $role->activo ? 'Reactivación de rol' : 'Desactivación de rol'
            );

            DB::commit();

            $mensaje = $role->activo 
                ? "Rol '{$role->nombre}' activado exitosamente."
                : "Rol '{$role->nombre}' desactivado exitosamente.";

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al cambiar estado del rol: ' . $e->getMessage());
        }
    }
}
