<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaProductoApiController extends Controller
{
    public function index(): JsonResponse
    {
        $categorias = CategoriaProducto::orderBy('nombre')->get();
        return response()->json($categorias);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_producto,nombre',
        ]);

        try {
            $categoria = CategoriaProducto::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(CategoriaProducto $categoriaProducto): JsonResponse
    {
        try {
            // Verificar si hay productos asociados
            if ($categoriaProducto->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen productos asociados a esta categoría.'
                ], 422);
            }

            $categoriaProducto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría: ' . $e->getMessage()
            ], 500);
        }
    }
}
