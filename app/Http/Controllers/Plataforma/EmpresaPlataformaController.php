<?php

namespace App\Http\Controllers\Plataforma;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plataforma\AltaEmpresaRequest;
use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\User;
use App\Services\Plataforma\AltaEmpresaService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel del operador de la plataforma: gestion de los clientes del sistema.
 *
 * Es el unico lugar de la aplicacion que trabaja POR ENCIMA del tenant, y
 * por eso sus rutas quedan fuera del middleware `tenant`. Todas las lecturas
 * de datos de empresa se hacen con TenantContext::sinRestriccion(), de forma
 * explicita, para que quede claro en el codigo donde se cruza la frontera de
 * aislamiento y donde no.
 *
 * El flujo de trabajo es el de "entrar como" (Shopify, Odoo, Intercom): el
 * operador no ve los datos de todas las empresas mezclados, elige una y
 * trabaja dentro de su contexto reutilizando el sistema completo.
 */
class EmpresaPlataformaController extends Controller
{
    /**
     * Listado de empresas con sus metricas basicas.
     */
    public function index(): Response
    {
        $empresas = TenantContext::sinRestriccion(fn () => Empresa::query()
            ->withCount([
                'users as usuarios_total',
                'users as usuarios_activos' => fn ($q) => $q->where('activo', true),
            ])
            ->orderByDesc('activa')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Empresa $empresa) => [
                'id'                => $empresa->id,
                'nombre'            => $empresa->nombre,
                'slug'              => $empresa->slug,
                'cuit'              => $empresa->cuit,
                'email'             => $empresa->email,
                'activa'            => $empresa->activa,
                'usuarios_total'    => $empresa->usuarios_total,
                'usuarios_activos'  => $empresa->usuarios_activos,
                'creada'            => $empresa->created_at?->format('d/m/Y'),
            ]));

        return Inertia::render('Plataforma/Empresas/Index', [
            'empresas' => $empresas,
            'empresaActiva' => TenantContext::empresaId(),
        ]);
    }

    /**
     * Alta de un cliente nuevo: empresa + rol administrador + usuario admin.
     */
    public function store(AltaEmpresaRequest $request, AltaEmpresaService $servicio): RedirectResponse
    {
        try {
            $empresa = $servicio->ejecutar(
                datosEmpresa: $request->only(['nombre', 'cuit', 'telefono', 'email', 'direccion']),
                datosAdmin: [
                    'name'     => $request->input('admin_name'),
                    'email'    => $request->input('admin_email'),
                    'password' => $request->input('admin_password'),
                    'telefono' => $request->input('admin_telefono'),
                ],
            );

            return redirect()
                ->route('super.empresas.index')
                ->with('success', "Empresa '{$empresa->nombre}' creada con su administrador.");
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'No se pudo crear la empresa: '.$e->getMessage());
        }
    }

    /**
     * Suspende o reactiva el acceso de una empresa.
     *
     * Es el control comercial del modelo SaaS: suspender corta el acceso de
     * todos los usuarios de esa empresa, incluso los que ya tengan sesion
     * abierta, porque el middleware `tenant` lo verifica en cada peticion.
     * Los datos no se tocan: la suspension es reversible.
     */
    public function toggleActiva(Empresa $empresa): RedirectResponse
    {
        $estadoAnterior = $empresa->activa;
        $empresa->activa = ! $empresa->activa;
        $empresa->save();

        // Si se suspende la empresa en la que el operador estaba trabajando,
        // se lo saca de ese contexto para no dejarlo dentro de un tenant
        // deshabilitado.
        if (! $empresa->activa && TenantContext::empresaId() === $empresa->id) {
            TenantContext::salir();
        }

        Auditoria::registrar(
            $empresa->activa ? Auditoria::ACCION_REACTIVAR_EMPRESA : Auditoria::ACCION_SUSPENDER_EMPRESA,
            'empresas',
            $empresa->id,
            ['activa' => $estadoAnterior],
            ['activa' => $empresa->activa],
            $empresa->activa
                ? "Reactivación del acceso de '{$empresa->nombre}'"
                : "Suspensión del acceso de '{$empresa->nombre}'"
        );

        return back()->with('success', $empresa->activa
            ? "Acceso de '{$empresa->nombre}' reactivado."
            : "Acceso de '{$empresa->nombre}' suspendido.");
    }

    /**
     * Entra al contexto de una empresa para operar dentro de ella.
     */
    public function entrar(Empresa $empresa): RedirectResponse
    {
        TenantContext::activar($empresa->id);

        // Se audita dentro de la empresa visitada: su administrador tiene
        // derecho a ver en su propia bitacora que el operador de la
        // plataforma entro a su sistema, y cuando.
        Auditoria::registrar(
            Auditoria::ACCION_INGRESO_PLATAFORMA,
            'empresas',
            $empresa->id,
            null,
            ['empresa' => $empresa->nombre],
            'El operador de la plataforma ingresó al contexto de esta empresa'
        );

        return redirect()
            ->route('dashboard')
            ->with('success', "Trabajando dentro de '{$empresa->nombre}'.");
    }

    /**
     * Sale del contexto de la empresa y vuelve al nivel de plataforma.
     */
    public function salir(): RedirectResponse
    {
        TenantContext::salir();

        return redirect()
            ->route('super.empresas.index')
            ->with('success', 'Saliste del contexto de la empresa.');
    }

    /**
     * Detalle de una empresa, con sus usuarios y su rol administrador.
     */
    public function show(Empresa $empresa): Response
    {
        $usuarios = TenantContext::paraEmpresa($empresa->id, fn () => User::with('rol')
            ->where('empresa_id', $empresa->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $usuario) => [
                'id'       => $usuario->id,
                'name'     => $usuario->name,
                'email'    => $usuario->email,
                'rol'      => $usuario->rol?->nombre,
                'es_admin' => (bool) $usuario->rol?->es_admin_empresa,
                'activo'   => $usuario->activo,
            ]));

        return Inertia::render('Plataforma/Empresas/Show', [
            'empresa' => [
                'id'        => $empresa->id,
                'nombre'    => $empresa->nombre,
                'slug'      => $empresa->slug,
                'cuit'      => $empresa->cuit,
                'telefono'  => $empresa->telefono,
                'email'     => $empresa->email,
                'direccion' => $empresa->direccion,
                'activa'    => $empresa->activa,
                'creada'    => $empresa->created_at?->format('d/m/Y H:i'),
            ],
            'usuarios' => $usuarios,
        ]);
    }
}
