<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Localidad;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocalidadApiController extends Controller
{
    /**
     * Listar localidades por provincia (API rápida).
     */
    public function index(Request $request): JsonResponse
    {
        $provinciaId = $request->query('provincia_id');
        
        if ($provinciaId) {
            $localidades = Localidad::where('provincia_id', $provinciaId)
                ->orderBy('nombre')
                ->get();
        } else {
            $localidades = Localidad::orderBy('nombre')->get();
        }
        
        return response()->json($localidades);
    }

    /**
     * Crear una nueva localidad (API rápida).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'provincia_id' => 'nullable|exists:provincias,provinciaID',
        ]);

        // Si no se especifica provincia_id, usar la de la sesión o contexto
        $provinciaId = $request->provincia_id ?? $request->header('X-Provincia-ID');

        if (!$provinciaId) {
            return response()->json([
                'success' => false,
                'message' => 'Debe seleccionar una provincia primero.'
            ], 422);
        }

        $localidad = Localidad::create([
            'nombre' => $request->nombre,
            'provinciaID' => $provinciaId,
        ]);

        return response()->json([
            'success' => true,
            'data' => $localidad,
            'message' => 'Localidad creada exitosamente'
        ], 201);
    }

    /**
     * Eliminar una localidad (API rápida).
     */
    public function destroy(Localidad $localidad): JsonResponse
    {
        try {
            $localidad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Localidad eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar. La localidad está siendo utilizada por direcciones existentes.'
            ], 422);
        }
    }
}
