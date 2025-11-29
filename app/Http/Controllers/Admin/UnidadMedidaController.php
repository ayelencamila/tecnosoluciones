<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnidadMedidaController extends Controller
{
    public function index()
    {
        $unidades = UnidadMedida::orderBy('nombre', 'asc')->paginate(10);
        return Inertia::render('Admin/UnidadesMedida/Index', ['unidades' => $unidades]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:unidades_medida,nombre',
            'abreviatura' => 'required|string|max:10|unique:unidades_medida,abreviatura',
        ]);

        UnidadMedida::create($request->all());
        return back()->with('success', 'Unidad creada.');
    }

    public function update(Request $request, $id)
    {
        $unidad = UnidadMedida::findOrFail($id);
        
        $request->validate([
            'nombre' => 'required|string|max:50|unique:unidades_medida,nombre,' . $id,
            'abreviatura' => 'required|string|max:10|unique:unidades_medida,abreviatura,' . $id,
            'activo' => 'boolean'
        ]);

        $unidad->update($request->all());
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
