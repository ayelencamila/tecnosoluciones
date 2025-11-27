<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstadoReparacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadoReparacionController extends Controller
{
    public function index()
    {
        // Listamos los estados paginados
        $estados = EstadoReparacion::orderBy('estadoReparacionID', 'asc')->paginate(10);

        return Inertia::render('Admin/EstadosReparacion/Index', [
            'estados' => $estados
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombreEstado' => 'required|string|max:100|unique:estados_reparacion,nombreEstado',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombreEstado.unique' => 'Este estado ya existe.'
        ]);

        EstadoReparacion::create($request->only(['nombreEstado', 'descripcion']));

        return back()->with('success', 'Estado creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $estado = EstadoReparacion::findOrFail($id);

        $request->validate([
            'nombreEstado' => 'required|string|max:100|unique:estados_reparacion,nombreEstado,' . $id . ',estadoReparacionID',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $estado->update($request->only(['nombreEstado', 'descripcion']));

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy($id)
    {
        $estado = EstadoReparacion::findOrFail($id);

        // Regla de Negocio: No borrar estados que ya se usaron en reparaciones
        if ($estado->reparaciones()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar este estado porque hay reparaciones asociadas a él.']);
        }

        $estado->delete();

        return back()->with('success', 'Estado eliminado.');
    }
}