<?php

namespace App\Jobs;

use App\Models\OrdenCompra;
use App\Models\Configuracion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Twilio\Rest\Client;
use Exception;

/**
 * Job para enviar Orden de Compra por WhatsApp al proveedor (CU-22)
 * 
 * Lineamientos aplicados:
 * - Larman: Separación de responsabilidades (envío asíncrono)
 * - Kendall: Trazabilidad completa de notificaciones
 * - Twilio WhatsApp Business API
 * 
 * Manejo de Excepciones:
 * - Reintento automático (3 intentos con backoff exponencial)
 * - Si falla, marca la orden como "Envío Fallido" y notifica internamente
 */
class EnviarOrdenCompraWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Configuración de reintentos (Excepción 11b CU-22)
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected OrdenCompra $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Control de Ventana Horaria (igual que otros Jobs de WhatsApp)
        $inicioStr = Configuracion::get('whatsapp_horario_inicio', '09:00');
        $finStr = Configuracion::get('whatsapp_horario_fin', '20:00');

        $ahora = Carbon::now();
        $inicio = Carbon::createFromTimeString($inicioStr);
        $fin = Carbon::createFromTimeString($finStr);

        // Horario normal (09:00 a 20:00)
        if (!$ahora->between($inicio, $fin)) {
            $this->posponerEnvio($ahora, $inicio);
            return;
        }

        // 2. Preparar datos del proveedor
        $proveedor = $this->orden->proveedor;
        
        $telefonoDestino = $proveedor->whatsapp ?? $proveedor->telefono;
        if (!$telefonoDestino) {
            Log::warning("⚠️ Proveedor {$proveedor->razon_social} sin teléfono. OC {$this->orden->numero_oc} no enviada.");
            $this->orden->marcarEnvioFallido();
            return;
        }

        // Formato internacional para Twilio
        if (!str_starts_with($telefonoDestino, '+')) {
            $telefonoDestino = '+549' . ltrim($telefonoDestino, '0'); // Formato Argentina móvil
        }
        $telefonoTwilio = 'whatsapp:' . $telefonoDestino;

        // 3. Construir mensaje
        $mensaje = $this->construirMensaje();

        // 4. Log de intento
        Log::info("📱 Enviando OC {$this->orden->numero_oc} por WhatsApp a {$proveedor->razon_social}");

        // 5. Envío via Twilio
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = 'whatsapp:' . config('services.twilio.whatsapp_from');

            if (!$sid || !$token || !$from) {
                throw new Exception("Credenciales de Twilio no configuradas en config/services.php");
            }

            $twilio = new Client($sid, $token);

            // Enviar mensaje con PDF adjunto si está disponible
            $mensajeConfig = [
                'from' => $from,
                'body' => $mensaje,
            ];

            // Si hay PDF, incluir como mediaUrl
            if ($this->orden->archivo_pdf && Storage::disk('public')->exists($this->orden->archivo_pdf)) {
                $pdfUrl = asset('storage/' . $this->orden->archivo_pdf);
                $mensajeConfig['mediaUrl'] = [$pdfUrl];
            }

            $twilio->messages->create($telefonoTwilio, $mensajeConfig);

            // 6. Marcar como enviada
            $this->orden->marcarEnviada();

            Log::info("✅ WhatsApp enviado exitosamente - OC {$this->orden->numero_oc} a {$proveedor->razon_social}");

        } catch (Exception $e) {
            Log::error("❌ Error enviando WhatsApp OC {$this->orden->numero_oc}: " . $e->getMessage());
            throw $e; // Dispara reintento automático
        }
    }

    /**
     * Construye el mensaje de WhatsApp para el proveedor
     */
    protected function construirMensaje(): string
    {
        $orden = $this->orden->load(['detalles.producto', 'usuario']);
        $proveedor = $this->orden->proveedor;

        $lineas = [
            "📋 *ORDEN DE COMPRA*",
            "",
            "*N° OC:* {$orden->numero_oc}",
            "*Fecha:* " . $orden->fecha_emision->format('d/m/Y H:i'),
            "*Proveedor:* {$proveedor->razon_social}",
            "",
            "*Productos solicitados:*",
        ];

        foreach ($orden->detalles as $detalle) {
            $producto = $detalle->producto;
            $nombreProducto = $producto ? $producto->nombre : "Producto #{$detalle->producto_id}";
            $lineas[] = "• {$nombreProducto} x{$detalle->cantidad_pedida} - \${$detalle->precio_unitario}";
        }

        $lineas[] = "";
        $lineas[] = "*Total:* \${$orden->total_final}";
        
        if ($orden->observaciones) {
            $lineas[] = "";
            $lineas[] = "*Instrucciones:*";
            $lineas[] = $orden->observaciones;
        }

        $lineas[] = "";
        $lineas[] = "_Por favor confirme recepción de esta orden._";
        $lineas[] = "";
        $lineas[] = "TecnoSoluciones - Sistema de Compras";

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

        Log::info("⏰ WhatsApp OC {$this->orden->numero_oc} pospuesto. Fuera de horario. Reintentando en {$segundosEspera}s");
        
        $this->release($segundosEspera);
    }

    /**
     * Maneja el fallo definitivo del Job
     */
    public function failed(Exception $exception): void
    {
        Log::error("💀 WhatsApp OC {$this->orden->numero_oc} falló definitivamente: " . $exception->getMessage());
        
        // Marcar orden como envío fallido
        $this->orden->marcarEnvioFallido();
    }
}
