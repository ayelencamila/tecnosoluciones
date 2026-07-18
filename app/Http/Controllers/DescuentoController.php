<?php

namespace App\Http\Controllers;

use App\Http\Requests\Descuentos\StoreDescuentoRequest;
use App\Models\AplicabilidadDescuento;        // <--- Nuevo
use App\Models\Auditoria; // <--- Nuevo
use App\Models\Descuento;
use App\Models\TipoDescuento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
                'usuarioID' => auth()->id(),
                'accion' => 'CREAR_DESCUENTO',
                'tabla_afectada' => 'descuentos',
                'registro_id' => $descuento->descuento_id,
                'datos_nuevos' => $descuento->toArray(),
                'detalles' => "Creación: {$descuento->codigo}",
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
                'usuarioID' => auth()->id(),
                'accion' => 'MODIFICAR_DESCUENTO',
                'tabla_afectada' => 'descuentos',
                'registro_id' => $descuento->descuento_id,
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $descuento->fresh()->toArray(),
                'detalles' => "Modificación: {$descuento->codigo}",
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
