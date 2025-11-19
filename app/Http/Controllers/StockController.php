<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreMovimientoStockRequest;
use App\Services\Stock\RegistrarMovimientoStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
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
            return redirect()->back()->with('success', 'Movimiento registrado correctamente.');

        } catch (\Exception $e) {
        // EN LUGAR DE Log::error...
        dd($e->getMessage()); // <--- ESTO MOSTRARÁ EL ERROR EN PANTALLA (Pestaña Red)

        // Log::error('Error al registrar movimiento de stock: ' . $e->getMessage());
        // return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

}