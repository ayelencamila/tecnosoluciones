<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de usuarios del sistema.
 * 
 * Implementa los casos de uso:
 * - CU Registrar Usuario
 * - CU Modificar Usuario Existente
 * - CU Desactivar/Activar Usuario (Eliminación Lógica)
 * - CU Bloquear/Desbloquear Usuario
 * - CU Restablecer Contraseña de Usuario
 */
class UserController extends Controller
{
    /**
     * Muestra el listado de usuarios con filtros.
     */
    public function index(Request $request): Response
    {
        $query = User::with('rol')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                });
            })
            ->when($request->rol_id, fn($q, $rol) => $q->where('rol_id', $rol))
            ->when($request->has('activo') && $request->activo !== '', 
                fn($q) => $q->where('activo', $request->boolean('activo'))
            )
            ->orderBy('name');

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $query->paginate(15)->withQueryString(),
            'roles' => Rol::where('activo', true)->orderBy('nombre')->get(),
            'filtros' => $request->only(['search', 'rol_id', 'activo']),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Usuarios/Create', [
            'roles' => Rol::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Almacena un nuevo usuario en la base de datos.
     * CU: Registrar Usuario
     */
    public function store(UserRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'password' => Hash::make($request->password),
                'rol_id' => $request->rol_id,
                'activo' => true,
            ]);

            // Registrar en auditoría
            Auditoria::registrar(
                Auditoria::ACCION_CREAR_USUARIO,
                'users',
                $user->id,
                null,
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'telefono' => $user->telefono,
                    'rol_id' => $user->rol_id,
                    'activo' => $user->activo,
                ],
                'Alta de nuevo usuario'
            );

            DB::commit();

            return redirect()->route('admin.usuarios.index')
                ->with('success', "Usuario '{$user->name}' registrado exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario de edición de un usuario.
     */
    public function edit(User $usuario): Response
    {
        return Inertia::render('Admin/Usuarios/Edit', [
            'usuario' => $usuario->load('rol'),
            'roles' => Rol::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Actualiza los datos de un usuario existente.
     * CU: Modificar Usuario Existente
     * Nota: No permite cambiar email ni contraseña desde aquí.
     */
    public function update(UserRequest $request, User $usuario): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $datosAnteriores = [
                'name' => $usuario->name,
                'telefono' => $usuario->telefono,
                'rol_id' => $usuario->rol_id,
            ];

            $usuario->update([
                'name' => $request->name,
                'telefono' => $request->telefono,
                'rol_id' => $request->rol_id,
            ]);

            // Registrar en auditoría
            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_USUARIO,
                'users',
                $usuario->id,
                $datosAnteriores,
                [
                    'name' => $usuario->name,
                    'telefono' => $usuario->telefono,
                    'rol_id' => $usuario->rol_id,
                ],
                'Modificación de datos de usuario'
            );

            DB::commit();

            return redirect()->route('admin.usuarios.index')
                ->with('success', "Usuario '{$usuario->name}' actualizado exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Activa o desactiva un usuario (eliminación lógica).
     * CU: Desactivar/Activar Usuario
     */
    public function toggleActivo(User $usuario): RedirectResponse
    {
        // Validar que no se desactive al último administrador activo
        if ($usuario->activo && $usuario->rol && $usuario->rol->nombre === 'administrador') {
            $adminsActivos = User::whereHas('rol', fn($q) => $q->where('nombre', 'administrador'))
                ->where('activo', true)
                ->count();
            
            if ($adminsActivos <= 1) {
                return back()->with('error', 'No se puede desactivar el último usuario con rol Administrador activo.');
            }
        }

        try {
            DB::beginTransaction();

            $estadoAnterior = $usuario->activo;
            $usuario->activo = !$usuario->activo;
            $usuario->save();

            Auditoria::registrar(
                $usuario->activo ? Auditoria::ACCION_ACTIVAR_USUARIO : Auditoria::ACCION_DESACTIVAR_USUARIO,
                'users',
                $usuario->id,
                ['activo' => $estadoAnterior],
                ['activo' => $usuario->activo],
                $usuario->activo ? 'Reactivación de usuario' : 'Desactivación de usuario'
            );

            DB::commit();

            $mensaje = $usuario->activo 
                ? "Usuario '{$usuario->name}' activado exitosamente."
                : "Usuario '{$usuario->name}' desactivado exitosamente.";

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al cambiar estado del usuario: ' . $e->getMessage());
        }
    }

    /**
     * Bloquea o desbloquea un usuario.
     * CU: Bloquear/Desbloquear Usuario
     */
    public function toggleBloqueo(User $usuario): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $estabaBloqueado = $usuario->estaBloqueado();

            if ($estabaBloqueado) {
                // Desbloquear
                $usuario->bloqueado_hasta = null;
                $mensaje = "Usuario '{$usuario->name}' desbloqueado exitosamente.";
                $accion = Auditoria::ACCION_DESBLOQUEAR_USUARIO;
            } else {
                // Bloquear indefinidamente (fecha muy lejana)
                $usuario->bloqueado_hasta = now()->addYears(100);
                $mensaje = "Usuario '{$usuario->name}' bloqueado exitosamente.";
                $accion = Auditoria::ACCION_BLOQUEAR_USUARIO;
            }

            $usuario->save();

            Auditoria::registrar(
                $accion,
                'users',
                $usuario->id,
                ['bloqueado_hasta' => $estabaBloqueado ? 'bloqueado' : null],
                ['bloqueado_hasta' => $usuario->bloqueado_hasta],
                $estabaBloqueado ? 'Desbloqueo manual de usuario' : 'Bloqueo manual de usuario'
            );

            DB::commit();

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al cambiar estado de bloqueo: ' . $e->getMessage());
        }
    }

    /**
     * Restablece la contraseña de un usuario.
     * CU: Restablecer Contraseña de Usuario
     */
    public function resetPassword(Request $request, User $usuario): RedirectResponse
    {
        $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'generar_temporal' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            if ($request->boolean('generar_temporal')) {
                // Generar contraseña temporal
                $nuevaPassword = Str::random(12);
            } else {
                $nuevaPassword = $request->password;
            }

            $usuario->password = Hash::make($nuevaPassword);
            $usuario->save();

            Auditoria::registrar(
                Auditoria::ACCION_RESTABLECER_PASSWORD,
                'users',
                $usuario->id,
                null,
                ['password_restablecida' => true],
                'Restablecimiento de contraseña por administrador'
            );

            DB::commit();

            if ($request->boolean('generar_temporal')) {
                return back()->with('success', "Contraseña temporal generada: {$nuevaPassword}");
            }

            return back()->with('success', "Contraseña de '{$usuario->name}' restablecida exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al restablecer contraseña: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el detalle de un usuario con su historial.
     */
    public function show(User $usuario): Response
    {
        $historial = Auditoria::where('tabla_afectada', 'users')
            ->where('registro_id', $usuario->id)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return Inertia::render('Admin/Usuarios/Show', [
            'usuario' => $usuario->load('rol'),
            'historial' => $historial,
        ]);
    }
}
