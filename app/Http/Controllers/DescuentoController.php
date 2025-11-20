<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use App\Models\Auditoria; 
use App\Http\Requests\Descuentos\StoreDescuentoRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DescuentoController extends Controller
{
    public function index(Request $request)
    {
        $descuentos = Descuento::query()
            ->when($request->search, function ($query, $search) {
                $query->where('codigo', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->orderBy('activo', 'desc') 
            ->orderBy('valido_hasta', 'desc')
            ->paginate(10)
            ->withQueryString();

        // CORRECCIÓN AQUÍ: Apuntamos a 'ListadoDescuentos' en lugar de 'Index'
        return Inertia::render('Descuentos/ListadoDescuentos', [
            'descuentos' => $descuentos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Descuentos/Create'); 
    }

    public function store(StoreDescuentoRequest $request)
    {
        DB::transaction(function () use ($request) {
            $descuento = Descuento::create($request->validated());

            Auditoria::create([
                'user_id' => auth()->id(),
                'accion' => 'CREAR_DESCUENTO',
                'tabla_afectada' => 'descuentos',
                'registro_id' => $descuento->descuento_id,
                'datos_nuevos' => json_encode($descuento->toArray()),
                'detalles' => "Creación de regla de descuento: {$descuento->codigo}",
                'fecha' => now(),
            ]);
        });

        return to_route('descuentos.index')->with('success', 'Descuento creado correctamente.');
    }

    public function edit(Descuento $descuento)
    {
        return Inertia::render('Descuentos/Edit', [
            'descuento' => $descuento
        ]);
    }

    public function update(StoreDescuentoRequest $request, Descuento $descuento)
    {
        DB::transaction(function () use ($request, $descuento) {
            $datosAnteriores = $descuento->toArray();
            
            $descuento->update($request->validated());

            Auditoria::create([
                'user_id' => auth()->id(),
                'accion' => 'MODIFICAR_DESCUENTO',
                'tabla_afectada' => 'descuentos',
                'registro_id' => $descuento->descuento_id,
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($descuento->fresh()->toArray()),
                'detalles' => "Modificación de descuento: {$descuento->codigo}",
                'fecha' => now(),
            ]);
        });

        return to_route('descuentos.index')->with('success', 'Descuento actualizado.');
    }

    public function destroy(Descuento $descuento)
    {
        $descuento->update(['activo' => false]);
        
        Auditoria::create([
            'user_id' => auth()->id(),
            'accion' => 'DESACTIVAR_DESCUENTO',
            'tabla_afectada' => 'descuentos',
            'registro_id' => $descuento->descuento_id,
            'detalles' => "Desactivación de descuento: {$descuento->codigo}",
            'fecha' => now(),
        ]);

        return back()->with('success', 'Descuento desactivado.');
    }
}
