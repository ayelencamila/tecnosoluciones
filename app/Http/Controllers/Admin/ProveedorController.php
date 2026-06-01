<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        // Multi-tenant: unicidad por empresa (Elmasri: scope de particion tenant).
        $request->validate([
            'razon_social' => 'required|string|max:100',
            'cuit' => [
                'required', 'string', 'max:20',
                Rule::unique('proveedores', 'cuit')
                    ->where('empresa_id', auth()->user()->empresa_id),
            ],
            'email' => 'nullable|email',
            'telefono' => 'nullable|string|max:20'
        ]);
        Proveedor::create($request->all());
        return back()->with('success', 'Proveedor creado.');
    }

    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        // Multi-tenant: unicidad por empresa, ignorando este registro.
        $request->validate([
            'razon_social' => 'required|string|max:100',
            'cuit' => [
                'required', 'string', 'max:20',
                Rule::unique('proveedores', 'cuit')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($id),
            ],
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
