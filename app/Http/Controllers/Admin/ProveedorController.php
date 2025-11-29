<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::orderBy('razon_social', 'asc')->paginate(10);
        return Inertia::render('Admin/Proveedores/Index', ['proveedores' => $proveedores]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'razon_social' => 'required|string|max:100',
            'cuit' => 'required|string|max:20|unique:proveedores,cuit',
            'email' => 'nullable|email',
            'telefono' => 'nullable|string|max:20'
        ]);
        Proveedor::create($request->all());
        return back()->with('success', 'Proveedor creado.');
    }

    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $request->validate([
            'razon_social' => 'required|string|max:100',
            'cuit' => 'required|string|max:20|unique:proveedores,cuit,'.$id,
            'email' => 'nullable|email',
            'telefono' => 'nullable|string|max:20'
        ]);
        $proveedor->update($request->all());
        return back()->with('success', 'Proveedor actualizado.');
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        // Validar integridad si hay productos asociados
        if ($proveedor->productos()->exists()) {
            return back()->withErrors(['error' => 'No se puede borrar: Proveedor asignado a productos.']);
        }
        $proveedor->delete();
        return back()->with('success', 'Proveedor eliminado.');
    }
}
