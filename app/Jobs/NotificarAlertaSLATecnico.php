<?php

namespace App\Jobs;

use App\Models\AlertaReparacion;
use App\Models\PlantillaWhatsapp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Job para notificar a técnicos sobre alertas de SLA excedido (CU-14)
 * Implementa CU-30: Uso de plantillas configurables con horarios
 */
class NotificarAlertaSLATecnico implements ShouldQueue
{
    use Queueable;

    protected AlertaReparacion $alerta;

    /**
     * Create a new job instance.
     */
    public function __construct(AlertaReparacion $alerta)
    {
        $this->alerta = $alerta;
    }

    /**
     * Execute the job.
     * CU-30: Usa plantillas configurables con validación de horarios
     */
    public function handle(): void
    {
        try {
            // CU-30 Paso 3: Obtener plantilla activa para el tipo de evento
            $plantilla = PlantillaWhatsapp::obtenerPorTipo('alerta_sla_tecnico');
            
            if (!$plantilla) {
                Log::error('Plantilla alerta_sla_tecnico no encontrada o inactiva', [
                    'alerta_id' => $this->alerta->alertaReparacionID,
                ]);
                return;
            }

            // CU-30 Paso 6: Verificar horario de envío
            if (!$plantilla->estaEnHorarioPermitido()) {
                $segundos = $plantilla->segundosHastaProximoEnvio();
                Log::info("Envío de alerta SLA pospuesto por horario. Esperando {$segundos}s", [
                    'alerta_id' => $this->alerta->alertaReparacionID,
                ]);
                $this->release($segundos);
                return;
            }

            // Cargar relaciones necesarias
            $this->alerta->load(['reparacion.cliente', 'tecnico']);

            $tecnico = $this->alerta->tecnico;
            $reparacion = $this->alerta->reparacion;
            $cliente = $reparacion->cliente;

            // Verificar que el técnico tenga teléfono
            if (!$tecnico || !$tecnico->telefono) {
                Log::warning('Técnico sin teléfono registrado', [
                    'alerta_id' => $this->alerta->alertaReparacionID,
                    'tecnico_id' => $this->alerta->tecnicoID,
                ]);
                return;
            }

            // CU-30 Paso 5: Preparar datos para compilar plantilla
            $datosPlantilla = [
                'codigo_reparacion' => $reparacion->codigo_reparacion,
                'nombre_tecnico' => $tecnico->name,
                'nombre_cliente' => $cliente->nombre . ' ' . $cliente->apellido,
                'equipo_marca' => $reparacion->equipo_marca,
                'equipo_modelo' => $reparacion->equipo_modelo,
                'sla_vigente' => $this->alerta->sla_vigente,
                'dias_efectivos' => $this->alerta->dias_efectivos,
                'dias_excedidos' => $this->alerta->dias_excedidos,
                'tipo_alerta' => $this->alerta->tipo_alerta === 'incumplimiento' ? 'INCUMPLIMIENTO' : 'EXCESO',
                'fecha_ingreso' => $reparacion->fecha_ingreso->format('d/m/Y'),
            ];

            // CU-30 Paso 5: Compilar mensaje con la plantilla
            $mensaje = $plantilla->compilar($datosPlantilla);

            // Enviar mensaje por WhatsApp
            $this->enviarWhatsApp($tecnico->telefono, $mensaje);

            Log::info('Notificación WhatsApp enviada a técnico', [
                'alerta_id' => $this->alerta->alertaReparacionID,
                'tecnico_id' => $tecnico->id,
                'telefono' => $tecnico->telefono,
                'reparacion_id' => $reparacion->reparacionID,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al notificar técnico por WhatsApp', [
                'alerta_id' => $this->alerta->alertaReparacionID,
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
