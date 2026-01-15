<?php

namespace App\Notifications;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Notificación de Orden de Compra generada (CU-22)
 * 
 * Envía email al administrador cuando se genera una OC.
 * En desarrollo usa Mailpit (localhost:1025).
 * Incluye el PDF como adjunto.
 * 
 * También puede usarse como alerta interna si hay problemas de envío.
 */
class OrdenCompraGenerada extends Notification implements ShouldQueue
{
    use Queueable;

    public OrdenCompra $orden;
    public bool $esAlerta;
    public ?string $motivoAlerta;

    /**
     * Create a new notification instance.
     */
    public function __construct(OrdenCompra $orden, bool $esAlerta = false, ?string $motivoAlerta = null)
    {
        $this->orden = $orden;
        $this->esAlerta = $esAlerta;
        $this->motivoAlerta = $motivoAlerta;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->orden->load(['proveedor', 'detalles.producto']);
        
        $mail = (new MailMessage);
        
        if ($this->esAlerta) {
            // Alerta de problema
            $mail->subject("⚠️ ALERTA: OC {$this->orden->numero_oc} requiere atención")
                ->error()
                ->greeting("⚠️ Alerta de Orden de Compra")
                ->line("La Orden de Compra **{$this->orden->numero_oc}** requiere atención manual.")
                ->line("**Motivo:** {$this->motivoAlerta}")
                ->line("**Proveedor:** {$this->orden->proveedor->razon_social}")
                ->line("**Total:** \${$this->orden->total_final}")
                ->action('Ver Orden de Compra', route('ordenes.show', $this->orden->id))
                ->line('Por favor revise y tome las acciones necesarias.');
        } else {
            // Notificación normal de OC generada
            $mail->subject("✅ Orden de Compra {$this->orden->numero_oc} generada")
                ->greeting("¡Orden de Compra Generada!")
                ->line("Se ha generado exitosamente la Orden de Compra **{$this->orden->numero_oc}**.")
                ->line("**Proveedor:** {$this->orden->proveedor->razon_social}")
                ->line("**Total:** \${$this->orden->total_final}")
                ->line("**Productos:**");
            
            // Agregar líneas de productos
            foreach ($this->orden->detalles as $detalle) {
                $producto = $detalle->producto;
                $nombre = $producto ? $producto->nombre : "Producto #{$detalle->producto_id}";
                $mail->line("• {$nombre} x{$detalle->cantidad_pedida} - \${$detalle->precio_unitario}");
            }
            
            $mail->line("")
                ->line("Se ha enviado un WhatsApp al proveedor con la orden.")
                ->action('Ver Orden de Compra', route('ordenes.show', $this->orden->id))
                ->line('Gracias por usar TecnoSoluciones.');
        }
        
        // Adjuntar PDF si existe
        if ($this->orden->archivo_pdf && Storage::disk('public')->exists($this->orden->archivo_pdf)) {
            $mail->attach(
                Storage::disk('public')->path($this->orden->archivo_pdf),
                [
                    'as' => "{$this->orden->numero_oc}.pdf",
                    'mime' => 'application/pdf',
                ]
            );
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     * Se almacena en la columna "data" de la tabla notifications
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => $this->esAlerta ? 'alerta_oc' : 'oc_generada',
            'icono' => $this->esAlerta ? '⚠️' : '📋',
            'titulo' => $this->esAlerta 
                ? "Alerta OC {$this->orden->numero_oc}" 
                : "OC {$this->orden->numero_oc} generada",
            'mensaje' => $this->esAlerta 
                ? $this->motivoAlerta 
                : "Orden enviada a {$this->orden->proveedor->razon_social}",
            'orden_id' => $this->orden->id,
            'numero_oc' => $this->orden->numero_oc,
            'proveedor' => $this->orden->proveedor->razon_social,
            'total' => $this->orden->total_final,
            'url' => route('ordenes.show', $this->orden->id),
        ];
    }
}
