<?php

namespace App\Jobs;

use App\Models\BonificacionReparacion;
use App\Models\PlantillaWhatsapp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Job para notificar al CLIENTE que su bonificación fue rechazada (CU-15)
 * Le indica que pase a retirar el equipo y se le devolverá el dinero
 */
class NotificarRechazoCliente implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected BonificacionReparacion $bonificacion,
        protected string $motivoRechazo
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Obtener plantilla activa para rechazo
            $plantilla = PlantillaWhatsapp::obtenerPorTipo('rechazo_bonificacion_cliente');
            
            if (!$plantilla) {
                Log::warning('Plantilla rechazo_bonificacion_cliente no encontrada o inactiva', [
                    'bonificacion_id' => $this->bonificacion->bonificacionID,
                ]);
                return;
            }

            // Verificar horario de envío
            if (!$plantilla->estaEnHorarioPermitido()) {
                $segundos = $plantilla->segundosHastaProximoEnvio();
                Log::info("Envío de notificación de rechazo pospuesto por horario. Esperando {$segundos}s", [
                    'bonificacion_id' => $this->bonificacion->bonificacionID,
                ]);
                $this->release($segundos);
                return;
            }

            // Cargar relaciones necesarias
            $this->bonificacion->load(['reparacion.cliente', 'reparacion.modelo.marca']);
            
            $reparacion = $this->bonificacion->reparacion;
            $cliente = $reparacion->cliente;

            // Verificar que el cliente tenga teléfono
            if (!$cliente || !$cliente->whatsapp) {
                Log::warning('Cliente sin WhatsApp registrado para notificar rechazo', [
                    'bonificacion_id' => $this->bonificacion->bonificacionID,
                    'cliente_id' => $cliente?->clienteID,
                ]);
                return;
            }

            // Preparar datos para compilar plantilla
            $datosPlantilla = [
                'nombre_cliente' => $cliente->nombre,
                'apellido_cliente' => $cliente->apellido,
                'codigo_reparacion' => $reparacion->codigo_reparacion,
                'equipo_marca' => $reparacion->modelo?->marca?->nombre ?? 'N/A',
                'equipo_modelo' => $reparacion->modelo?->nombre ?? 'N/A',
                'motivo_rechazo' => $this->motivoRechazo,
                'nombre_empresa' => config('app.name', 'TecnoSoluciones'),
            ];

            // Compilar mensaje con la plantilla
            $mensaje = $plantilla->compilar($datosPlantilla);

            // Enviar mensaje por WhatsApp
            $this->enviarWhatsApp($cliente->whatsapp, $mensaje);

            Log::info('Notificación WhatsApp de rechazo enviada a cliente', [
                'bonificacion_id' => $this->bonificacion->bonificacionID,
                'reparacion_id' => $reparacion->reparacionID,
                'cliente_id' => $cliente->clienteID,
                'telefono' => $cliente->whatsapp,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar cliente por WhatsApp sobre rechazo', [
                'bonificacion_id' => $this->bonificacion->bonificacionID,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
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

        $client->messages->create(
            "whatsapp:{$telefonoFormateado}",
            [
                'from' => "whatsapp:{$whatsappFrom}",
                'body' => $mensaje,
            ]
        );
    }

    /**
     * Formatea número de teléfono al formato internacional
     */
    protected function formatearTelefono(string $telefono): string
    {
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);

        if (str_starts_with($telefono, '+')) {
            return $telefono;
        }

        if (str_starts_with($telefono, '549')) {
            return '+' . $telefono;
        }

        return '+549' . $telefono;
    }
}
