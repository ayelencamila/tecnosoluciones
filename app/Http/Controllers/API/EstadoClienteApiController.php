<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoCliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EstadoClienteApiController extends Controller
{
    /**
     * Crear un nuevo estado de cliente (API rápida).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombreEstado' => 'required|string|max:50|unique:estados_cliente,nombreEstado',
        ]);

        $estado = EstadoCliente::create([
            'nombreEstado' => $request->nombreEstado,
        ]);

        return response()->json([
            'success' => true,
            'data' => $estado,
            'message' => 'Estado de cliente creado exitosamente'
        ], 201);
    }

    /**
     * Eliminar un estado de cliente (API rápida).
     */
    public function destroy(EstadoCliente $estadoCliente): JsonResponse
    {
        try {
            $estadoCliente->delete();

            return response()->json([
                'success' => true,
                'message' => 'Estado de cliente eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar. El estado está siendo utilizado por clientes existentes.'
            ], 422);
        }
    }
}
