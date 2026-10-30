<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoCliente;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Inertia\Inertia;

/**
 * Tipos de cliente: catalogo propio de cada empresa (su segmentacion
 * comercial). Ver MedioPagoController para el detalle de por que la regla
 * `unique` se acota a mano mientras el resto lo cubre el Global Scope.
 */
class TipoClienteController extends Controller
{
    public function index()
    {
        $tipos = TipoCliente::orderBy('tipoClienteID', 'asc')->paginate(10);
        return Inertia::render('Admin/TiposCliente/Index', ['tipos' => $tipos]);
    }

    private function reglaNombreUnico(?int $ignorarId = null): Unique
    {
        $regla = Rule::unique('tipos_cliente', 'nombreTipo')
            ->where('empresa_id', TenantContext::empresaId());

        return $ignorarId ? $regla->ignore($ignorarId, 'tipoClienteID') : $regla;
    }

    public function store(Request $request)
    {
        $validado = $request->validate([
            'nombreTipo' => ['required', 'string', 'max:50', $this->reglaNombreUnico()],
            'descripcion' => 'nullable|string|max:255',
        ]);

        TipoCliente::create($validado);
        return back()->with('success', 'Tipo de cliente creado.');
    }

    public function update(Request $request, $id)
    {
        $tipo = TipoCliente::findOrFail($id);

        $validado = $request->validate([
            'nombreTipo' => ['required', 'string', 'max:50', $this->reglaNombreUnico((int) $id)],
            'descripcion' => 'nullable|string|max:255',
        ]);

        $tipo->update($validado);
        return back()->with('success', 'Tipo de cliente actualizado.');
    }

    public function destroy($id)
    {
        $tipo = TipoCliente::findOrFail($id);
        // Regla: No borrar si hay clientes usándolo
        if ($tipo->clientes()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: Hay clientes de este tipo.']);
        }
        $tipo->delete();
        return back()->with('success', 'Tipo de cliente eliminado.');
    }
}
