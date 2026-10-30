<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedioPago;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Inertia\Inertia;

/**
 * Medios de pago: catalogo propio de cada empresa.
 *
 * El Global Scope de MedioPago acota los listados y el route-model binding,
 * de modo que `findOrFail` de un medio de otra empresa devuelve 404. Lo que
 * el scope no cubre son las reglas `unique`, que trabajan sobre el query
 * builder: por eso la unicidad del nombre se acota explicitamente a la
 * empresa activa, igual que el UNIQUE (empresa_id, nombre) de la tabla.
 */
class MedioPagoController extends Controller
{
    public function index()
    {
        $medios = MedioPago::orderBy('medioPagoID', 'asc')->paginate(10);
        return Inertia::render('Admin/MediosPago/Index', ['medios' => $medios]);
    }

    /**
     * Unicidad del nombre DENTRO de la empresa: dos empresas distintas pueden
     * tener cada una su "Efectivo".
     */
    private function reglaNombreUnico(?int $ignorarId = null): Unique
    {
        $regla = Rule::unique('medios_pago', 'nombre')
            ->where('empresa_id', TenantContext::empresaId());

        return $ignorarId ? $regla->ignore($ignorarId, 'medioPagoID') : $regla;
    }

    public function store(Request $request)
    {
        $validado = $request->validate([
            'nombre' => ['required', 'string', 'max:50', $this->reglaNombreUnico()],
            'recargo_porcentaje' => 'required|numeric|min:0|max:100',
            'instrucciones' => 'nullable|string|max:500',
        ]);

        MedioPago::create($validado);
        return back()->with('success', 'Medio de pago creado.');
    }

    public function update(Request $request, $id)
    {
        $medio = MedioPago::findOrFail($id);

        $validado = $request->validate([
            'nombre' => ['required', 'string', 'max:50', $this->reglaNombreUnico((int) $id)],
            'recargo_porcentaje' => 'required|numeric|min:0|max:100',
            'instrucciones' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ]);

        $medio->update($validado);
        return back()->with('success', 'Medio de pago actualizado.');
    }

    public function destroy($id)
    {
        // Podrías validar si ya se usó en pagos antes de borrar (recomendado)
        $medio = MedioPago::findOrFail($id);
        $medio->delete();
        return back()->with('success', 'Medio de pago eliminado.');
    }
}
