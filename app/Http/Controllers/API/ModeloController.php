<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Modelo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModeloController extends Controller
{
    public function index($marcaId): JsonResponse
    {
        // Buscamos modelos de esa marca que estén ACTIVOS
        $modelos = Modelo::where('marca_id', $marcaId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($modelos);
    }

    /**
     * Lista modelos filtrados por marca_id (query param)
     */
    public function indexAll(Request $request): JsonResponse
    {
        $marcaId = $request->query('marca_id');
        
        if ($marcaId) {
            $modelos = Modelo::where('marca_id', $marcaId)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']);
        } else {
            $modelos = Modelo::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'marca_id']);
        }

        return response()->json($modelos);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'marca_id' => 'required|exists:marcas,id',
        ]);

        try {
            // Verificar que no exista ya un modelo con ese nombre para esa marca
            $existe = Modelo::where('marca_id', $request->marca_id)
                ->where('nombre', $request->nombre)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un modelo con ese nombre para esta marca.'
                ], 422);
            }

            $modelo = Modelo::create([
                'nombre' => $request->nombre,
                'marca_id' => $request->marca_id,
                'activo' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Modelo creado exitosamente',
                'data' => $modelo
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el modelo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Modelo $modelo): JsonResponse
    {
        try {
            // Verificar si hay reparaciones asociadas
            if ($modelo->reparaciones()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. Existen reparaciones asociadas a este modelo.'
                ], 422);
            }

            $modelo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Modelo eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el modelo: ' . $e->getMessage()
            ], 500);
        }
    }
}
