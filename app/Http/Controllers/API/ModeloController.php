<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Modelo;
use Illuminate\Http\JsonResponse;

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
}
