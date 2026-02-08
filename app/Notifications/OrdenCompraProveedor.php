<?php

namespace App\Notifications;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Notificación de Orden de Compra enviada al PROVEEDOR (CU-22)
 * 
 * Esta notificación se envía al email del proveedor con el PDF adjunto.
 * En desarrollo usa Mailpit (localhost:1025).
 */
class OrdenCompraProveedor extends Notification implements ShouldQueue
{
    use Queueable;

    public OrdenCompra $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
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
        $this->orden->load(['detalles.producto', 'usuario']);
        
        $mail = (new MailMessage)
            ->subject("📋 Orden de Compra {$this->orden->numero_oc} - TecnoSoluciones")
            ->greeting("Estimado/a {$notifiable->razon_social},")
            ->line("Le enviamos la **Orden de Compra N° {$this->orden->numero_oc}** para su revisión y confirmación.")
            ->line("")
            ->line("**Detalles de la orden:**");
        
        // Agregar productos
        foreach ($this->orden->detalles as $detalle) {
            $producto = $detalle->producto;
            $nombre = $producto ? $producto->nombre : "Producto #{$detalle->producto_id}";
            $subtotal = number_format($detalle->cantidad_pedida * $detalle->precio_unitario, 2, ',', '.');
            $mail->line("• {$nombre} x{$detalle->cantidad_pedida} - \${$subtotal}");
        }
        
        $totalFormateado = number_format($this->orden->total_final, 2, ',', '.');
        $mail->line("")
            ->line("**Total: \${$totalFormateado}**");
        
        // Instrucciones/observaciones
        if ($this->orden->observaciones) {
            $mail->line("")
                ->line("**Instrucciones especiales:**")
                ->line($this->orden->observaciones);
        }
        
        $mail->line("")
            ->line("Por favor, confirme la recepción de esta orden respondiendo a este correo o comunicándose con nosotros.")
            ->line("")
            ->salutation("Atentamente,\nTecnoSoluciones - Departamento de Compras");
        
        // Adjuntar PDF si existe
        if ($this->orden->archivo_pdf && Storage::disk('public')->exists($this->orden->archivo_pdf)) {
            $pdfPath = Storage::disk('public')->path($this->orden->archivo_pdf);
            
            if (file_exists($pdfPath)) {
                $mail->attach(
                    $pdfPath,
                    [
                        'as' => "{$this->orden->numero_oc}.pdf",
                        'mime' => 'application/pdf',
                    ]
                );
            } else {
                \Log::warning("PDF no encontrado en disco: {$pdfPath}");
            }
        } else {
            \Log::warning("PDF no existe para OC {$this->orden->numero_oc}: " . ($this->orden->archivo_pdf ?? 'null'));
        }

        return $mail;
    }
}
