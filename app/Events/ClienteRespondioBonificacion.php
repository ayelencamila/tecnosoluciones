<?php

namespace App\Events;

use App\Models\BonificacionReparacion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando el cliente responde (acepta/rechaza) una bonificación
 * CU-14/CU-15: Parte del flujo de decisión del cliente
 */
class ClienteRespondioBonificacion
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BonificacionReparacion $bonificacion;
    public string $decision; // 'continuar' o 'cancelar'

    /**
     * Create a new event instance.
     */
    public function __construct(BonificacionReparacion $bonificacion, string $decision)
    {
        $this->bonificacion = $bonificacion;
        $this->decision = $decision;
    }
}
