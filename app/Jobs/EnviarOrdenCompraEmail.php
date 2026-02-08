<?php

namespace App\Jobs;

use App\Models\OrdenCompra;
use App\Models\EstadoOrdenCompra;
use App\Mail\OrdenCompraMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

/**
 * Job para enviar Orden de Compra por Email al proveedor
 * 
 * Incluye PDF adjunto de la OC.
 * 
 * Lineamientos:
 * - Larman: Separación de responsabilidades (envío asíncrono)
 * - Kendall: Trazabilidad de comunicaciones
 */
class EnviarOrdenCompraEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected OrdenCompra $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function handle(): void
    {
        $proveedor = $this->orden->proveedor;

        // Verificar que el proveedor tenga email
        if (!$proveedor->email) {
            Log::warning("⚠️ Proveedor {$proveedor->razon_social} sin email. OC {$this->orden->numero_oc} no enviada por email.");
            return;
        }

        Log::info("📧 Enviando OC {$this->orden->numero_oc} por email a {$proveedor->email}");

        try {
            Mail::to($proveedor->email)
                ->send(new OrdenCompraMail($this->orden));

            Log::info("✅ Email enviado exitosamente - OC {$this->orden->numero_oc} a {$proveedor->email}");

            // Si no se envió por WhatsApp, marcar como enviada
            if ($this->orden->estado->nombre !== EstadoOrdenCompra::ENVIADA) {
                $this->orden->marcarEnviada();
            }

        } catch (Exception $e) {
            Log::error("❌ Error enviando email OC {$this->orden->numero_oc}: " . $e->getMessage());
            throw $e; // Dispara reintento automático
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("❌ Fallo definitivo al enviar email OC {$this->orden->numero_oc} después de {$this->tries} intentos: " . $exception->getMessage());
    }
}
