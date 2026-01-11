<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProvinciaApiController extends Controller
{
    /**
     * Crear una nueva provincia (API rápida).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:provincias,nombre',
        ]);

        $provincia = Provincia::create([
            'nombre' => $request->nombre,
        ]);

        return response()->json([
            'success' => true,
            'data' => $provincia,
            'message' => 'Provincia creada exitosamente'
        ], 201);
    }

    /**
     * Eliminar una provincia (API rápida).
     */
    public function destroy(Provincia $provincia): JsonResponse
    {
        try {
            $provincia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Provincia eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar. La provincia está siendo utilizada por localidades o direcciones existentes.'
            ], 422);
        }
    }
}
