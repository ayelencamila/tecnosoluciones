<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ConfiguracionController extends Controller
{
    /**
     * Muestra la pantalla de configuración (CU-31 Paso 2)
     */
    public function index()
    {
        // Obtenemos todos los parámetros organizados por clave
        $configuraciones = Configuracion::all()->pluck('valor', 'clave');

        return Inertia::render('Configuracion/Index', [
            'parametros' => $configuraciones,
        ]);
    }

    /**
     * Actualiza los parámetros globales (CU-31 Pasos 5-8)
     */
    public function update(Request $request)
    {
        // 1. Validación (CU-31 Paso 6)
        $validated = $request->validate([
            'limite_credito_global' => 'required|numeric|min:0',
            'dias_gracia_global' => 'required|integer|min:0',
            'monto_minimo_notif' => 'required|numeric|min:0',
            'bloqueo_auto' => 'boolean',
            // Agrega aquí más validaciones según tus parámetros
        ]);

        try {
            DB::transaction(function () use ($validated) {
                foreach ($validated as $clave => $valor) {
                    $config = Configuracion::firstOrNew(['clave' => $clave]);
                    
                    // Auditoría manual si cambia el valor (CU-31 Paso 9)
                    if ($config->exists && $config->valor != $valor) {
                         Auditoria::create([
                            'tabla_afectada' => 'configuraciones',
                            'registro_id' => $config->id,
                            'accion' => 'MODIFICAR_PARAMETRO',
                            'datos_anteriores' => json_encode(['valor' => $config->valor]),
                            'datos_nuevos' => json_encode(['valor' => $valor]),
                            'motivo' => 'Cambio de configuración global',
                            'usuarioID' => auth()->id(),
                            'detalles' => "Parámetro '{$clave}' actualizado."
                        ]);
                    }

                    $config->valor = $valor;
                    $config->save();
                }
            });

            return back()->with('success', 'Configuración actualizada correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al guardar la configuración: ' . $e->getMessage()]);
        }
    }
}