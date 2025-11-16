<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class DescuentoController extends Controller
{
    /**
     * Muestra el listado de descuentos (CU-08).
     */
    public function index(Request $request)
    {
        $descuentos = Descuento::query()
            ->select('descuento_id', 'codigo', 'descripcion', 'tipo', 'valor', 'activo', 'valido_desde', 'valido_hasta')
            ->when($request->input('search'), function (Builder $query, $search) {
                $query->where('codigo', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->when($request->input('tipo'), fn(Builder $q, $tipo) => $q->where('tipo', $tipo))
            ->when($request->has('activo'), fn(Builder $q) => $q->where('activo', $request->input('activo')))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Descuentos/ListadoDescuentos', [
            'descuentos' => $descuentos,
            'filters' => $request->only(['search', 'tipo', 'activo']),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo descuento.
     */
    public function create()
    {
        return Inertia::render('Descuentos/FormularioDescuento', [
            'descuento' => null, // Nuevo descuento
        ]);
    }

    /**
     * Almacena un nuevo descuento en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:descuentos,codigo',
            'descripcion' => 'required|string|max:255',
            'tipo' => 'required|in:porcentaje,monto_fijo',
            'valor' => 'required|numeric|min:0',
            'activo' => 'boolean',
            'valido_desde' => 'nullable|date',
            'valido_hasta' => 'nullable|date|after_or_equal:valido_desde',
        ], [
            'codigo.unique' => 'El código de descuento ya existe.',
            'valido_hasta.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ]);

        // Validación adicional: Si es porcentaje, no puede ser mayor a 100
        if ($validated['tipo'] === 'porcentaje' && $validated['valor'] > 100) {
            return back()->withErrors(['valor' => 'El porcentaje no puede ser mayor a 100.']);
        }

        $descuento = Descuento::create($validated);

        Log::info("Descuento creado: ID {$descuento->descuento_id} - Código: {$descuento->codigo}");

        return to_route('descuentos.show', $descuento->descuento_id)
               ->with('success', '¡Descuento creado con éxito!');
    }

    /**
     * Muestra el detalle de un descuento.
     */
    public function show(Descuento $descuento)
    {
        return Inertia::render('Descuentos/DetalleDescuento', [
            'descuento' => $descuento,
        ]);
    }

    /**
     * Muestra el formulario para editar un descuento.
     */
    public function edit(Descuento $descuento)
    {
        return Inertia::render('Descuentos/FormularioDescuento', [
            'descuento' => $descuento,
        ]);
    }

    /**
     * Actualiza un descuento existente.
     */
    public function update(Request $request, Descuento $descuento)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:descuentos,codigo,' . $descuento->descuento_id . ',descuento_id',
            'descripcion' => 'required|string|max:255',
            'tipo' => 'required|in:porcentaje,monto_fijo',
            'valor' => 'required|numeric|min:0',
            'activo' => 'boolean',
            'valido_desde' => 'nullable|date',
            'valido_hasta' => 'nullable|date|after_or_equal:valido_desde',
        ], [
            'codigo.unique' => 'El código de descuento ya existe.',
            'valido_hasta.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ]);

        // Validación adicional: Si es porcentaje, no puede ser mayor a 100
        if ($validated['tipo'] === 'porcentaje' && $validated['valor'] > 100) {
            return back()->withErrors(['valor' => 'El porcentaje no puede ser mayor a 100.']);
        }

        $descuento->update($validated);

        Log::info("Descuento actualizado: ID {$descuento->descuento_id}");

        return to_route('descuentos.show', $descuento->descuento_id)
               ->with('success', '¡Descuento actualizado con éxito!');
    }

    /**
     * Desactiva un descuento (no se elimina físicamente).
     */
    public function destroy(Descuento $descuento)
    {
        $descuento->update(['activo' => false]);

        Log::info("Descuento desactivado: ID {$descuento->descuento_id}");

        return to_route('descuentos.index')
               ->with('success', 'Descuento desactivado correctamente.');
    }
}
