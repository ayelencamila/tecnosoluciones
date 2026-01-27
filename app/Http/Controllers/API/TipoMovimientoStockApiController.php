<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoMovimientoStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipoMovimientoStockApiController extends Controller
{
    public function index(): JsonResponse
    {
        $tipos = TipoMovimientoStock::where('activo', true)->orderBy('nombre')->get();
        return response()->json($tipos);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:tipos_movimiento_stock,nombre',
            'signo' => 'nullable|integer|in:-1,1',
        ]);

        try {
            // Si el nombre contiene "entrada", "ingreso", "compra" => signo positivo
            // Si contiene "salida", "egreso", "baja", "ajuste" => signo negativo
            $nombre = strtolower($request->nombre);
            $signo = $request->signo;
            
            if (!$signo) {
                if (preg_match('/(entrada|ingreso|compra|recepcion|devolucion)/i', $nombre)) {
                    $signo = 1;
                } else {
                    $signo = -1; // Por defecto salida
                }
            }

            $tipo = TipoMovimientoStock::create([
                'nombre' => $request->nombre,
                'signo' => $signo,
                'activo' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de movimiento creado exitosamente',
                'data' => $tipo
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tipo de movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(TipoMovimientoStock $tipoMovimientoStock): JsonResponse
    {
        try {
            // Verificar si hay movimientos asociados
            if ($tipoMovimientoStock->movimientos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen movimientos de stock asociados a este tipo.'
                ], 422);
            }

            $tipoMovimientoStock->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de movimiento eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de movimiento: ' . $e->getMessage()
            ], 500);
        }
    }
}
