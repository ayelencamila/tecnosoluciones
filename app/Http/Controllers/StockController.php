<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreMovimientoStockRequest;
use App\Services\Stock\RegistrarMovimientoStockService;
use App\Models\Stock; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    /**
     * CU-30: Registrar Movimiento Manual (Entrada/Salida/Ajuste)
     */
    public function storeMovimiento(StoreMovimientoStockRequest $request, RegistrarMovimientoStockService $service)
    {
        // 1. Log de entrada
        Log::info('>>> INICIO MOVIMIENTO STOCK <<<');
        Log::info('Datos recibidos:', $request->all());

        try {
            // 2. Ejecución del servicio
            $service->handle($request->validated(), auth()->id());

            Log::info('>>> MOVIMIENTO EXITOSO <<<');
            
            // 3. Redirección explícita para Inertia
            return redirect()->back()->with('success', 'Movimiento de stock registrado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al registrar movimiento de stock: ' . $e->getMessage());
            // Devolvemos el error al frontend
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Actualiza la configuración del stock (Mínimos).
     * ESTE ES EL MÉTODO QUE TE FALTABA
     */
    public function update(Request $request, Stock $stock)
    {
        // Validación simple (Boundary)
        $validated = $request->validate([
            'stock_minimo' => 'required|integer|min:0',
        ], [
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
        ]);

        try {
            // Actualizamos solo el campo permitido
            $stock->update([
                'stock_minimo' => $validated['stock_minimo']
            ]);

            return back()->with('success', 'Configuración de alerta actualizada.');
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar configuración de stock: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al guardar configuración.']);
        }
    }
}