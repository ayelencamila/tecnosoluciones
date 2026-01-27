<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposito;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepositoController extends Controller
{
    public function index()
    {
        $depositos = Deposito::orderBy('deposito_id', 'asc')->paginate(10);

        return Inertia::render('Admin/Depositos/Index', [
            'depositos' => $depositos
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:depositos,nombre',
            'direccion' => 'nullable|string|max:255',
        ]);

        Deposito::create([
            'nombre' => $request->nombre,
            'direccion' => $request->direccion,
            'activo' => true
        ]);

        return back()->with('success', 'Depósito creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $deposito = Deposito::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:depositos,nombre,' . $id . ',deposito_id',
            'direccion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        // Regla de Negocio: No se puede desactivar el Depósito Principal
        if ($id == 1 && !$request->activo) {
            return back()->withErrors(['activo' => 'El depósito principal no puede desactivarse.']);
        }

        $deposito->update($request->only(['nombre', 'direccion', 'activo']));

        return back()->with('success', 'Depósito actualizado.');
    }

    public function destroy($id)
    {
        // Regla de Negocio: Prohibido borrar el depósito principal (ID 1)
        if ($id == 1) {
            return back()->withErrors(['error' => 'El depósito principal es crítico y no se puede eliminar.']);
        }

        $deposito = Deposito::findOrFail($id);

        // Regla de Integridad: No borrar si tiene stock asociado
        if ($deposito->stocks()->where('cantidad_disponible', '>', 0)->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: Este depósito tiene productos con stock.']);
        }

        // Si no tiene stock o está en cero, permitimos el borrado (o soft delete si lo tuvieras)
        // Como tu modelo no tiene SoftDeletes en el $fillable visible, asumimos delete físico o lógico según migración.
        // Si prefieres solo desactivar:
        $deposito->update(['activo' => false]);
        
        return back()->with('success', 'Depósito desactivado/eliminado.');
    }
}
