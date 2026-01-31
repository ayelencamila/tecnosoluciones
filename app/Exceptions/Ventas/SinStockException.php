<?php

namespace App\Exceptions\Ventas;

use Exception;

/**
 * CU-12 Excepción 5c: Repuesto sin stock disponible
 */
class SinStockException extends Exception
{
    public function __construct(string $nombreProducto, int $cantidadPedida, int $stockActual)
    {
        // CU-12: "Repuesto [nombre] no encontrado o sin stock disponible. Por favor, revise la cantidad o seleccione otro repuesto."
        $mensaje = "Repuesto '{$nombreProducto}' sin stock disponible. Solicitado: {$cantidadPedida}, Disponible: {$stockActual}. Por favor, revise la cantidad o seleccione otro repuesto.";
        parent::__construct($mensaje);
    }
}
