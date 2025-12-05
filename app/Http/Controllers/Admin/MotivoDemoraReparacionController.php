<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MotivoDemoraReparacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MotivoDemoraReparacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $motivos = MotivoDemoraReparacion::orderBy('orden')->get();

        return Inertia::render('Admin/MotivosDemora/Index', [
            'motivos' => $motivos,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/MotivosDemora/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:motivos_demora_reparacion,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'requiere_bonificacion' => 'required|boolean',
            'pausa_sla' => 'required|boolean',
            'activo' => 'required|boolean',
        ]);

        // Obtener el próximo orden disponible
        $maxOrden = MotivoDemoraReparacion::max('orden') ?? 0;
        $validated['orden'] = $maxOrden + 1;

        MotivoDemoraReparacion::create($validated);

        return redirect()->route('admin.motivos-demora.index')
            ->with('success', 'Motivo de demora creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MotivoDemoraReparacion $motivosDemora)
    {
        return Inertia::render('Admin/MotivosDemora/Show', [
            'motivo' => $motivosDemora,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MotivoDemoraReparacion $motivosDemora)
    {
        return Inertia::render('Admin/MotivosDemora/Edit', [
            'motivo' => $motivosDemora,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MotivoDemoraReparacion $motivosDemora)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:motivos_demora_reparacion,codigo,' . $motivosDemora->motivoDemoraID . ',motivoDemoraID',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'requiere_bonificacion' => 'required|boolean',
            'pausa_sla' => 'required|boolean',
            'activo' => 'required|boolean',
            'orden' => 'nullable|integer|min:1',
        ]);

        $motivosDemora->update($validated);

        return redirect()->route('admin.motivos-demora.index')
            ->with('success', 'Motivo de demora actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete: marca como inactivo en lugar de eliminar
     */
    public function destroy(MotivoDemoraReparacion $motivosDemora)
    {
        // Soft delete para preservar integridad referencial
        $motivosDemora->update(['activo' => false]);

        return redirect()->route('admin.motivos-demora.index')
            ->with('success', 'Motivo de demora desactivado exitosamente.');
    }

    /**
     * Reordenar motivos
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'motivos' => 'required|array',
            'motivos.*.motivoDemoraID' => 'required|exists:motivos_demora_reparacion,motivoDemoraID',
            'motivos.*.orden' => 'required|integer|min:1',
        ]);

        foreach ($validated['motivos'] as $motivoData) {
            MotivoDemoraReparacion::where('motivoDemoraID', $motivoData['motivoDemoraID'])
                ->update(['orden' => $motivoData['orden']]);
        }

        return back()->with('success', 'Orden de motivos actualizado exitosamente.');
    }

    /**
     * Toggle activo status
     */
    public function toggle(MotivoDemoraReparacion $motivosDemora)
    {
        $motivosDemora->update(['activo' => !$motivosDemora->activo]);

        $estado = $motivosDemora->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Motivo de demora {$estado} exitosamente.");
    }
}
