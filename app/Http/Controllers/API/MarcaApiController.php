<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarcaApiController extends Controller
{
    public function index(): JsonResponse
    {
        $marcas = Marca::orderBy('nombre')->get();
        return response()->json($marcas);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:marcas,nombre',
        ]);

        try {
            $marca = Marca::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Marca creada exitosamente',
                'data' => $marca
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la marca: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Marca $marca): JsonResponse
    {
        try {
            // Verificar si hay productos asociados
            if ($marca->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen productos asociados a esta marca.'
                ], 422);
            }

            // Verificar si hay reparaciones asociadas
            if ($marca->reparaciones()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen reparaciones asociadas a esta marca.'
                ], 422);
            }

            // Verificar si hay modelos asociados
            if ($marca->modelos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen modelos asociados a esta marca.'
                ], 422);
            }

            $marca->delete();

            return response()->json([
                'success' => true,
                'message' => 'Marca eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la marca: ' . $e->getMessage()
            ], 500);
        }
    }
}
