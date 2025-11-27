<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstadoCliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadoClienteController extends Controller
{
    public function index()
    {
        $estados = EstadoCliente::orderBy('estadoClienteID', 'asc')->paginate(10);
        return Inertia::render('Admin/EstadosCliente/Index', ['estados' => $estados]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombreEstado' => 'required|string|max:50|unique:estados_cliente,nombreEstado',
            'descripcion' => 'nullable|string|max:255',
        ]);

        EstadoCliente::create($request->all());
        return back()->with('success', 'Estado creado.');
    }

    public function update(Request $request, $id)
    {
        $estado = EstadoCliente::findOrFail($id);
        $request->validate([
            'nombreEstado' => 'required|string|max:50|unique:estados_cliente,nombreEstado,' . $id . ',estadoClienteID',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $estado->update($request->all());
        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy($id)
    {
        $estado = EstadoCliente::findOrFail($id);
        
        // Protección de estados críticos (Activo/Inactivo) para no romper lógica del sistema
        if (in_array($estado->nombreEstado, ['Activo', 'Inactivo'])) {
            return back()->withErrors(['error' => 'Los estados del sistema (Activo/Inactivo) no pueden eliminarse.']);
        }

        if ($estado->clientes()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: Hay clientes en este estado.']);
        }
        
        $estado->delete();
        return back()->with('success', 'Estado eliminado.');
    }
}
