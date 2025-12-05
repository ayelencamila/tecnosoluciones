<?php

namespace App\Jobs;

use App\Models\BonificacionReparacion;
use App\Models\PlantillaWhatsapp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Job para notificar a clientes sobre bonificaciones por demora (CU-14/15)
 * Implementa CU-30: Uso de plantillas configurables con horarios
 */
class NotificarBonificacionCliente implements ShouldQueue
{
    use Queueable;

    protected BonificacionReparacion $bonificacion;

    /**
     * Create a new job instance.
     */
    public function __construct(BonificacionReparacion $bonificacion)
    {
        $this->bonificacion = $bonificacion;
    }

    /**
     * Execute the job.
     * CU-30: Usa plantillas configurables con validación de horarios
     */
    public function handle(): void
    {
        try {
            // CU-30 Paso 3: Obtener plantilla activa para el tipo de evento
            $plantilla = PlantillaWhatsapp::where('tipo_evento', 'bonificacion_cliente')
                ->where('activo', true)
                ->first();
            
            if (!$plantilla) {
                Log::error('Plantilla bonificacion_cliente no encontrada o inactiva');
                return;
            }

            // CU-30 Paso 6: Verificar horario de envío (si el método existe)
            if (method_exists($plantilla, 'estaEnHorarioPermitido') && !$plantilla->estaEnHorarioPermitido()) {
                $segundos = $plantilla->segundosHastaProximoEnvio();
                Log::info("Envío de bonificación pospuesto por horario. Esperando {$segundos}s");
                $this->release($segundos);
                return;
            }

            // Cargar relaciones necesarias
            $this->bonificacion->load(['reparacion.cliente', 'motivoDemora']);

            $reparacion = $this->bonificacion->reparacion;
            $cliente = $reparacion->cliente;

            // Verificar que el cliente tenga teléfono
            if (!$cliente || !$cliente->telefono) {
                Log::warning('Cliente sin teléfono registrado', [
                    'bonificacion_id' => $this->bonificacion->bonificacionID,
                    'cliente_id' => $cliente?->clienteID,
                ]);
                return;
            }

            // Generar token para respuesta del cliente
            $token = \App\Http\Controllers\Api\ClienteBonificacionController::generarToken($this->bonificacion->bonificacionID);
            $baseUrl = env('NGROK_URL', config('app.url'));
            $urlRespuesta = rtrim($baseUrl, '/') . "/bonificacion/{$token}";

            // CU-30 Paso 5: Preparar datos para compilar plantilla
            $montoFinal = $this->bonificacion->monto_original - $this->bonificacion->monto_bonificado;
            $porcentaje = $this->bonificacion->porcentaje_aprobado ?? $this->bonificacion->porcentaje_sugerido;
            
            $datosPlantilla = [ 
                'codigo_reparacion' => $reparacion->codigo_reparacion,
                'nombre_cliente' => $cliente->nombre . ' ' . $cliente->apellido,
                'equipo_marca' => $reparacion->equipo_marca,
                'equipo_modelo' => $reparacion->equipo_modelo,
                'fecha_ingreso' => $reparacion->fecha_ingreso->format('d/m/Y'),
                'dias_excedidos' => $this->bonificacion->dias_excedidos ?? 'N/A',
                'porcentaje' => $porcentaje,
                'monto_original' => number_format($this->bonificacion->monto_original, 2, ',', '.'),
                'monto_bonificado' => number_format($this->bonificacion->monto_bonificado, 2, ',', '.'),
                'monto_final' => number_format($montoFinal, 2, ',', '.'),
                'motivo_demora' => $this->bonificacion->motivoDemora?->nombre ?? 'Sin especificar',
                'url_respuesta' => $urlRespuesta,
            ];

            // CU-30 Paso 5: Compilar mensaje con la plantilla
            $mensaje = $plantilla->compilar($datosPlantilla);

            // Enviar mensaje por WhatsApp
            $this->enviarWhatsApp($cliente->telefono, $mensaje);

            Log::info('Notificación WhatsApp enviada a cliente', [
                'bonificacion_id' => $this->bonificacion->bonificacionID,
                'cliente_id' => $cliente->clienteID,
                'telefono' => $cliente->telefono,
                'reparacion_id' => $reparacion->reparacionID,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar cliente por WhatsApp', [
                'bonificacion_id' => $this->bonificacion->bonificacionID,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw para que Laravel reintente el job
        }
    }

    /**
     * Envía mensaje por WhatsApp usando Twilio
     */
    protected function enviarWhatsApp(string $telefono, string $mensaje): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $whatsappFrom = config('services.twilio.whatsapp_from');

        $client = new Client($sid, $token);

        // Formatear número de teléfono
        $telefonoFormateado = $this->formatearTelefono($telefono);

        Log::info('Enviando WhatsApp', [
            'to' => "whatsapp:{$telefonoFormateado}",
            'from' => "whatsapp:{$whatsappFrom}",
            'mensaje_length' => strlen($mensaje),
        ]);

        $response = $client->messages->create(
            "whatsapp:{$telefonoFormateado}",
            [
                'from' => "whatsapp:{$whatsappFrom}",
                'body' => $mensaje,
            ]
        );

        Log::info('WhatsApp enviado por Twilio', [
            'message_sid' => $response->sid,
            'status' => $response->status,
            'to' => $response->to,
        ]);
    }

    /**
     * Formatea número de teléfono al formato internacional
     */
    protected function formatearTelefono(string $telefono): string
    {
        // Eliminar espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);

        // Si ya tiene +, retornar tal cual
        if (str_starts_with($telefono, '+')) {
            return $telefono;
        }

        // Si empieza con 549, agregar +
        if (str_starts_with($telefono, '549')) {
            return '+' . $telefono;
        }

        // Si no tiene prefijo, asumir Argentina (+549)
        return '+549' . $telefono;
    }
}
