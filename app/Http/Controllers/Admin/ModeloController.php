<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modelo;
use App\Models\Marca;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModeloController extends Controller
{
    public function index()
    {
        // 1. Listamos modelos con su marca (para mostrar en la tabla "Samsung - S20")
        $modelos = Modelo::with('marca')
            ->orderBy('marca_id') // Agrupados por marca
            ->orderBy('nombre')
            ->paginate(10);

        // 2. Traemos las marcas activas para el select del formulario ("Elegir Marca")
        $marcas = Marca::where('activo', true)->orderBy('nombre')->get();

        return Inertia::render('Admin/Modelos/Index', [
            'modelos' => $modelos,
            'marcas' => $marcas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'marca_id' => 'required|exists:marcas,id',
            'nombre' => 'required|string|max:50',
        ]);

        // Validación extra: Que no exista "S20" dos veces en "Samsung"
        $existe = Modelo::where('marca_id', $request->marca_id)
            ->where('nombre', $request->nombre)
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Este modelo ya existe en la marca seleccionada.']);
        }

        Modelo::create($request->all());
        return back()->with('success', 'Modelo creado.');
    }

    public function update(Request $request, $id)
    {
        $modelo = Modelo::findOrFail($id);
        
        $request->validate([
            'marca_id' => 'required|exists:marcas,id',
            'nombre' => 'required|string|max:50',
            'activo' => 'boolean'
        ]);

        $modelo->update($request->all());
        return back()->with('success', 'Modelo actualizado.');
    }

    public function destroy($id)
    {
        $modelo = Modelo::findOrFail($id);
        $modelo->delete();
        return back()->with('success', 'Modelo eliminado.');
    }
}
