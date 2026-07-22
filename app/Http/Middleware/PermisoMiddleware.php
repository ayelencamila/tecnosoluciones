<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por PERMISO (granular), complementario a RoleMiddleware.
 *
 * Uso en rutas: ->middleware('permiso:reparaciones.ver')
 *               ->middleware('permiso:reparaciones.editar,reparaciones.cambiar_estado')  (basta uno)
 *
 * El administrador siempre pasa (bypass total, resuelto en User::hasAnyPermission).
 * Así el enforcement de backend queda alineado con el menú (AppLayout::can()).
 */
class PermisoMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401); // no autenticado
        }

        if (empty($permisos) || $user->hasAnyPermission($permisos)) {
            return $next($request);
        }

        abort(403); // autenticado pero sin el permiso requerido
    }
}
