<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedioPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedioPagoApiController extends Controller
{
    public function index(): JsonResponse
    {
        $medios = MedioPago::where('activo', true)->orderBy('nombre')->get();
        return response()->json($medios);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:medios_pago,nombre',
            'recargo_porcentaje' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $medio = MedioPago::create([
                'nombre' => $request->nombre,
                'recargo_porcentaje' => $request->recargo_porcentaje ?? 0,
                'activo' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Medio de pago creado exitosamente',
                'data' => $medio
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el medio de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(MedioPago $medioPago): JsonResponse
    {
        try {
            // Verificar si hay ventas asociadas
            if ($medioPago->ventas()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen ventas asociadas a este medio de pago.'
                ], 422);
            }

            $medioPago->delete();

            return response()->json([
                'success' => true,
                'message' => 'Medio de pago eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el medio de pago: ' . $e->getMessage()
            ], 500);
        }
    }
}
