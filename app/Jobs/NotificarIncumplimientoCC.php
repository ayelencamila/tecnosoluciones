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
        // Usar horario específico de la plantilla, con fallback a configuración general
        $tipoEvento = match ($this->tipoAccion) {
            'admin_alert' => 'admin_alert_cc',
            'bloqueo' => 'bloqueo_cc',
            'revision' => 'revision_cc',
            default => null,
        };

        $plantilla = $tipoEvento ? \App\Models\PlantillaWhatsapp::obtenerPorTipo($tipoEvento) : null;
        
        if ($plantilla && $plantilla->horario_inicio && $plantilla->horario_fin) {
            // Usar horario de la plantilla (son objetos Carbon, extraer solo HH:MM)
            $inicioStr = $plantilla->horario_inicio instanceof Carbon 
                ? $plantilla->horario_inicio->format('H:i') 
                : (string) $plantilla->horario_inicio;
            $finStr = $plantilla->horario_fin instanceof Carbon 
                ? $plantilla->horario_fin->format('H:i') 
                : (string) $plantilla->horario_fin;
        } else {
            // Fallback a configuración general
            $inicioStr = Configuracion::get('whatsapp_horario_inicio', '09:00');
            $finStr = Configuracion::get('whatsapp_horario_fin', '20:00');
        }

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

        // 2. Preparar Datos del Destinatario
        $cliente = $this->cuentaCorriente->cliente;
        
        // Si es alerta de admin, usar teléfono del administrador
        if ($this->tipoAccion === 'admin_alert') {
            $telefonoDestino = Configuracion::get('whatsapp_admin_notificaciones');
            if (!$telefonoDestino) {
                Log::warning("⚠️ No hay teléfono de administrador configurado para WhatsApp.");
                return;
            }
        } else {
            // Para cliente normal
            $telefonoDestino = $cliente->whatsapp ?? $cliente->telefono;
            if (!$telefonoDestino) {
                Log::warning("⚠️ No se pudo notificar al cliente {$cliente->clienteID}: Sin teléfono registrado.");
                return;
            }
        }

        // Formato internacional para Twilio
        if (!str_starts_with($telefonoDestino, '+')) {
            $telefonoDestino = '+549' . ltrim($telefonoDestino, '0'); // Ajuste estándar Argentina móvil
        }
        $telefonoTwilio = 'whatsapp:' . $telefonoDestino;

        // 3. Construcción del Mensaje usando Plantillas Parametrizables (CU-30)
        $mensaje = $this->construirMensaje($cliente->nombre, $this->motivo);

        // 4. Notificación Interna
        $destinatarioLog = $this->tipoAccion === 'admin_alert' ? 'ADMIN' : "Cliente {$cliente->nombre}";
        Log::alert("🚨 NOTIFICACIÓN WHATSAPP: {$destinatarioLog} - Acción: {$this->tipoAccion} - Motivo: {$this->motivo}");

        // 5. Envío Real (WhatsApp via Twilio)
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = 'whatsapp:' . config('services.twilio.whatsapp_from');

            if (!$sid || !$token || !$from) {
                throw new Exception("Credenciales de Twilio no configuradas en config/services.php");
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

    /**
     * CU-30: Construir mensaje usando plantillas configurables
     * 
     * Variables disponibles en plantillas:
     * - nombre_cliente: Nombre del cliente
     * - motivo: Motivo del incumplimiento/alerta
     */
    private function construirMensaje($nombreCliente, $motivo)
    {
        // Mapeo de tipo de acción a tipo de evento de plantilla
        $tipoEvento = match ($this->tipoAccion) {
            'admin_alert' => 'admin_alert_cc',
            'bloqueo' => 'bloqueo_cc',
            'revision' => 'revision_cc',
            default => null,
        };

        if (!$tipoEvento) {
            // Fallback genérico si no se mapea el tipo
            return "Hola {$nombreCliente}, le informamos desde TecnoSoluciones sobre el estado de su cuenta: {$motivo}.";
        }

        // CU-30: Obtener plantilla activa
        $plantilla = \App\Models\PlantillaWhatsapp::obtenerPorTipo($tipoEvento);

        if (!$plantilla) {
            Log::warning("Plantilla {$tipoEvento} no encontrada, usando fallback");
            return "Hola {$nombreCliente}, le informamos desde TecnoSoluciones sobre el estado de su cuenta: {$motivo}.";
        }

        // CU-30: Compilar mensaje con datos
        return $plantilla->compilar([
            'nombre_cliente' => $nombreCliente,
            'motivo' => $motivo,
        ]);
    }
}