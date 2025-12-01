<?php

namespace App\Jobs;

use App\Models\AlertaReparacion;
use App\Models\Configuracion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Job para notificar a técnicos sobre alertas de SLA excedido (CU-14)
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
     */
    public function handle(): void
    {
        try {
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

            // Obtener template desde configuración
            $template = Configuracion::get('whatsapp_template_alerta_tecnico', 
                "⚠️ *ALERTA SLA - Reparación #{codigo_reparacion}*\n\n" .
                "Técnico: {nombre_tecnico}\n" .
                "Cliente: {nombre_cliente}\n" .
                "Equipo: {equipo_marca} {equipo_modelo}\n\n" .
                "📊 Estado del SLA:\n" .
                "• SLA vigente: {sla_vigente} días\n" .
                "• Días efectivos: {dias_efectivos} días\n" .
                "• Días excedidos: {dias_excedidos} días\n" .
                "• Tipo: {tipo_alerta}\n\n" .
                "⏰ Fecha de ingreso: {fecha_ingreso}\n\n" .
                "Por favor, ingrese al sistema para registrar el motivo de la demora."
            );

            // Reemplazar variables en el template
            $mensaje = str_replace(
                [
                    '{codigo_reparacion}',
                    '{nombre_tecnico}',
                    '{nombre_cliente}',
                    '{equipo_marca}',
                    '{equipo_modelo}',
                    '{sla_vigente}',
                    '{dias_efectivos}',
                    '{dias_excedidos}',
                    '{tipo_alerta}',
                    '{fecha_ingreso}',
                ],
                [
                    $reparacion->codigo_reparacion,
                    $tecnico->name,
                    $cliente->nombre . ' ' . $cliente->apellido,
                    $reparacion->equipo_marca,
                    $reparacion->equipo_modelo,
                    $this->alerta->sla_vigente,
                    $this->alerta->dias_efectivos,
                    $this->alerta->dias_excedidos,
                    $this->alerta->tipo_alerta === 'incumplimiento' ? 'INCUMPLIMIENTO' : 'EXCESO',
                    $reparacion->fecha_ingreso->format('d/m/Y'),
                ],
                $template
            );

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
