<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedioPago;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MedioPagoController extends Controller
{
    public function index()
    {
        $medios = MedioPago::orderBy('medioPagoID', 'asc')->paginate(10);
        return Inertia::render('Admin/MediosPago/Index', ['medios' => $medios]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:medios_pago,nombre',
            'recargo_porcentaje' => 'required|numeric|min:0|max:100',
            'instrucciones' => 'nullable|string|max:500',
        ]);

        MedioPago::create($request->all());
        return back()->with('success', 'Medio de pago creado.');
    }

    public function update(Request $request, $id)
    {
        $medio = MedioPago::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:50|unique:medios_pago,nombre,' . $id . ',medioPagoID',
            'recargo_porcentaje' => 'required|numeric|min:0|max:100',
            'instrucciones' => 'nullable|string|max:500',
            'activo' => 'boolean'
        ]);

        $medio->update($request->all());
        return back()->with('success', 'Medio de pago actualizado.');
    }

    public function destroy($id)
    {
        // Podrías validar si ya se usó en pagos antes de borrar (recomendado)
        $medio = MedioPago::findOrFail($id);
        $medio->delete();
        return back()->with('success', 'Medio de pago eliminado.');
    }
}
