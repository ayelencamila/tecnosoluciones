<?php

namespace App\Exceptions\Ventas;

use Exception;

class VentaYaAnuladaException extends Exception
{
    public function __construct(string $numeroComprobante)
    {
        $mensaje = "La venta ({$numeroComprobante}) ya se encuentra anulada.";
        parent::__construct($mensaje);
    }
}
