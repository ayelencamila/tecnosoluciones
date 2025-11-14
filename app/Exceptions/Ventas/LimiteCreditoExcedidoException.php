<?php

namespace App\Exceptions\Ventas;

use Exception;

class LimiteCreditoExcedidoException extends Exception
{
    public function __construct(string $nombreCliente, float $montoVenta, float $limiteDisponible)
    {
        $mensaje = "Límite de crédito excedido para {$nombreCliente}. Intenta comprar por {$montoVenta} y su límite disponible es {$limiteDisponible}.";
        parent::__construct($mensaje);
    }
}
