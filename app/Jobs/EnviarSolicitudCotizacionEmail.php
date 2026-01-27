<?php

namespace App\Jobs;

use App\Mail\SolicitudCotizacionMail;
use App\Models\CotizacionProveedor;
use App\Models\Configuracion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;

/**
 * Job para enviar Solicitud de Cotización por Email (CU-20)
 * 
 * Envía el Magic Link al proveedor para que pueda cotizar
 * desde el portal público sin necesidad de autenticación.
 * 
 * Lineamientos aplicados:
 * - Larman: Separación de responsabilidades (envío asíncrono)
 * - Kendall: Magic Link para acceso externo seguro
 * - Sommerville: Manejo robusto de excepciones con reintentos
 * 
 * Ventajas del Email:
 * - Más profesional y formal
 * - Sin costos de API (a diferencia de WhatsApp)
 * - Mejor trazabilidad
 * - Puede incluir adjuntos (PDF de la solicitud)
 */
class EnviarSolicitudCotizacionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Configuración de reintentos
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected CotizacionProveedor $cotizacion;
    protected bool $esRecordatorio;

    public function __construct(CotizacionProveedor $cotizacion, bool $esRecordatorio = false)
    {
        $this->cotizacion = $cotizacion;
        $this->esRecordatorio = $esRecordatorio;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Validar email del proveedor
        $proveedor = $this->cotizacion->proveedor;
        
        if (empty($proveedor->email)) {
            Log::warning("⚠️ Proveedor {$proveedor->razon_social} sin email. Solicitud #{$this->cotizacion->solicitud_id} no enviada.");
            $this->cotizacion->update(['enviado_email' => false]);
            return;
        }

        // 2. Generar URL del Magic Link
        // Usar NGROK_URL si está disponible (para desarrollo) o APP_URL en producción
        $baseUrl = config('app.ngrok_url') ?: config('app.url');
        $url = $baseUrl . route('portal.cotizacion', ['token' => $this->cotizacion->token_unico], false);

        // 3. Log de intento
        $tipo = $this->esRecordatorio ? 'RECORDATORIO' : 'SOLICITUD';
        Log::info("📧 Enviando {$tipo} por Email a {$proveedor->razon_social} ({$proveedor->email})");

        try {
            // 4. Enviar email con Mailable
            Mail::to($proveedor->email)
                ->send(new SolicitudCotizacionMail(
                    cotizacion: $this->cotizacion,
                    url: $url,
                    esRecordatorio: $this->esRecordatorio
                ));

            // 5. Actualizar registro de envío
            $this->cotizacion->update([
                'enviado_email' => true,
                'fecha_envio_email' => now(),
            ]);
            
            // Marcar como enviada (solo si no es recordatorio)
            if (!$this->esRecordatorio) {
                $this->cotizacion->marcarEnviado();
            }

            Log::info("✅ Email enviado exitosamente a {$proveedor->razon_social}");

        } catch (Exception $e) {
            Log::error("❌ Error al enviar email a {$proveedor->razon_social}: " . $e->getMessage());
            
            // Marcar como fallido después de todos los reintentos
            if ($this->attempts() >= $this->tries) {
                $this->cotizacion->update([
                    'enviado_email' => false,
                    'error_envio_email' => $e->getMessage(),
                ]);
            }
            
            throw $e; // Re-lanzar para que se reintente
        }
    }

    /**
     * Manejo de fallo después de todos los reintentos
     */
    public function failed(Exception $exception): void
    {
        Log::error("❌ Envío de email FALLIDO definitivamente para solicitud #{$this->cotizacion->solicitud_id}: " . $exception->getMessage());
        
        $this->cotizacion->update([
            'enviado_email' => false,
            'error_envio_email' => $exception->getMessage(),
        ]);
    }
}
