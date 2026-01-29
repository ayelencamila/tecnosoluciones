<?php

namespace App\Jobs;

use App\Models\CotizacionProveedor;
use App\Models\Configuracion;
use App\Models\PlantillaWhatsapp;
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
 * Usa plantillas configurables (CU-30) con horarios específicos.
 * 
 * Lineamientos aplicados:
 * - Larman: Separación de responsabilidades (envío asíncrono)
 * - Kendall: Magic Link para acceso externo seguro
 * - Twilio WhatsApp Business API
 */
class EnviarSolicitudCotizacionWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Configuración de reintentos para ERRORES reales (no para posponer)
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    
    // Tiempo máximo antes de que expire el job (7 días)
    public $retryUntil;

    protected CotizacionProveedor $cotizacion;
    protected bool $esRecordatorio;

    public function __construct(CotizacionProveedor $cotizacion, bool $esRecordatorio = false)
    {
        $this->cotizacion = $cotizacion;
        $this->esRecordatorio = $esRecordatorio;
        $this->retryUntil = now()->addDays(7);
    }

    /**
     * Determina el tiempo máximo de reintentos
     */
    public function retryUntil(): \DateTime
    {
        return $this->retryUntil ?? now()->addDays(7);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Obtener plantilla según tipo (solicitud o recordatorio)
        $tipoPlantilla = $this->esRecordatorio ? 'recordatorio_cotizacion' : 'solicitud_cotizacion';
        $plantilla = PlantillaWhatsapp::obtenerPorTipo($tipoPlantilla);

        if (!$plantilla) {
            Log::warning("⚠️ Plantilla {$tipoPlantilla} no encontrada o inactiva. Usando mensaje por defecto.");
        }

        // 2. Control de Ventana Horaria - Usa horario de plantilla o global
        if ($plantilla && !$plantilla->estaEnHorarioPermitido()) {
            $segundosEspera = $plantilla->segundosHastaProximoEnvio();
            
            Log::info("⏰ WhatsApp solicitud pospuesta. Fuera de horario de plantilla. Reenviando en {$segundosEspera}s");
            
            // Re-despachar como NUEVO job (no consume intentos)
            self::dispatch($this->cotizacion, $this->esRecordatorio)
                ->delay(now()->addSeconds($segundosEspera));
            
            $this->delete();
            return;
        } elseif (!$plantilla) {
            // Fallback a horarios globales si no hay plantilla
            $inicioStr = Configuracion::get('whatsapp_horario_inicio', '09:00');
            $finStr = Configuracion::get('whatsapp_horario_fin', '20:00');

            $ahora = Carbon::now();
            $inicio = Carbon::createFromTimeString($inicioStr);
            $fin = Carbon::createFromTimeString($finStr);

            if (!$ahora->between($inicio, $fin)) {
                $segundosEspera = $this->calcularSegundosHastaProximoHorario($ahora, $inicio);
                
                Log::info("⏰ WhatsApp solicitud pospuesta. Fuera de horario global ({$ahora->format('H:i')}). Reenviando en {$segundosEspera}s");
                
                self::dispatch($this->cotizacion, $this->esRecordatorio)
                    ->delay(now()->addSeconds($segundosEspera));
                
                $this->delete();
                return;
            }
        }

        // 3. Cargar datos necesarios
        $this->cotizacion->load(['solicitud.detalles.producto', 'proveedor']);
        $proveedor = $this->cotizacion->proveedor;
        $solicitud = $this->cotizacion->solicitud;

        // 4. Validar teléfono
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

        // 5. Construir mensaje usando plantilla o fallback
        $mensaje = $this->construirMensaje($plantilla);

        Log::info("📱 Enviando solicitud cotización {$solicitud->codigo_solicitud} a {$proveedor->razon_social}" . ($this->esRecordatorio ? ' (RECORDATORIO)' : ''));

        // 6. Envío via Twilio
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

            // 7. Marcar como enviada (solo si no es recordatorio)
            if (!$this->esRecordatorio) {
                $this->cotizacion->marcarEnviado();
            }

            Log::info("✅ WhatsApp enviado - Solicitud {$solicitud->codigo_solicitud} a {$proveedor->razon_social}" . ($this->esRecordatorio ? ' (RECORDATORIO)' : ''));

        } catch (Exception $e) {
            Log::error("❌ Error enviando WhatsApp solicitud {$solicitud->codigo_solicitud}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Construye el mensaje de WhatsApp usando plantilla configurable (CU-30)
     */
    protected function construirMensaje(?PlantillaWhatsapp $plantilla): string
    {
        $solicitud = $this->cotizacion->solicitud;
        $proveedor = $this->cotizacion->proveedor;
        $magicLink = $this->cotizacion->generarMagicLink();

        // Preparar lista de productos
        $listaProductos = [];
        foreach ($solicitud->detalles as $detalle) {
            $producto = $detalle->producto;
            $listaProductos[] = "• {$producto->nombre} - Cantidad: {$detalle->cantidad_sugerida}";
        }

        // Datos para compilar plantilla
        $datos = [
            'razon_social' => $proveedor->razon_social,
            'lista_productos' => implode("\n", $listaProductos),
            'fecha_vencimiento' => $solicitud->fecha_vencimiento->format('d/m/Y'),
            'magic_link' => $magicLink,
            'codigo_solicitud' => $solicitud->codigo_solicitud,
            'dias_restantes' => now()->diffInDays($solicitud->fecha_vencimiento, false),
        ];

        // Si hay plantilla activa, usarla
        if ($plantilla) {
            return $plantilla->compilar($datos);
        }

        // Fallback: mensaje hardcodeado si no hay plantilla
        return $this->construirMensajeFallback($datos);
    }

    /**
     * Mensaje de fallback si no hay plantilla configurada
     */
    protected function construirMensajeFallback(array $datos): string
    {
        if ($this->esRecordatorio) {
            $lineas = [
                "🔔 *RECORDATORIO - SOLICITUD DE COTIZACIÓN*",
                "",
                "Estimado/a *{$datos['razon_social']}*,",
                "",
                "Le recordamos que tenemos una solicitud de cotización pendiente.",
                "⏰ *Solo quedan {$datos['dias_restantes']} día(s) para responder.*",
                "",
                "*Productos solicitados:*",
                "",
                $datos['lista_productos'],
            ];
        } else {
            $lineas = [
                "📋 *SOLICITUD DE COTIZACIÓN*",
                "",
                "Estimado/a *{$datos['razon_social']}*,",
                "",
                "Le invitamos a cotizar los siguientes productos:",
                "",
                $datos['lista_productos'],
            ];
        }

        $lineas[] = "";
        $lineas[] = "*Fecha límite:* {$datos['fecha_vencimiento']}";
        $lineas[] = "";
        $lineas[] = "🔗 *Para cotizar, ingrese al siguiente enlace:*";
        $lineas[] = $datos['magic_link'];
        $lineas[] = "";
        $lineas[] = "_Este enlace es único y personal. No lo comparta._";
        $lineas[] = "";
        $lineas[] = "Gracias por su colaboración.";
        $lineas[] = "*TecnoSoluciones*";

        return implode("\n", $lineas);
    }

    /**
     * Calcula segundos hasta el próximo horario permitido
     */
    protected function calcularSegundosHastaProximoHorario(Carbon $ahora, Carbon $inicio): int
    {
        if ($ahora->gt($inicio)) {
            // Ya pasó el inicio de hoy, esperar hasta mañana
            return $inicio->copy()->addDay()->diffInSeconds($ahora);
        }
        // Aún no llegó el inicio de hoy
        return $inicio->diffInSeconds($ahora);
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
