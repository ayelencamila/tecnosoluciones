<?php

namespace App\Http\Controllers;

use App\Models\Localidad;
use App\Models\Provincia;
use Illuminate\Http\Request;

class LocalidadController extends Controller
{
    /**
     * Obtiene las localidades de una provincia específica
     * 
     * @param Provincia $provincia
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocalidadesByProvincia(Provincia $provincia)
    {
        try {
            $localidades = Localidad::where('provinciaID', $provincia->provinciaID)
                ->orderBy('nombre')
                ->get(['localidadID', 'nombre', 'provinciaID']);

            return response()->json($localidades);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las localidades',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
