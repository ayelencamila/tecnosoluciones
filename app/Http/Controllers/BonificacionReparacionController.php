<?php

namespace App\Http\Controllers;

use App\Models\BonificacionReparacion;
use App\Jobs\NotificarBonificacionCliente;
use App\Jobs\NotificarRechazoCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Controlador para gestión de bonificaciones (CU-15)
 * Solo accesible para administradores/gerentes
 * El middleware role:admin,manager está configurado en routes/web.php
 */
class BonificacionReparacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BonificacionReparacion::with([
            'reparacion.cliente',
            'reparacion.tecnico',
            'motivoDemora',
            'aprobadaPor'
        ]);

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        } else {
            // Por defecto, mostrar solo pendientes
            $query->where('estado', 'pendiente');
        }

        $bonificaciones = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Reparaciones/Bonificaciones/Index', [
            'bonificaciones' => $bonificaciones,
            'filtros' => [
                'estado' => $request->estado,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(BonificacionReparacion $bonificacion)
    {
        $bonificacion->load([
            'reparacion.cliente',
            'reparacion.tecnico',
            'reparacion.estado',
            'motivoDemora',
            'aprobadaPor',
            'estadoDecision'
        ]);

        // Agregar decision_cliente manualmente para que llegue a la vista
        $bonificacion->decision_cliente = $bonificacion->estadoDecision?->nombre;

        return Inertia::render('Reparaciones/Bonificaciones/Detalle', [
            'bonificacion' => $bonificacion,
        ]);
    }

    /**
     * Aprobar bonificación
     */
    public function aprobar(Request $request, BonificacionReparacion $bonificacion)
    {
        $validated = $request->validate([
            'porcentaje_ajustado' => 'nullable|numeric|min:0|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $usuarioID = Auth::id();

        if (isset($validated['porcentaje_ajustado'])) {
            // Aprobar con porcentaje ajustado
            $bonificacion->aprobarConPorcentaje(
                $usuarioID,
                $validated['porcentaje_ajustado'],
                $validated['observaciones'] ?? null
            );
        } else {
            // Aprobar con porcentaje sugerido
            $bonificacion->aprobar($usuarioID, $validated['observaciones'] ?? null);
        }

        // Despachar notificación al cliente
        NotificarBonificacionCliente::dispatch($bonificacion->fresh());

        return redirect()->route('bonificaciones.index')
            ->with('success', 'Bonificación aprobada exitosamente. Se notificará al cliente.');
    }

    /**
     * Rechazar bonificación
     */
    public function rechazar(Request $request, BonificacionReparacion $bonificacion)
    {
        $validated = $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ]);

        $bonificacion->rechazar(Auth::id(), $validated['motivo_rechazo']);

        // Despachar notificación al cliente sobre el rechazo
        NotificarRechazoCliente::dispatch($bonificacion->fresh(), $validated['motivo_rechazo']);

        return redirect()->route('bonificaciones.index')
            ->with('success', 'Bonificación rechazada. Se notificará al cliente para que retire su equipo.');
    }

    /**
     * Historial de bonificaciones (estadísticas)
     */
    public function historial()
    {
        $stats = [
            'total' => BonificacionReparacion::count(),
            'pendientes' => BonificacionReparacion::pendientes()->count(),
            'aprobadas' => BonificacionReparacion::aprobadas()->count(),
            'rechazadas' => BonificacionReparacion::where('estado', 'rechazada')->count(),
            'monto_total_bonificado' => BonificacionReparacion::aprobadas()->sum('monto_bonificado'),
        ];

        $bonificacionesRecientes = BonificacionReparacion::with([
            'reparacion.cliente',
            'motivoDemora',
            'aprobadaPor'
        ])
        ->orderBy('created_at', 'desc')
        ->limit(20)
        ->get();

        return Inertia::render('Reparaciones/Bonificaciones/Historial', [
            'stats' => $stats,
            'bonificaciones' => $bonificacionesRecientes,
        ]);
    }
}

