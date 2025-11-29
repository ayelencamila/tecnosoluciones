<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstadoProducto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadoProductoController extends Controller
{
    public function index()
    {
        $estados = EstadoProducto::orderBy('nombre', 'asc')->paginate(10);
        return Inertia::render('Admin/EstadosProducto/Index', ['estados' => $estados]);
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:50|unique:estados_producto,nombre']);
        EstadoProducto::create($request->all());
        return back()->with('success', 'Estado creado.');
    }

    public function update(Request $request, $id)
    {
        $estado = EstadoProducto::findOrFail($id);
        $request->validate(['nombre' => 'required|string|max:50|unique:estados_producto,nombre,'.$id]);
        $estado->update($request->all());
        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy($id)
    {
        $estado = EstadoProducto::findOrFail($id);
        // Validación de integridad: No borrar si hay productos
        if ($estado->productos()->exists()) {
            return back()->withErrors(['error' => 'No se puede borrar: Hay productos en este estado.']);
        }
        $estado->delete();
        return back()->with('success', 'Estado eliminado.');
    }
}
