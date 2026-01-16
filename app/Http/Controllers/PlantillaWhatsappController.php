<?php

namespace App\Http\Controllers;

use App\Models\PlantillaWhatsapp;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para gestión de plantillas WhatsApp (CU-30)
 */
class PlantillaWhatsappController extends Controller
{
    /**
     * CU-30 Paso 1-2: Mostrar listado de plantillas
     */
    public function index()
    {
        $plantillas = PlantillaWhatsapp::with('usuarioModificador:id,name')
            ->orderBy('tipo_evento')
            ->get()
            ->map(function ($plantilla) {
                return [
                    'plantilla_id' => $plantilla->plantilla_id,
                    'tipo_evento' => $plantilla->tipo_evento,
                    'nombre' => $plantilla->nombre,
                    'contenido_preview' => $plantilla->contenido_plantilla, // Mostrar contenido completo
                    'activo' => $plantilla->activo,
                    'horario_inicio' => $plantilla->horario_inicio,
                    'horario_fin' => $plantilla->horario_fin,
                    'cantidad_variables' => count($plantilla->variables_disponibles),
                    'usuario_modificacion' => $plantilla->usuarioModificador?->name ?? 'Sistema',
                    'updated_at' => $plantilla->updated_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('PlantillasWhatsapp/Index', [
            'plantillas' => $plantillas,
        ]);
    }

    /**
     * CU-30 Paso 3-4: Mostrar editor de plantilla
     */
    public function edit($id)
    {
        $plantilla = PlantillaWhatsapp::with('usuarioModificador:id,name')->findOrFail($id);

        return Inertia::render('PlantillasWhatsapp/Editar', [
            'plantilla' => [
                'plantilla_id' => $plantilla->plantilla_id,
                'tipo_evento' => $plantilla->tipo_evento,
                'nombre' => $plantilla->nombre,
                'contenido_plantilla' => $plantilla->contenido_plantilla,
                'variables_disponibles' => $plantilla->variables_disponibles,
                'horario_inicio' => $plantilla->horario_inicio,
                'horario_fin' => $plantilla->horario_fin,
                'activo' => $plantilla->activo,
                'motivo_modificacion' => $plantilla->motivo_modificacion,
                'usuario_modificacion' => $plantilla->usuarioModificador?->name ?? 'Sistema',
                'updated_at' => $plantilla->updated_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * CU-30 Paso 5-13: Actualizar plantilla con validaciones
     */
    public function update(Request $request, $id)
    {
        $plantilla = PlantillaWhatsapp::findOrFail($id);

        // CU-30 Paso 9: Validaciones
        $validated = $request->validate([
            'contenido_plantilla' => 'required|string|min:10',
            'horario_inicio' => 'required|date_format:H:i',
            'horario_fin' => 'required|date_format:H:i|after:horario_inicio',
            'activo' => 'required|boolean',
            'motivo_modificacion' => 'required|string|min:10|max:500', // CU-30 Paso 7-8
        ], [
            'contenido_plantilla.required' => 'El contenido de la plantilla es obligatorio.',
            'contenido_plantilla.min' => 'El contenido debe tener al menos 10 caracteres.',
            'horario_inicio.required' => 'El horario de inicio es obligatorio.',
            'horario_inicio.date_format' => 'Formato de horario inválido. Use HH:MM.',
            'horario_fin.required' => 'El horario de fin es obligatorio.',
            'horario_fin.date_format' => 'Formato de horario inválido. Use HH:MM.',
            'horario_fin.after' => 'El horario de fin debe ser posterior al de inicio.',
            'motivo_modificacion.required' => 'Se requiere un motivo para la configuración (CU-30).',
            'motivo_modificacion.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo_modificacion.max' => 'El motivo no puede exceder 500 caracteres.',
        ]);

        try {
            DB::beginTransaction();

            // Actualizar campos
            $plantilla->contenido_plantilla = $validated['contenido_plantilla'];
            $plantilla->horario_inicio = $validated['horario_inicio'];
            $plantilla->horario_fin = $validated['horario_fin'];
            $plantilla->activo = $validated['activo'];

            // CU-30 Excepción 9a: Validar sintaxis de variables
            $erroresVariables = $plantilla->validarVariables();
            if (!empty($erroresVariables)) {
                return back()->withErrors([
                    'contenido_plantilla' => 'Sintaxis de variables incorrecta: ' . implode(', ', $erroresVariables),
                ])->withInput();
            }

            // CU-30 Paso 7-12: Registrar cambio con motivo y auditoría
            $plantilla->registrarCambio(
                $validated['motivo_modificacion'],
                auth()->id()
            );

            DB::commit();

            // CU-30 Paso 13: Confirmación
            return redirect()->route('plantillas-whatsapp.index')
                ->with('success', 'Plantilla actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // CU-30 Excepción 11a: Error al aplicar configuración
            return back()->withErrors([
                'error' => 'Error al guardar la configuración. Intente nuevamente.',
            ])->withInput();
        }
    }

    /**
     * Previsualizar plantilla con datos de ejemplo
     */
    public function preview(Request $request, $id)
    {
        $plantilla = PlantillaWhatsapp::findOrFail($id);

        // Datos de ejemplo según el tipo de evento
        $datosEjemplo = $this->obtenerDatosEjemplo($plantilla->tipo_evento);

        // Compilar plantilla con datos de ejemplo
        $mensajePreview = $plantilla->compilar($datosEjemplo);

        return response()->json([
            'mensaje' => $mensajePreview,
            'variables_usadas' => array_keys($datosEjemplo),
        ]);
    }

    /**
     * Obtener datos de ejemplo para preview según tipo de evento
     */
    private function obtenerDatosEjemplo(string $tipoEvento): array
    {
        return match ($tipoEvento) {
            'bonificacion_cliente' => [
                'codigo_reparacion' => 'REP-2025-001',
                'nombre_cliente' => 'Juan Pérez',
                'equipo_marca' => 'Samsung',
                'equipo_modelo' => 'Galaxy S21',
                'fecha_ingreso' => '01/12/2025',
                'dias_excedidos' => '3',
                'porcentaje' => '15',
                'monto_original' => '15.000,00',
                'monto_bonificado' => '2.250,00',
                'monto_final' => '12.750,00',
                'motivo_demora' => 'Demora en recepción de repuesto',
                'url_respuesta' => 'https://ejemplo.com/bonificacion/abc123',
            ],
            'alerta_sla_tecnico' => [
                'codigo_reparacion' => 'REP-2025-002',
                'nombre_tecnico' => 'Carlos García',
                'nombre_cliente' => 'María López',
                'equipo_marca' => 'iPhone',
                'equipo_modelo' => '13 Pro',
                'sla_vigente' => '7',
                'dias_efectivos' => '9',
                'dias_excedidos' => '2',
                'tipo_alerta' => 'EXCEDIDO',
                'fecha_ingreso' => '25/11/2025',
            ],
            'bloqueo_cc', 'revision_cc', 'recordatorio_cc', 'admin_alert_cc' => [
                'nombre_cliente' => 'Pedro Rodríguez',
                'motivo' => 'Saldo vencido mayor a 30 días',
            ],
            default => [],
        };
    }

    /**
     * Historial de cambios de una plantilla
     */
    public function historial($id)
    {
        $plantilla = PlantillaWhatsapp::findOrFail($id);

        $historial = \App\Models\Auditoria::where('tabla_afectada', 'plantillas_whatsapp')
            ->where('registro_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($auditoria) {
                return [
                    'fecha' => $auditoria->created_at->format('d/m/Y H:i'),
                    'usuario' => $auditoria->usuario?->name ?? 'Sistema',
                    'accion' => $auditoria->accion,
                    'motivo' => $auditoria->motivo,
                    'detalles' => $auditoria->detalles,
                    'datos_anteriores' => $auditoria->datos_anteriores,
                    'datos_nuevos' => $auditoria->datos_nuevos,
                ];
            });

        return Inertia::render('PlantillasWhatsapp/Historial', [
            'plantilla' => [
                'plantilla_id' => $plantilla->plantilla_id,
                'nombre' => $plantilla->nombre,
                'tipo_evento' => $plantilla->tipo_evento,
            ],
            'historial' => $historial,
        ]);
    }
}
