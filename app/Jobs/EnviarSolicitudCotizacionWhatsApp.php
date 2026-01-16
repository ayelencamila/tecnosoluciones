<?php

namespace App\Jobs;

use App\Models\CotizacionProveedor;
use App\Models\Configuracion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Twilio\Rest\Client;
use Exception;

/**
 * Job para enviar Solicitud de Cotización por WhatsApp (CU-20)
 * 
 * Envía el Magic Link al proveedor para que pueda cotizar
 * desde el portal público sin necesidad de autenticación.
 * 
 * Lineamientos aplicados:
 * - Larman: Separación de responsabilidades (envío asíncrono)
 * - Kendall: Magic Link para acceso externo seguro
 * - Twilio WhatsApp Business API
 */
class EnviarSolicitudCotizacionWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Configuración de reintentos
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected CotizacionProveedor $cotizacion;

    public function __construct(CotizacionProveedor $cotizacion)
    {
        $this->cotizacion = $cotizacion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Control de Ventana Horaria
        $inicioStr = Configuracion::get('whatsapp_horario_inicio', '09:00');
        $finStr = Configuracion::get('whatsapp_horario_fin', '20:00');

        $ahora = Carbon::now();
        $inicio = Carbon::createFromTimeString($inicioStr);
        $fin = Carbon::createFromTimeString($finStr);

        if (!$ahora->between($inicio, $fin)) {
            $this->posponerEnvio($ahora, $inicio);
            return;
        }

        // 2. Cargar datos necesarios
        $this->cotizacion->load(['solicitud.detalles.producto', 'proveedor']);
        $proveedor = $this->cotizacion->proveedor;
        $solicitud = $this->cotizacion->solicitud;

        // 3. Validar teléfono
        $telefonoDestino = $proveedor->whatsapp ?? $proveedor->telefono;
        if (!$telefonoDestino) {
            Log::warning("⚠️ Proveedor {$proveedor->razon_social} sin teléfono. Solicitud no enviada.");
            $this->cotizacion->marcarEnvioFallido();
            return;
        }

        // Formato internacional para Twilio
        if (!str_starts_with($telefonoDestino, '+')) {
            $telefonoDestino = '+549' . ltrim($telefonoDestino, '0');
        }
        $telefonoTwilio = 'whatsapp:' . $telefonoDestino;

        // 4. Construir mensaje con Magic Link
        $mensaje = $this->construirMensaje();

        Log::info("📱 Enviando solicitud cotización {$solicitud->codigo_solicitud} a {$proveedor->razon_social}");

        // 5. Envío via Twilio
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = 'whatsapp:' . config('services.twilio.whatsapp_from');

            if (!$sid || !$token || !$from) {
                throw new Exception("Credenciales de Twilio no configuradas");
            }

            $twilio = new Client($sid, $token);

            $twilio->messages->create($telefonoTwilio, [
                'from' => $from,
                'body' => $mensaje,
            ]);

            // 6. Marcar como enviada
            $this->cotizacion->marcarEnviado();

            Log::info("✅ WhatsApp enviado - Solicitud {$solicitud->codigo_solicitud} a {$proveedor->razon_social}");

        } catch (Exception $e) {
            Log::error("❌ Error enviando WhatsApp solicitud {$solicitud->codigo_solicitud}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Construye el mensaje de WhatsApp con el Magic Link
     */
    protected function construirMensaje(): string
    {
        $solicitud = $this->cotizacion->solicitud;
        $proveedor = $this->cotizacion->proveedor;
        $magicLink = $this->cotizacion->generarMagicLink();

        $lineas = [
            "📋 *SOLICITUD DE COTIZACIÓN*",
            "",
            "Estimado/a *{$proveedor->razon_social}*,",
            "",
            "Le invitamos a cotizar los siguientes productos:",
            "",
        ];

        // Lista de productos
        foreach ($solicitud->detalles as $detalle) {
            $producto = $detalle->producto;
            $lineas[] = "• {$producto->nombre} - Cantidad: {$detalle->cantidad_sugerida}";
        }

        $lineas[] = "";
        $lineas[] = "*Fecha límite:* " . $solicitud->fecha_vencimiento->format('d/m/Y');
        $lineas[] = "";
        $lineas[] = "🔗 *Para cotizar, ingrese al siguiente enlace:*";
        $lineas[] = $magicLink;
        $lineas[] = "";
        $lineas[] = "_Este enlace es único y personal. No lo comparta._";
        $lineas[] = "";
        $lineas[] = "Gracias por su colaboración.";
        $lineas[] = "*TecnoSoluciones*";

        return implode("\n", $lineas);
    }

    /**
     * Pospone el envío hasta el horario permitido
     */
    protected function posponerEnvio($ahora, $inicio): void
    {
        $segundosEspera = $ahora->gt($inicio) 
            ? $inicio->addDay()->diffInSeconds($ahora) 
            : $inicio->diffInSeconds($ahora);

        Log::info("⏰ Solicitud cotización pospuesta. Fuera de horario. Reintentando en {$segundosEspera}s");
        
        $this->release($segundosEspera);
    }

    /**
     * Maneja el fallo definitivo del Job
     */
    public function failed(Exception $exception): void
    {
        $solicitud = $this->cotizacion->solicitud;
        Log::error("💀 Solicitud {$solicitud->codigo_solicitud} falló: " . $exception->getMessage());
        
        $this->cotizacion->marcarEnvioFallido();
    }
}
