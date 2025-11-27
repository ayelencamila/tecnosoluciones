<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Localidad;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocalidadController extends Controller
{
    public function index()
    {
        // 1. Traemos las localidades con su provincia (Eager Loading para optimizar)
        $localidades = Localidad::with('provincia')
            ->orderBy('nombre', 'asc')
            ->paginate(10);

        // 2. Traemos TODAS las provincias para el Select del formulario
        $provincias = Provincia::orderBy('nombre', 'asc')->get(['provinciaID', 'nombre']);

        return Inertia::render('Admin/Localidades/Index', [
            'localidades' => $localidades,
            'provincias' => $provincias // <--- ¡Importante! Enviamos esto a la vista
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'provinciaID' => 'required|exists:provincias,provinciaID', // Validamos que la provincia exista
        ]);

        // Verificamos duplicados manualmente (Nombre + Provincia)
        // Ej: Puede haber "San Martín" en BsAs y en Mendoza, pero no dos en la misma provincia.
        $existe = Localidad::where('nombre', $request->nombre)
            ->where('provinciaID', $request->provinciaID)
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Esta localidad ya existe en la provincia seleccionada.']);
        }

        Localidad::create($request->only(['nombre', 'provinciaID']));

        return back()->with('success', 'Localidad creada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $localidad = Localidad::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100',
            'provinciaID' => 'required|exists:provincias,provinciaID',
        ]);

        // Validación de duplicados al editar (excluyendo la propia)
        $existe = Localidad::where('nombre', $request->nombre)
            ->where('provinciaID', $request->provinciaID)
            ->where('localidadID', '!=', $id)
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe otra localidad con este nombre en esa provincia.']);
        }

        $localidad->update($request->only(['nombre', 'provinciaID']));

        return back()->with('success', 'Localidad actualizada.');
    }

    public function destroy($id)
    {
        $localidad = Localidad::findOrFail($id);

        // Regla de Integridad: No borrar si tiene direcciones/clientes
        if ($localidad->direcciones()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: Hay clientes o direcciones usando esta localidad.']);
        }

        $localidad->delete();

        return back()->with('success', 'Localidad eliminada.');
    }
}
