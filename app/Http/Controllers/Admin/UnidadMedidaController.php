<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnidadMedida;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Inertia\Inertia;

/**
 * Unidades de medida: catalogo propio de cada empresa, porque depende de lo
 * que cada una venda. Ver MedioPagoController para el detalle de por que la
 * regla `unique` se acota a mano mientras el resto lo cubre el Global Scope.
 */
class UnidadMedidaController extends Controller
{
    public function index()
    {
        $unidades = UnidadMedida::orderBy('nombre', 'asc')->paginate(10);
        return Inertia::render('Admin/UnidadesMedida/Index', ['unidades' => $unidades]);
    }

    private function reglaUnica(string $columna, ?int $ignorarId = null): Unique
    {
        $regla = Rule::unique('unidades_medida', $columna)
            ->where('empresa_id', TenantContext::empresaId());

        return $ignorarId ? $regla->ignore($ignorarId) : $regla;
    }

    public function store(Request $request)
    {
        $validado = $request->validate([
            'nombre' => ['required', 'string', 'max:50', $this->reglaUnica('nombre')],
            'abreviatura' => ['required', 'string', 'max:10', $this->reglaUnica('abreviatura')],
        ]);

        UnidadMedida::create($validado);
        return back()->with('success', 'Unidad creada.');
    }

    public function update(Request $request, $id)
    {
        $unidad = UnidadMedida::findOrFail($id);

        $validado = $request->validate([
            'nombre' => ['required', 'string', 'max:50', $this->reglaUnica('nombre', (int) $id)],
            'abreviatura' => ['required', 'string', 'max:10', $this->reglaUnica('abreviatura', (int) $id)],
            'activo' => 'boolean',
        ]);

        $unidad->update($validado);
        return back()->with('success', 'Unidad actualizada.');
    }

    public function destroy($id)
    {
        $unidad = UnidadMedida::findOrFail($id);
        // Aquí podrías validar si hay productos usando esta unidad antes de borrar
        $unidad->delete();
        return back()->with('success', 'Unidad eliminada.');
    }
}
