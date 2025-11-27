<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoCliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TipoClienteController extends Controller
{
    public function index()
    {
        $tipos = TipoCliente::orderBy('tipoClienteID', 'asc')->paginate(10);
        return Inertia::render('Admin/TiposCliente/Index', ['tipos' => $tipos]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombreTipo' => 'required|string|max:50|unique:tipos_cliente,nombreTipo',
            'descripcion' => 'nullable|string|max:255',
        ]);

        TipoCliente::create($request->all());
        return back()->with('success', 'Tipo de cliente creado.');
    }

    public function update(Request $request, $id)
    {
        $tipo = TipoCliente::findOrFail($id);
        $request->validate([
            'nombreTipo' => 'required|string|max:50|unique:tipos_cliente,nombreTipo,' . $id . ',tipoClienteID',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $tipo->update($request->all());
        return back()->with('success', 'Tipo de cliente actualizado.');
    }

    public function destroy($id)
    {
        $tipo = TipoCliente::findOrFail($id);
        // Regla: No borrar si hay clientes usándolo
        if ($tipo->clientes()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: Hay clientes de este tipo.']);
        }
        $tipo->delete();
        return back()->with('success', 'Tipo de cliente eliminado.');
    }
}
