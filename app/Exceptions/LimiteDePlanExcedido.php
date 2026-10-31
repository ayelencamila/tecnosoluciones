<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se alcanzó un límite DURO del plan contratado.
 *
 * Solo la lanzan los límites de capacidad (usuarios, depósitos). Los límites
 * de volumen operativo nunca bloquean, así que nunca llegan acá: se avisan.
 */
class LimiteDePlanExcedido extends RuntimeException
{
    public function __construct(
        public readonly string $clave,
        public readonly int $limite,
        public readonly int $consumo,
        string $mensaje
    ) {
        parent::__construct($mensaje);
    }
}
