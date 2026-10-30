<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta al nivel de PLATAFORMA.
 *
 * Protege el panel del operador del sistema (alta, suspension y acceso a
 * empresas). Es deliberadamente un control binario y no un permiso: los
 * permisos son relativos a una empresa y estas acciones estan por encima de
 * cualquier empresa, asi que no hay forma coherente de expresarlas como tal.
 *
 * Devuelve 404 y no 403 para que el panel de plataforma sea indistinguible
 * de una ruta inexistente ante un usuario de empresa: no conviene revelarle
 * a los clientes que existe un nivel de acceso superior al suyo.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->esSuperAdmin()) {
            abort(404);
        }

        return $next($request);
    }
}
