<?php

// app/Listeners/SendStockErrorNotification.php

namespace App\Listeners;

use App\Events\StockUpdateFailed;
use Illuminate\Support\Facades\Log;

// (Acá importarías tu servicio de Notificación, ej: Mail, WhatsApp)

class SendStockErrorNotification
{
    public function handle(StockUpdateFailed $event): void
    {
        $ventaId = $event->venta->venta_id;
        $error = $event->exception->getMessage();

        // ¡Alerta! ¡Alerta!
        Log::critical("¡FALLO CRÍTICO DE STOCK! Venta ID: {$ventaId}. Error: {$error}");

        // TODO: Enviar notificación al Admin
        // Cuando tengas tu sistema de notificación:
        // $admin = User::find(1); // O buscar por rol
        // $admin->notify(new StockFalloNotification($event->venta, $error));
    }
}
