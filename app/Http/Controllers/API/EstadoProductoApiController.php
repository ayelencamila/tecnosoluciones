<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstadoProductoApiController extends Controller
{
    public function index(): JsonResponse
    {
        $estados = EstadoProducto::orderBy('nombre')->get();
        return response()->json($estados);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:estados_producto,nombre',
        ]);

        try {
            $estado = EstadoProducto::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado creado exitosamente',
                'data' => $estado
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(EstadoProducto $estadoProducto): JsonResponse
    {
        try {
            // Verificar si hay productos asociados
            if ($estadoProducto->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen productos asociados a este estado.'
                ], 422);
            }

            $estadoProducto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Estado eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el estado: ' . $e->getMessage()
            ], 500);
        }
    }
}
