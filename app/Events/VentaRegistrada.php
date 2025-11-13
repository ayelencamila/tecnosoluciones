<?php

// app/Events/VentaRegistrada.php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VentaRegistrada
{
    use Dispatchable, SerializesModels;

    /**
     * @param  Venta  $venta  El modelo Venta con todas sus relaciones cargadas
     * @param  string  $metodoPago  El método de pago (ej: 'cuenta_corriente')
     */
    public function __construct(
        public Venta $venta,
        public string $metodoPago
    ) {
        //
    }
}
