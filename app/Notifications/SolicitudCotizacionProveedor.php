<?php

namespace App\Notifications;

use App\Models\CotizacionProveedor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación: Solicitud de Cotización a Proveedor (CU-20)
 * 
 * Envía email con Magic Link para que el proveedor pueda
 * cotizar desde el portal público.
 * 
 * Lineamientos aplicados:
 * - Kendall: Magic Link para acceso externo seguro
 * - Laravel Notifications para envío de email
 */
class SolicitudCotizacionProveedor extends Notification implements ShouldQueue
{
    use Queueable;

    protected CotizacionProveedor $cotizacion;
    protected bool $esRecordatorio;

    public function __construct(CotizacionProveedor $cotizacion, bool $esRecordatorio = false)
    {
        $this->cotizacion = $cotizacion;
        $this->esRecordatorio = $esRecordatorio;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $solicitud = $this->cotizacion->solicitud;
        $magicLink = $this->cotizacion->generarMagicLink();
        $diasRestantes = now()->diffInDays($solicitud->fecha_vencimiento, false);
        
        // Asunto diferente para recordatorios
        $asunto = $this->esRecordatorio 
            ? "🔔 RECORDATORIO - Solicitud de Cotización {$solicitud->codigo_solicitud}"
            : "Solicitud de Cotización - {$solicitud->codigo_solicitud}";

        $mail = (new MailMessage)
            ->subject($asunto)
            ->greeting("Estimado/a {$notifiable->razon_social},");

        // Mensaje diferente para recordatorios
        if ($this->esRecordatorio) {
            $mail->line("Le recordamos que tenemos una solicitud de cotización pendiente de su respuesta.")
                 ->line("⏰ **Solo quedan {$diasRestantes} día(s) para responder.**")
                 ->line('');
        } else {
            $mail->line("Le invitamos a cotizar los siguientes productos para TecnoSoluciones:")
                 ->line('');
        }

        // Agregar lista de productos
        $productosTexto = '';
        foreach ($solicitud->detalles as $detalle) {
            $productosTexto .= "• {$detalle->producto->nombre} - Cantidad: {$detalle->cantidad_sugerida}\n";
        }
        $mail->line($productosTexto);

        $mail->line('')
            ->line("**Fecha límite para cotizar:** " . $solicitud->fecha_vencimiento->format('d/m/Y'))
            ->line('')
            ->action('Cotizar Ahora', $magicLink)
            ->line('')
            ->line('Este enlace es único y personal. Por favor no lo comparta.')
            ->line('')
            ->salutation('Gracias por su colaboración, TecnoSoluciones');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'cotizacion_id' => $this->cotizacion->id,
            'solicitud_codigo' => $this->cotizacion->solicitud->codigo_solicitud,
        ];
    }
}
