<?php

namespace App\Jobs;

use App\Models\CuentaCorriente;
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

class NotificarIncumplimientoCC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Configuración de Reintentos (Excepción 7b del CU-09)
    public $tries = 3;
    public $backoff = [60, 300, 600];

    protected $cuentaCorriente;
    protected $motivo;
    protected $tipoAccion; // 'bloqueo', 'revision', 'recordatorio'

    public function __construct(CuentaCorriente $cuentaCorriente, string $motivo, string $tipoAccion)
    {
        $this->cuentaCorriente = $cuentaCorriente;
        $this->motivo = $motivo;
        $this->tipoAccion = $tipoAccion;
    }

    public function handle(): void
    {
        // 1. Control de Ventana Horaria (Excepción 7a)
        $inicioStr = Configuracion::get('whatsapp_horario_inicio', '09:00');
        $finStr = Configuracion::get('whatsapp_horario_fin', '20:00');

        $ahora = Carbon::now();
        $inicio = Carbon::createFromTimeString($inicioStr);
        $fin = Carbon::createFromTimeString($finStr);

        // Ajuste si el horario cruza la medianoche (ej: 22:00 a 06:00)
        if ($inicio->gt($fin)) {
            if ($ahora->lt($inicio) && $ahora->gt($fin)) {
                $this->posponerEnvio($ahora, $inicio);
                return;
            }
        } else {
            // Horario normal (ej: 09:00 a 20:00)
            if (!$ahora->between($inicio, $fin)) {
                $this->posponerEnvio($ahora, $inicio);
                return;
            }
        }

        // 2. Preparar Datos del Cliente
        $cliente = $this->cuentaCorriente->cliente;
        $telefonoDestino = $cliente->whatsapp ?? $cliente->telefono;

        if (!$telefonoDestino) {
            Log::warning("⚠️ No se pudo notificar al cliente {$cliente->clienteID}: Sin teléfono registrado.");
            return;
        }

        // Formato internacional para Twilio
        if (!str_starts_with($telefonoDestino, '+')) {
            $telefonoDestino = '+549' . ltrim($telefonoDestino, '0'); // Ajuste estándar Argentina móvil
        }
        $telefonoTwilio = 'whatsapp:' . $telefonoDestino;

        // 3. Construcción del Mensaje usando Plantillas Parametrizables (CU-30)
        $mensaje = $this->construirMensaje($cliente->nombre, $this->motivo);

        // 4. Notificación Interna
        Log::alert("🚨 NOTIFICACIÓN INTERNA: Cliente {$cliente->nombre} - Acción: {$this->tipoAccion} - Motivo: {$this->motivo}");

        // 5. Envío Real (WhatsApp via Twilio)
        try {
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');
            $from = 'whatsapp:' . env('TWILIO_WHATSAPP_FROM');

            if (!$sid || !$token || !$from) {
                throw new Exception("Credenciales de Twilio no configuradas en .env");
            }

            $twilio = new Client($sid, $token);

            $twilio->messages->create($telefonoTwilio, [
                'from' => $from,
                'body' => $mensaje
            ]);

            Log::info("✅ WhatsApp enviado exitosamente a {$cliente->nombre} ({$telefonoTwilio})");

        } catch (Exception $e) {
            // Excepción 7b: Falla en la entrega
            Log::error("❌ Error enviando WhatsApp a {$cliente->nombre}: " . $e->getMessage());
            throw $e; // Dispara el reintento automático
        }
    }

    private function posponerEnvio($ahora, $inicio)
    {
        $segundosEspera = $ahora->gt($inicio) 
            ? $ahora->diffInSeconds($inicio->addDay()) 
            : $ahora->diffInSeconds($inicio);

        Log::info("⏳ [CU-09] Envío pospuesto por horario. Esperando {$segundosEspera}s.");
        $this->release($segundosEspera);
    }

    private function construirMensaje($nombreCliente, $motivo)
    {
        // Mapeo de tipo de acción a clave de configuración
        $clavePlantilla = match ($this->tipoAccion) {
            'bloqueo' => 'whatsapp_plantilla_bloqueo',
            'revision' => 'whatsapp_plantilla_revision',
            'recordatorio' => 'whatsapp_plantilla_recordatorio',
            default => null,
        };

        $plantilla = null;
        if ($clavePlantilla) {
            $plantilla = Configuracion::get($clavePlantilla);
        }

        // Si no hay plantilla configurada, usamos un fallback genérico seguro
        if (!$plantilla) {
            return "Hola {$nombreCliente}, le informamos desde TecnoSoluciones sobre el estado de su cuenta: {$motivo}.";
        }

        // Reemplazo de variables dinámicas
        return str_replace(
            ['[nombre_cliente]', '[motivo]'],
            [$nombreCliente, $motivo],
            $plantilla
        );
    }
}