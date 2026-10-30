<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => fn () => $request->user()
                    ? $request->user()->only('id', 'name', 'email', 'role', 'foto_perfil')
                    : null,
                // La empresa compartida es la ACTIVA, no la del usuario: para
                // el super usuario son distintas (no tiene empresa propia y
                // trabaja sobre la que eligió).
                'empresa' => fn () => $this->empresaActiva(),
                'permisos' => fn () => $request->user()?->permisos ?? [],
                // Permite que la UI muestre el aviso de "estás dentro de la
                // empresa X" y el botón para volver al panel de plataforma.
                'es_super_admin' => fn () => (bool) $request->user()?->esSuperAdmin(),
                'es_admin_empresa' => fn () => (bool) $request->user()?->esAdminEmpresa(),
            ],

            // También pasamos mensajes Flash (éxito/error) para que funcionen las alertas
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],

            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    /**
     * Empresa (tenant) en la que se esta trabajando, o null si el super
     * usuario todavia no eligio ninguna.
     */
    private function empresaActiva(): ?array
    {
        $empresaId = TenantContext::empresaId();

        if ($empresaId === null) {
            return null;
        }

        return Empresa::find($empresaId)?->only('id', 'nombre', 'slug', 'logo');
    }
}