<?php

namespace App\Jobs;

use App\Models\BonificacionReparacion;
use App\Models\Configuracion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Job para notificar a clientes sobre bonificaciones por demora (CU-14/15)
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
     */
    public function handle(): void
    {
        try {
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

            // Obtener template desde configuración
            $template = Configuracion::get('whatsapp_template_bonificacion', 
                "🎁 *BONIFICACIÓN POR DEMORA - Reparación #{codigo_reparacion}*\n\n" .
                "Estimado/a {nombre_cliente},\n\n" .
                "Lamentamos informarle que su reparación ha excedido el tiempo estimado.\n\n" .
                "📱 Equipo: {equipo_marca} {equipo_modelo}\n" .
                "⏰ Ingresado: {fecha_ingreso}\n" .
                "📊 Días de demora: {dias_excedidos}\n\n" .
                "Como compensación, aplicaremos una *bonificación del {porcentaje}%* sobre el costo final.\n\n" .
                "💰 Monto original: \${monto_original}\n" .
                "🎉 Bonificación: \${monto_bonificado}\n" .
                "💳 Total a pagar: \${monto_final}\n\n" .
                "Motivo: {motivo_demora}\n\n" .
                "Gracias por su comprensión."
            );

            $montoFinal = $this->bonificacion->monto_original - $this->bonificacion->monto_bonificado;

            // Reemplazar variables en el template
            $mensaje = str_replace(
                [
                    '{codigo_reparacion}',
                    '{nombre_cliente}',
                    '{equipo_marca}',
                    '{equipo_modelo}',
                    '{fecha_ingreso}',
                    '{dias_excedidos}',
                    '{porcentaje}',
                    '{monto_original}',
                    '{monto_bonificado}',
                    '{monto_final}',
                    '{motivo_demora}',
                ],
                [
                    $reparacion->codigo_reparacion,
                    $cliente->nombre . ' ' . $cliente->apellido,
                    $reparacion->equipo_marca,
                    $reparacion->equipo_modelo,
                    $reparacion->fecha_ingreso->format('d/m/Y'),
                    $this->bonificacion->dias_excedidos ?? 'N/A',
                    $this->bonificacion->porcentaje_aprobado ?? $this->bonificacion->porcentaje_sugerido,
                    number_format($this->bonificacion->monto_original, 2, ',', '.'),
                    number_format($this->bonificacion->monto_bonificado, 2, ',', '.'),
                    number_format($montoFinal, 2, ',', '.'),
                    $this->bonificacion->motivoDemora?->nombre ?? 'Sin especificar',
                ],
                $template
            );

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
