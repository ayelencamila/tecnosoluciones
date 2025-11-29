<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use App\Models\TipoDescuento;        // <--- Nuevo
use App\Models\AplicabilidadDescuento; // <--- Nuevo
use App\Models\Auditoria; 
use App\Http\Requests\Descuentos\StoreDescuentoRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DescuentoController extends Controller
{
    public function index(Request $request)
    {
        // Cargamos las relaciones para mostrar los nombres reales en la tabla
        $descuentos = Descuento::with(['tipo', 'aplicabilidad'])
            ->when($request->search, function ($query, $search) {
                $query->where('codigo', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->orderBy('activo', 'desc') 
            ->orderBy('valido_hasta', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Descuentos/ListadoDescuentos', [
            'descuentos' => $descuentos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        // ENVIAMOS LAS LISTAS PARAMÉTRICAS A LA VISTA
        return Inertia::render('Descuentos/Create', [
            'tipos' => TipoDescuento::where('activo', true)->get(),
            'aplicabilidades' => AplicabilidadDescuento::where('activo', true)->get(),
        ]);
    }

    public function store(StoreDescuentoRequest $request)
    {
        DB::transaction(function () use ($request) {
            $descuento = Descuento::create($request->validated());

            Auditoria::create([
                'user_id' => auth()->id() ?? 1,
                'accion' => 'CREAR_DESCUENTO',
                'tabla_afectada' => 'descuentos',
                'registro_id' => $descuento->descuento_id,
                'datos_nuevos' => json_encode($descuento->toArray()),
                'detalles' => "Creación: {$descuento->codigo}",
                'fecha' => now(),
            ]);
        });

        return to_route('descuentos.index')->with('success', 'Descuento creado correctamente.');
    }

    public function edit(Descuento $descuento)
    {
        return Inertia::render('Descuentos/Edit', [
            'descuento' => $descuento,
            // También las necesitamos al editar
            'tipos' => TipoDescuento::where('activo', true)->get(),
            'aplicabilidades' => AplicabilidadDescuento::where('activo', true)->get(),
        ]);
    }

    public function update(StoreDescuentoRequest $request, Descuento $descuento)
    {
        DB::transaction(function () use ($request, $descuento) {
            $datosAnteriores = $descuento->toArray();
            
            $descuento->update($request->validated());

            Auditoria::create([
                'user_id' => auth()->id() ?? 1,
                'accion' => 'MODIFICAR_DESCUENTO',
                'tabla_afectada' => 'descuentos',
                'registro_id' => $descuento->descuento_id,
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($descuento->fresh()->toArray()),
                'detalles' => "Modificación: {$descuento->codigo}",
                'fecha' => now(),
            ]);
        });

        return to_route('descuentos.index')->with('success', 'Descuento actualizado.');
    }

    public function destroy(Descuento $descuento)
    {
        // Baja Lógica (Soft Delete manual o flag activo)
        $descuento->update(['activo' => false]);
        
        return back()->with('success', 'Descuento desactivado.');
    }
}