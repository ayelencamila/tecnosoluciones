<?php

namespace App\Mail;

use App\Models\CotizacionProveedor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para Solicitud de Cotización
 * 
 * Email profesional con Magic Link para que el proveedor
 * pueda cotizar desde el portal público.
 */
class SolicitudCotizacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public CotizacionProveedor $cotizacion;
    public string $url;
    public bool $esRecordatorio;

    /**
     * Create a new message instance.
     */
    public function __construct(CotizacionProveedor $cotizacion, string $url, bool $esRecordatorio = false)
    {
        $this->cotizacion = $cotizacion;
        $this->url = $url;
        $this->esRecordatorio = $esRecordatorio;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $asunto = $this->esRecordatorio 
            ? "🔔 Recordatorio: Solicitud de Cotización #{$this->cotizacion->solicitud->codigo_solicitud}"
            : "📋 Solicitud de Cotización #{$this->cotizacion->solicitud->codigo_solicitud}";

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $asunto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-cotizacion',
            with: [
                'solicitud' => $this->cotizacion->solicitud,
                'proveedor' => $this->cotizacion->proveedor,
                'url' => $this->url,
                'esRecordatorio' => $this->esRecordatorio,
                'diasParaVencer' => now()->diffInDays($this->cotizacion->fecha_vencimiento, false),
            ]
        );
    }
}
