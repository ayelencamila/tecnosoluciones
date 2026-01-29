<?php

namespace App\Jobs;

use App\Models\Reparacion;
use App\Models\PlantillaWhatsapp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Job para notificar al CLIENTE sobre demora en su reparación (CU-14 Paso 8)
 * Usa plantillas configurables y respeta horarios de envío
 */
class NotificarDemoraCliente implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected Reparacion $reparacion,
        protected int $diasExcedidos
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Obtener plantilla activa para demora de cliente
            $plantilla = PlantillaWhatsapp::obtenerPorTipo('demora_reparacion_cliente');
            
            if (!$plantilla) {
                Log::warning('Plantilla demora_reparacion_cliente no encontrada o inactiva', [
                    'reparacion_id' => $this->reparacion->reparacionID,
                ]);
                return;
            }

            // Verificar horario de envío
            if (!$plantilla->estaEnHorarioPermitido()) {
                $segundos = $plantilla->segundosHastaProximoEnvio();
                Log::info("Envío de notificación de demora pospuesto por horario. Esperando {$segundos}s", [
                    'reparacion_id' => $this->reparacion->reparacionID,
                ]);
                $this->release($segundos);
                return;
            }

            // Cargar relaciones necesarias
            $this->reparacion->load(['cliente', 'modelo.marca', 'tecnico']);
            
            $cliente = $this->reparacion->cliente;

            // Verificar que el cliente tenga teléfono
            if (!$cliente || !$cliente->whatsapp) {
                Log::warning('Cliente sin WhatsApp registrado para notificar demora', [
                    'reparacion_id' => $this->reparacion->reparacionID,
                    'cliente_id' => $cliente?->clienteID,
                ]);
                return;
            }

            // Preparar datos para compilar plantilla
            $datosPlantilla = [
                'nombre_cliente' => $cliente->nombre,
                'apellido_cliente' => $cliente->apellido,
                'codigo_reparacion' => $this->reparacion->codigo_reparacion,
                'equipo_marca' => $this->reparacion->modelo?->marca?->nombre ?? 'N/A',
                'equipo_modelo' => $this->reparacion->modelo?->nombre ?? 'N/A',
                'dias_excedidos' => $this->diasExcedidos,
                'fecha_ingreso' => $this->reparacion->fecha_ingreso->format('d/m/Y'),
                'fecha_promesa' => $this->reparacion->fecha_promesa?->format('d/m/Y') ?? 'No especificada',
                'tecnico_nombre' => $this->reparacion->tecnico?->name ?? 'Nuestro equipo técnico',
                'nombre_empresa' => config('app.name', 'TecnoSoluciones'),
            ];

            // Compilar mensaje con la plantilla
            $mensaje = $plantilla->compilar($datosPlantilla);

            // Enviar mensaje por WhatsApp
            $this->enviarWhatsApp($cliente->whatsapp, $mensaje);

            Log::info('Notificación WhatsApp de demora enviada a cliente', [
                'reparacion_id' => $this->reparacion->reparacionID,
                'cliente_id' => $cliente->clienteID,
                'telefono' => $cliente->whatsapp,
                'dias_excedidos' => $this->diasExcedidos,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar cliente por WhatsApp sobre demora', [
                'reparacion_id' => $this->reparacion->reparacionID,
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
