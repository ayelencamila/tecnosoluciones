<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnidadMedidaApiController extends Controller
{
    public function index(): JsonResponse
    {
        $unidades = UnidadMedida::orderBy('nombre')->get();
        return response()->json($unidades);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:unidades_medida,nombre',
            'abreviatura' => 'nullable|string|max:10',
        ]);

        try {
            // Si no viene abreviatura, generar una automática (primeras 3 letras)
            $abreviatura = $request->abreviatura ?? strtoupper(substr($request->nombre, 0, 3));
            
            $unidad = UnidadMedida::create([
                'nombre' => $request->nombre,
                'abreviatura' => $abreviatura,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida creada exitosamente',
                'data' => $unidad
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la unidad de medida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(UnidadMedida $unidadMedida): JsonResponse
    {
        try {
            // Verificar si hay productos asociados
            if ($unidadMedida->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen productos asociados a esta unidad de medida.'
                ], 422);
            }

            $unidadMedida->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la unidad de medida: ' . $e->getMessage()
            ], 500);
        }
    }
}
