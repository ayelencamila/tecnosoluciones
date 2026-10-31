<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use App\Exceptions\LimiteDePlanExcedido;
use App\Services\Planes\ControlDeLimites;
use App\Support\TenantContext;
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
     * Garantiza que el usuario objetivo pertenezca a la empresa activa.
     *
     * A diferencia del resto de los modelos del sistema, `User` NO lleva el
     * Global Scope de empresa: resolverlo requeriria consultar al usuario
     * autenticado, que es justamente lo que se esta resolviendo, y produciria
     * una recursion infinita durante el login. Por eso el aislamiento de
     * `users` se hace explicito aca, en el unico controller que los gestiona.
     *
     * Devuelve 404 y no 403 a proposito: un 403 confirmaria que el usuario
     * existe en otra empresa, filtrando informacion entre tenants.
     *
     * Como el super usuario de plataforma tiene `empresa_id` NULL, esta misma
     * comprobacion impide que el administrador de una empresa lo modifique,
     * lo bloquee o le restablezca la contraseña.
     */
    protected function autorizarEmpresa(User $usuario): void
    {
        abort_if($usuario->empresa_id !== TenantContext::empresaId(), 404);
    }

    /**
     * Roles asignables: solo los de la empresa activa y activos.
     * El Global Scope de `Rol` ya restringe por empresa; el filtro explicito
     * se mantiene por legibilidad del caso de uso.
     */
    protected function rolesAsignables()
    {
        return Rol::where('activo', true)->orderBy('nombre')->get();
    }

    /**
     * Muestra el listado de usuarios con filtros.
     */
    public function index(Request $request): Response
    {
        $query = User::with('rol')
            ->where('empresa_id', TenantContext::empresaId())
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
            'roles' => $this->rolesAsignables(),
            'filtros' => $request->only(['search', 'rol_id', 'activo']),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Usuarios/Create', [
            'roles' => $this->rolesAsignables(),
        ]);
    }

    /**
     * Almacena un nuevo usuario en la base de datos.
     * CU: Registrar Usuario
     */
    public function store(UserRequest $request): RedirectResponse
    {
        /*
         * Límite DURO del plan: la cantidad de usuarios es capacidad
         * contratada, así que acá sí corresponde bloquear. Dar de alta un
         * usuario es una acción administrativa y planificada: quien la
         * intenta ve el motivo y el sistema sigue funcionando.
         *
         * Va ANTES de la transacción: no tiene sentido abrirla para
         * cancelarla enseguida.
         */
        try {
            ControlDeLimites::paraTenantActivo()->exigirCupo('usuarios');
        } catch (LimiteDePlanExcedido $e) {
            return back()->withInput()->withErrors(['limite_plan' => $e->getMessage()]);
        }

        try {
            DB::beginTransaction();

            // empresa_id explicito: `users` no tiene Global Scope, y confiar en
            // el DEFAULT 1 de la columna haria que el administrador de la
            // empresa 2 creara usuarios dentro de la empresa 1.
            $user = User::create([
                'empresa_id' => TenantContext::empresaId(),
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
        $this->autorizarEmpresa($usuario);

        return Inertia::render('Admin/Usuarios/Edit', [
            'usuario' => $usuario->load('rol'),
            'roles' => $this->rolesAsignables(),
        ]);
    }

    /**
     * Actualiza los datos de un usuario existente.
     * CU: Modificar Usuario Existente
     * Nota: No permite cambiar email ni contraseña desde aquí.
     */
    public function update(UserRequest $request, User $usuario): RedirectResponse
    {
        $this->autorizarEmpresa($usuario);

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
        $this->autorizarEmpresa($usuario);

        // Validar que no se desactive al último administrador activo DE ESTA
        // EMPRESA. Sin el filtro por empresa, los administradores de otras
        // empresas contarian como respaldo y una empresa podria quedarse sin
        // ningun administrador, es decir sin forma de gestionar su sistema.
        if ($usuario->activo && $usuario->esAdminEmpresa()) {
            $adminsActivos = User::where('empresa_id', TenantContext::empresaId())
                ->whereHas('rol', fn($q) => $q->where('es_admin_empresa', true))
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
        $this->autorizarEmpresa($usuario);

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
        $this->autorizarEmpresa($usuario);

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
        $this->autorizarEmpresa($usuario);

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

    /**
     * Busca usuarios/técnicos para autocompletado.
     * Filtra por nombre o email, opcionalmente por rol.
     */
    public function buscar(Request $request)
    {
        $query = $request->get('q');
        $soloTecnicos = $request->boolean('tecnicos', false);
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $usuarios = User::with('rol')
            ->where('empresa_id', TenantContext::empresaId())
            ->where('activo', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->when($soloTecnicos, function ($q) {
                $q->whereHas('rol', fn($r) => $r->whereIn('nombre', ['tecnico', 'administrador']));
            })
            ->limit(15)
            ->get(['id', 'name', 'email', 'rol_id']);

        return response()->json($usuarios);
    }
}
