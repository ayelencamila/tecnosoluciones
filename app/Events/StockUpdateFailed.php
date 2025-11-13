<?php

// app/Events/StockUpdateFailed.php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable; // Importante para capturar el error

class StockUpdateFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Venta $venta,
        public Throwable $exception // Pasamos la excepción completa
    ) {
        //
    }
}
