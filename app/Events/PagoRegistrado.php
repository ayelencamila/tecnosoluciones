<?php

namespace App\Events;

use App\Models\Pago;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se registra un pago en el sistema
 * Usado para verificar normalización de cuenta corriente (CU-09 Paso 7)
 */
class PagoRegistrado
{
    use Dispatchable, SerializesModels;

    public Pago $pago;
    public int $userID;

    public function __construct(Pago $pago, int $userID)
    {
        $this->pago = $pago;
        $this->userID = $userID;
    }
}
