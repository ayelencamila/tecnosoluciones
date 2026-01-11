<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoCliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TipoClienteApiController extends Controller
{
    /**
     * Crear un nuevo tipo de cliente (API rápida).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombreTipo' => 'required|string|max:50|unique:tipos_cliente,nombreTipo',
        ]);

        $tipo = TipoCliente::create([
            'nombreTipo' => $request->nombreTipo,
        ]);

        return response()->json([
            'success' => true,
            'data' => $tipo,
            'message' => 'Tipo de cliente creado exitosamente'
        ], 201);
    }

    /**
     * Eliminar un tipo de cliente (API rápida).
     */
    public function destroy(TipoCliente $tipoCliente): JsonResponse
    {
        try {
            $tipoCliente->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cliente eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar. El tipo está siendo utilizado por clientes existentes.'
            ], 422);
        }
    }
}
