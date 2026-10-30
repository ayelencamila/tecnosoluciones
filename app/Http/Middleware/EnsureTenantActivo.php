<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que toda ruta de negocio se ejecute con un tenant resuelto y
 * habilitado. Es la contraparte necesaria del Global Scope de empresa.
 *
 * El scope no restringe cuando no hay tenant resuelto, porque los procesos
 * de sistema (jobs, comandos, seeders) deben poder recorrer todas las
 * empresas. Ese permiso no puede extenderse a una peticion web: si un
 * super usuario entrara a /clientes sin haber elegido empresa, veria los
 * clientes de todos los tenants mezclados. Este middleware cierra ese hueco
 * exigiendo tenant en la puerta de entrada.
 *
 * Ademas hace cumplir `empresas.activa`, que hasta ahora existia en la base
 * pero no se verificaba en ningun lado. Es el control comercial del modelo
 * SaaS: suspender una empresa debe cortar el acceso de sus usuarios, incluso
 * los que ya tengan la sesion abierta.
 */
class EnsureTenantActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Super usuario que todavia no eligio empresa: no se le niega el
        // acceso, se lo manda a elegir una (patron "log in as").
        if ($user->esSuperAdmin() && ! TenantContext::tieneEmpresa()) {
            return redirect()
                ->route('super.empresas.index')
                ->with('error', 'Elegí una empresa para trabajar dentro de ella.');
        }

        $empresaId = TenantContext::empresaId();

        if ($empresaId === null) {
            abort(403, 'Tu usuario no tiene una empresa asignada.');
        }

        $empresa = Empresa::find($empresaId);

        // Empresa inexistente, dada de baja o suspendida. El super usuario si
        // puede entrar: necesita acceso para reactivarla o revisar sus datos.
        if (! $empresa || (! $empresa->activa && ! $user->esSuperAdmin())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'El acceso de su empresa se encuentra suspendido. Contacte al proveedor del sistema.']);
        }

        return $next($request);
    }
}
