<?php

namespace App\Http\Controllers;

use App\Services\Configuracion\ConfiguracionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Controlador para gestión de parámetros globales del sistema (CU-31)
 * Delega lógica de negocio a ConfiguracionService
 */
class ConfiguracionController extends Controller
{
    public function __construct(
        private ConfiguracionService $configuracionService
    ) {}

    /**
     * CU-31 Paso 1-2: Mostrar parámetros globales agrupados por categoría
     */
    public function index()
    {
        $grupos = $this->configuracionService->obtenerConfiguracionesAgrupadas();
        
        // Aplicar casting inteligente para el frontend
        foreach ($grupos as $categoria => $configs) {
            $grupos[$categoria] = $configs->map(function ($item) {
                $item->valor = $this->configuracionService->obtener($item->clave, $item->valor);
                return $item;
            });
        }

        return Inertia::render('Configuracion/Index', [
            'grupos' => $grupos,
        ]);
    }
    /**
     * CU-31 Paso 3-10: Actualizar parámetros con validación y auditoría
     */
    public function update(Request $request)
    {
        // Validación básica de Laravel para campos requeridos
        $validated = $request->validate([
            'nombre_empresa' => 'required|string',
            'cuit_empresa' => 'required|string',
            'email_contacto' => 'required|email',
            'direccion_empresa' => 'nullable|string',
            
            // Los demás campos se validan en ConfiguracionValidationService
            // con reglas de negocio específicas
        ]);

        // Obtener todos los parámetros enviados
        $configuraciones = $request->except(['_token', '_method']);

        // CU-31 Paso 6-7: Actualizar configuraciones con validación de negocio
        $resultado = $this->configuracionService->actualizarConfiguraciones(
            $configuraciones,
            auth()->id(),
            'Actualización desde panel de configuración global'
        );

        if (!$resultado['exito']) {
            // CU-31 Excepción 6a: Valores inválidos
            return back()
                ->withErrors($resultado['errores'])
                ->withInput();
        }

        // CU-31 Paso 10: Confirmación
        $mensaje = count($resultado['cambios']) > 0
            ? 'Configuración actualizada exitosamente. ' . count($resultado['cambios']) . ' parámetro(s) modificado(s).'
            : 'No se realizaron cambios en la configuración.';

        return back()->with('success', $mensaje);
    }

    /**
     * Obtener historial de cambios de una configuración específica
     */
    public function historial(string $clave)
    {
        $historial = $this->configuracionService->obtenerHistorial($clave, 100);

        return Inertia::render('Configuracion/Historial', [
            'clave' => $clave,
            'historial' => $historial,
        ]);
    }

    /**
     * Limpiar caché de configuraciones (útil para desarrollo)
     */
    public function limpiarCache()
    {
        $this->configuracionService->limpiarCache();

        return back()->with('success', 'Caché de configuraciones limpiado exitosamente.');
    }
}