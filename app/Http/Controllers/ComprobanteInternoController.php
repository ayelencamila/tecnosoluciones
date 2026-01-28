<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CU-32: Gestionar Comprobantes Internos
 * Usa el modelo Comprobante existente con tipos_comprobante y estados_comprobante
 */
class ComprobanteInternoController extends Controller
{
    /**
     * Listado de comprobantes con filtros básicos
     */
    public function index(Request $request): Response
    {
        $query = Comprobante::with(['usuario', 'tipoComprobante', 'estadoComprobante'])
            ->orderBy('fecha_emision', 'desc');

        // Filtro por número
        if ($request->filled('buscar')) {
            $query->where('numero_correlativo', 'like', '%' . $request->buscar . '%');
        }

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo_comprobante_id', $request->tipo);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado_comprobante_id', $request->estado);
        }

        $comprobantes = $query->paginate(20)->withQueryString();

        // Obtener tipos y estados de las tablas (sin hardcodeo)
        $tipos = DB::table('tipos_comprobante')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['tipo_id', 'nombre']);

        $estados = DB::table('estados_comprobante')
            ->where('activo', true)
            ->get(['estado_id', 'nombre']);

        return Inertia::render('Comprobantes/Index', [
            'comprobantes' => $comprobantes,
            'filtros' => $request->only(['buscar', 'tipo', 'estado']),
            'tipos' => $tipos,
            'estados' => $estados,
        ]);
    }

    /**
     * Detalle del comprobante
     */
    public function show(Comprobante $comprobante): Response
    {
        $comprobante->load(['usuario', 'tipoComprobante', 'estadoComprobante', 'original', 'reemisiones']);

        // Determinar si puede anularse o reemitirse
        $estadoEmitido = DB::table('estados_comprobante')->where('nombre', 'EMITIDO')->value('estado_id');
        $estadoAnulado = DB::table('estados_comprobante')->where('nombre', 'ANULADO')->value('estado_id');

        return Inertia::render('Comprobantes/Show', [
            'comprobante' => $comprobante,
            'puedeAnularse' => $comprobante->estado_comprobante_id === $estadoEmitido,
            'puedeReemitirse' => $comprobante->estado_comprobante_id === $estadoEmitido,
        ]);
    }

    /**
     * Ver PDF del comprobante
     */
    public function verPdf(Comprobante $comprobante)
    {
        if (!$comprobante->ruta_archivo) {
            return back()->with('error', 'Este comprobante no tiene PDF generado.');
        }

        return redirect($comprobante->ruta_archivo);
    }

    /**
     * Anular comprobante
     */
    public function anular(Request $request, Comprobante $comprobante): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'Debe indicar un motivo para la anulación.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        try {
            $comprobante->anular($request->motivo);

            return redirect()->route('comprobantes.show', $comprobante)
                ->with('success', 'Comprobante anulado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reemitir comprobante
     */
    public function reemitir(Request $request, Comprobante $comprobante): RedirectResponse
    {
        try {
            $nuevo = $comprobante->reemitir(auth()->id());

            return redirect()->route('comprobantes.show', $nuevo)
                ->with('success', "Comprobante reemitido. Nuevo número: {$nuevo->numero_correlativo}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
