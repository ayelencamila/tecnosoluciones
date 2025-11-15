<?php

namespace App\Exceptions\Ventas;

use Exception;

class SinStockException extends Exception
{
    public function __construct(string $nombreProducto, int $cantidadPedida, int $stockActual)
    {
        $mensaje = "Stock insuficiente para '{$nombreProducto}'. Solicitado: {$cantidadPedida}, Disponible: {$stockActual}.";
        parent::__construct($mensaje);
    }
}
