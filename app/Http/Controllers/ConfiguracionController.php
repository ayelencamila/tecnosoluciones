<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $todas = Configuracion::all()->map(function ($item) {
            // Casteo inteligente para el Frontend
            if ($item->valor === 'true') $item->valor = true;
            elseif ($item->valor === 'false') $item->valor = false;
            elseif (is_numeric($item->valor)) $item->valor = +$item->valor; // Convertir a int/float
            return $item;
        });

        // Definimos los grupos según tu Seeder
        $grupos = [
            'Generales' => $todas->filter(fn($c) => in_array($c->clave, ['nombre_empresa', 'cuit_empresa', 'email_contacto', 'direccion_empresa']))->values(),
            
            'Ventas y Stock' => $todas->filter(fn($c) => 
                str_starts_with($c->clave, 'dias_maximos') || 
                str_starts_with($c->clave, 'permitir_venta') ||
                str_contains($c->clave, 'stock')
            )->values(),

            'Cuentas Corrientes' => $todas->filter(fn($c) => 
                str_contains($c->clave, 'global') || 
                str_contains($c->clave, 'AutoBlock') ||
                str_contains($c->clave, 'whatsapp_admin')
            )->values(),
            
            'Reparaciones (SLA)' => $todas->filter(fn($c) => str_starts_with($c->clave, 'reparacion_'))->values(),
            
            'Comunicación (WhatsApp)' => $todas->filter(fn($c) => 
                str_starts_with($c->clave, 'whatsapp_') && !str_contains($c->clave, 'admin')
            )->values(),
        ];

        return Inertia::render('Configuracion/Index', [
            'grupos' => $grupos,
        ]);
    }

    public function update(Request $request)
    {
        // Validamos todo lo que mandaste en el Seeder
        $validated = $request->validate([
            'nombre_empresa' => 'required|string',
            'cuit_empresa' => 'required|string',
            'email_contacto' => 'required|email',
            'direccion_empresa' => 'nullable|string',
            
            'limite_credito_global' => 'required|numeric|min:0',
            'dias_gracia_global' => 'required|integer|min:0',
            'politicaAutoBlock' => 'boolean',
            'whatsapp_admin_notificaciones' => 'nullable|string',

            'dias_maximos_anulacion_venta' => 'required|integer|min:0',
            'permitir_venta_sin_stock' => 'boolean',
            'stock_minimo_global' => 'required|integer|min:0',
            'alerta_stock_bajo' => 'boolean',

            'reparacion_sla_dias_estandar' => 'required|integer|min:1',
            'reparacion_habilitar_bonificacion' => 'boolean',
            'reparacion_bonificacion_diaria_porc' => 'required|numeric|min:0',
            'reparacion_tope_bonificacion_porc' => 'required|numeric|min:0',

            'whatsapp_activo' => 'boolean',
            'whatsapp_horario_inicio' => 'required',
            'whatsapp_horario_fin' => 'required',
            'whatsapp_reintentos_maximos' => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                foreach ($validated as $clave => $valor) {
                    // Convertimos booleanos de vuelta a string para la BD
                    if (is_bool($valor)) {
                        $valor = $valor ? 'true' : 'false';
                    }

                    $config = Configuracion::where('clave', $clave)->first();
                    
                    if ($config && $config->valor != $valor) {
                        // Auditoría
                        Auditoria::create([
                            'tabla_afectada' => 'configuracion',
                            'registro_id' => $config->configuracionID,
                            'accion' => 'MODIFICAR_PARAMETRO',
                            'datos_anteriores' => json_encode(['valor' => $config->valor]),
                            'datos_nuevos' => json_encode(['valor' => $valor]),
                            'motivo' => 'Panel de Configuración',
                            'usuarioID' => auth()->id(),
                            'detalles' => "Cambio en {$clave}"
                        ]);

                        $config->valor = (string) $valor;
                        $config->save();
                    }
                }
            });

            return back()->with('success', 'Configuración guardada correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }
}