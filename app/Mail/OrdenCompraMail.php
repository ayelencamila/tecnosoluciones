<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Mailable para enviar Orden de Compra por email al proveedor
 * 
 * Incluye:
 * - PDF de la OC adjunto
 * - Resumen de productos en el cuerpo
 * - Datos de contacto
 */
class OrdenCompraMail extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenCompra $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden->load(['proveedor', 'detalles.producto', 'estado', 'usuario']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Orden de Compra {$this->orden->numero_oc} - TecnoSoluciones",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orden-compra',
            with: [
                'orden' => $this->orden,
                'proveedor' => $this->orden->proveedor,
                'detalles' => $this->orden->detalles,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        // Adjuntar PDF si existe
        if ($this->orden->archivo_pdf && Storage::disk('public')->exists($this->orden->archivo_pdf)) {
            $attachments[] = Attachment::fromStorage($this->orden->archivo_pdf)
                ->as("{$this->orden->numero_oc}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
