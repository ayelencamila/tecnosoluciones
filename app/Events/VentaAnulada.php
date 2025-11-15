<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VentaAnulada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Venta $venta,
        public int $userID
    ) {
        //
    }
}
