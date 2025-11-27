<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProvinciaController extends Controller
{
    public function index()
    {
        // Ordenamos por nombre para facilitar la búsqueda visual
        $provincias = Provincia::orderBy('nombre', 'asc')->paginate(10);

        return Inertia::render('Admin/Provincias/Index', [
            'provincias' => $provincias
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:provincias,nombre',
        ], [
            'nombre.unique' => 'Esta provincia ya está registrada.'
        ]);

        Provincia::create($request->only('nombre'));

        return back()->with('success', 'Provincia agregada correctamente.');
    }

    public function update(Request $request, $id)
    {
        // Buscamos por la PK personalizada 'provinciaID'
        $provincia = Provincia::findOrFail($id);

        $request->validate([
            // Validamos unique ignorando el ID actual
            'nombre' => 'required|string|max:100|unique:provincias,nombre,' . $id . ',provinciaID',
        ]);

        $provincia->update($request->only('nombre'));

        return back()->with('success', 'Provincia actualizada.');
    }

    public function destroy($id)
    {
        $provincia = Provincia::findOrFail($id);

        // Regla de Integridad: No borrar si tiene localidades hijas
        if ($provincia->localidades()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: Esta provincia tiene localidades asociadas.']);
        }

        $provincia->delete(); // Soft Delete

        return back()->with('success', 'Provincia eliminada.');
    }
}
