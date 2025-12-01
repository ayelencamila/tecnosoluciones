<?php

namespace App\Notifications;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de incumplimiento de Cuenta Corriente para administradores
 * CU-09 Paso 4: Notificación interna al administrador/vendedor
 */
class IncumplimientoCCNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Cliente $cliente;
    public string $motivo;
    public string $tipoAccion; // 'bloqueo', 'revision', 'alerta'
    public float $saldoTotal;
    public float $saldoVencido;
    public float $limiteCredito;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Cliente $cliente, 
        string $motivo, 
        string $tipoAccion,
        float $saldoTotal = 0,
        float $saldoVencido = 0,
        float $limiteCredito = 0
    ) {
        $this->cliente = $cliente;
        $this->motivo = $motivo;
        $this->tipoAccion = $tipoAccion;
        $this->saldoTotal = $saldoTotal;
        $this->saldoVencido = $saldoVencido;
        $this->limiteCredito = $limiteCredito;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Almacenar en BD para mostrar en el panel
    }

    /**
     * Get the array representation of the notification.
     * Este método se almacena en la columna "data" de la tabla notifications
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $iconos = [
            'bloqueo' => '🔒',
            'revision' => '⚠️',
            'alerta' => '🔔',
        ];

        $titulos = [
            'bloqueo' => 'Cuenta Corriente Bloqueada',
            'revision' => 'Cuenta en Revisión',
            'alerta' => 'Alerta de Cuenta Corriente',
        ];

        return [
            'tipo' => 'incumplimiento_cc',
            'accion' => $this->tipoAccion,
            'icono' => $iconos[$this->tipoAccion] ?? '⚠️',
            'titulo' => $titulos[$this->tipoAccion] ?? 'Alerta CC',
            'mensaje' => $this->motivo,
            'cliente_id' => $this->cliente->clienteID,
            'cliente_nombre' => $this->cliente->nombre_completo,
            'cliente_dni' => $this->cliente->DNI,
            'saldo_total' => $this->saldoTotal,
            'saldo_vencido' => $this->saldoVencido,
            'limite_credito' => $this->limiteCredito,
            'url' => route('clientes.show', $this->cliente->clienteID),
            'fecha' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Opcional: Si quieres también enviar email
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ Incumplimiento CC - {$this->cliente->nombre_completo}")
            ->line("Se detectó un incumplimiento en la cuenta corriente del cliente:")
            ->line("**Cliente:** {$this->cliente->nombre_completo} (DNI: {$this->cliente->DNI})")
            ->line("**Motivo:** {$this->motivo}")
            ->line("**Saldo Total:** \${$this->saldoTotal}")
            ->line("**Saldo Vencido:** \${$this->saldoVencido}")
            ->line("**Límite de Crédito:** \${$this->limiteCredito}")
            ->action('Ver Cliente', route('clientes.show', $this->cliente->clienteID))
            ->line('Por favor, tome las medidas correspondientes.');
    }
}