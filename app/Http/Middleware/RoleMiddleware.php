<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por ROL (grueso), complementario a PermisoMiddleware.
 *
 * Uso en rutas: ->middleware('role:administrador')
 *               ->middleware('role:administrador,vendedor')  (basta uno)
 *
 * Resolucion, de mayor a menor autoridad:
 *  1. Super usuario de plataforma: pasa siempre.
 *  2. `administrador`: se resuelve por el flag `roles.es_admin_empresa`,
 *     NO por el nombre del rol. Asi el privilegio no depende de un texto
 *     editable desde la UI, y el administrador de cada empresa conserva su
 *     acceso aunque su rol se llame distinto.
 *  3. Cualquier otro rol (vendedor, tecnico, ...): se compara por nombre,
 *     como hasta ahora.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401); // no autenticado
        }

        if ($user->esSuperAdmin()) {
            return $next($request);
        }

        if (in_array('administrador', $roles, true) && $user->esAdminEmpresa()) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403); // no autorizado
        }

        return $next($request);
    }
}
