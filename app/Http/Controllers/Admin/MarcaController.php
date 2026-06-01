<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MarcaController extends Controller
{
    public function index()
    {
        $marcas = Marca::orderBy('nombre', 'asc')->paginate(10);
        return Inertia::render('Admin/Marcas/Index', ['marcas' => $marcas]);
    }

    public function store(Request $request)
    {
        // Multi-tenant: unicidad por empresa (Elmasri: scope de particion tenant).
        $request->validate([
            'nombre' => [
                'required', 'string', 'max:50',
                Rule::unique('marcas', 'nombre')
                    ->where('empresa_id', auth()->user()->empresa_id),
            ],
        ]);
        Marca::create($request->all());
        return back()->with('success', 'Marca creada.');
    }

    public function update(Request $request, Marca $marca)
    {
        // Multi-tenant: unicidad por empresa, ignorando este registro.
        $request->validate([
            'nombre' => [
                'required', 'string', 'max:50',
                Rule::unique('marcas', 'nombre')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($marca->id),
            ],
            'activo' => 'boolean'
        ]);
        $marca->update($request->all());
        return back()->with('success', 'Marca actualizada.');
    }

    public function destroy(Marca $marca)
    {
        // Validar si tiene modelos asociados antes de borrar (Integridad)
        // if ($marca->modelos()->exists()) ... (Lo activaremos al crear Modelos)
        
        $marca->delete();
        return back()->with('success', 'Marca eliminada.');
    }
}
