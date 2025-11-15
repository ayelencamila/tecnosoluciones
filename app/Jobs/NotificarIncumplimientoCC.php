<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Configuracion; // Importamos
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarIncumplimientoCC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Cliente $cliente,
        public string $motivo
    ) {}

    public function handle(): void
    {
        // Leemos el número del admin desde la BD (CU-31)
        $adminWhatsapp = Configuracion::get('whatsapp_admin_notificaciones');

        // AQUÍ iría la integración real con WhatsApp (Twilio, Meta API, etc.)

        // SIMULAMOS el envío a la consola/log
        Log::channel('daily')->info("📢 [WHATSAPP SIMULADO] Para: {$adminWhatsapp} (Admin) | Mensaje: El cliente {$this->cliente->nombre_completo} (ID: {$this->cliente->clienteID}) entró en incumplimiento. {$this->motivo}");
    }
}
