<?php

namespace App\Http\Controllers;

use App\Models\AlertaReparacion;
use App\Models\MotivoDemoraReparacion;
use App\Models\BonificacionReparacion;
use App\Services\MonitoreoSLAReparacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Controlador para gestión de alertas de SLA (CU-14)
 * Los técnicos responden sus alertas
 */
class AlertaReparacionController extends Controller
{
    /**
     * Display a listing of the resource.
     * Muestra alertas del técnico autenticado
     */
    public function index()
    {
        $tecnicoID = Auth::id();

        $alertas = AlertaReparacion::with(['reparacion.cliente', 'reparacion.estado'])
            ->where('tecnicoID', $tecnicoID)
            ->orderBy('leida', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Reparaciones/Alertas/Index', [
            'alertas' => $alertas,
        ]);
    }

    /**
     * Show the form for responding to alert.
     */
    public function show(AlertaReparacion $alerta)
    {
        // Verificar que la alerta pertenece al técnico autenticado
        if ($alerta->tecnicoID !== Auth::id()) {
            abort(403, 'No autorizado para ver esta alerta');
        }

        $alerta->load(['reparacion.cliente', 'reparacion.estado', 'tecnico']);

        // Obtener motivos de demora activos
        $motivos = MotivoDemoraReparacion::activos()
            ->orderBy('orden')
            ->get();

        return Inertia::render('Reparaciones/Alertas/Responder', [
            'alerta' => $alerta,
            'motivos' => $motivos,
        ]);
    }

    /**
     * Registrar respuesta del técnico a la alerta
     */
    public function responder(Request $request, AlertaReparacion $alerta)
    {
        // Verificar que la alerta pertenece al técnico autenticado
        if ($alerta->tecnicoID !== Auth::id()) {
            abort(403, 'No autorizado para responder esta alerta');
        }

        $validated = $request->validate([
            'motivoDemoraID' => 'required|exists:motivos_demora_reparacion,motivoDemoraID',
            'es_factible' => 'required|boolean',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        // Registrar respuesta en la alerta
        $alerta->registrarRespuesta(
            $validated['motivoDemoraID'],
            $validated['es_factible'],
            $validated['observaciones'] ?? null
        );

        // Marcar como leída
        $alerta->marcarComoLeida();

        // Obtener el motivo seleccionado
        $motivo = MotivoDemoraReparacion::find($validated['motivoDemoraID']);

        // Si el motivo requiere bonificación, crearla
        if ($motivo && $motivo->requiere_bonificacion && $validated['es_factible']) {
            $this->crearBonificacion($alerta, $motivo);
        }

        return redirect()->route('alertas.index')
            ->with('success', 'Respuesta registrada exitosamente.');
    }

    /**
     * Marcar alerta como leída
     */
    public function marcarLeida(AlertaReparacion $alerta)
    {
        if ($alerta->tecnicoID !== Auth::id()) {
            abort(403, 'No autorizado');
        }

        $alerta->marcarComoLeida();

        return back()->with('success', 'Alerta marcada como leída.');
    }

    /**
     * Crea bonificación sugerida basada en días de exceso
     */
    protected function crearBonificacion(AlertaReparacion $alerta, MotivoDemoraReparacion $motivo): void
    {
        $service = app(MonitoreoSLAReparacionService::class);
        $reparacion = $alerta->reparacion;

        // Calcular porcentaje de bonificación
        $porcentajeSugerido = $service->calcularPorcentajeBonificacion($alerta->dias_excedidos);
        $porcentajeAjustado = $service->aplicarTopeBonificacion($porcentajeSugerido);

        // Calcular montos (sobre mano de obra)
        $montoOriginal = $reparacion->costo_mano_obra ?? 0;
        $montoBonificacion = ($montoOriginal * $porcentajeAjustado) / 100;

        BonificacionReparacion::create([
            'reparacionID' => $reparacion->reparacionID,
            'motivoDemoraID' => $motivo->motivoDemoraID,
            'porcentaje_sugerido' => $porcentajeSugerido,
            'porcentaje_aprobado' => null, // Pendiente de aprobación
            'monto_original' => $montoOriginal,
            'monto_bonificado' => $montoBonificacion,
            'dias_excedidos' => $alerta->dias_excedidos,
            'estado' => 'pendiente',
            'justificacion_tecnico' => $alerta->respuesta_tecnico['observaciones'] ?? null,
        ]);
    }
}

