<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Modelo SOLICITUDES_COTIZACION (CU-20)
 * 
 * Representa solicitudes de cotización enviadas a múltiples proveedores.
 * Cada solicitud puede tener múltiples detalles (productos) y múltiples
 * cotizaciones de proveedores (Magic Links).
 * 
 * Lineamientos aplicados:
 * - Elmasri 3FN: estado_id referencia tabla paramétrica
 * - Kendall: Control de estados del flujo de compras
 * - Larman: Patrón Experto (conoce su estado y puede validar)
 * 
 * @property int $id
 * @property string $codigo_solicitud Código único SOL-YYYYMMDD-XXX
 * @property Carbon $fecha_emision
 * @property Carbon $fecha_vencimiento
 * @property int $estado_id FK a estados_solicitud
 * @property int|null $user_id Usuario que creó (null si automático)
 * @property string|null $observaciones
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read EstadoSolicitud $estado
 * @property-read User|null $usuario
 * @property-read \Illuminate\Database\Eloquent\Collection<DetalleSolicitudCotizacion> $detalles
 * @property-read \Illuminate\Database\Eloquent\Collection<CotizacionProveedor> $cotizacionesProveedores
 */
class SolicitudCotizacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_cotizacion';

    protected $fillable = [
        'codigo_solicitud',
        'fecha_emision',
        'fecha_vencimiento',
        'estado_id',
        'user_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_vencimiento' => 'datetime',
    ];

    // --- RELACIONES (Elmasri) ---

    /**
     * Estado actual de la solicitud (3FN)
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoSolicitud::class, 'estado_id');
    }

    /**
     * Usuario que creó la solicitud
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Detalles de productos solicitados (1FN - relación normalizada)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleSolicitudCotizacion::class, 'solicitud_id');
    }

    /**
     * Cotizaciones enviadas a proveedores (Magic Links)
     */
    public function cotizacionesProveedores(): HasMany
    {
        return $this->hasMany(CotizacionProveedor::class, 'solicitud_id');
    }

    // --- SCOPES ---

    /**
     * Scope: Solicitudes abiertas
     */
    public function scopeAbiertas($query)
    {
        return $query->whereHas('estado', fn($q) => $q->where('nombre', 'Abierta'));
    }

    /**
     * Scope: Solicitudes enviadas esperando respuestas
     */
    public function scopeEnviadas($query)
    {
        return $query->whereHas('estado', fn($q) => $q->where('nombre', 'Enviada'));
    }

    /**
     * Scope: Solicitudes vencidas
     */
    public function scopeVencidas($query)
    {
        return $query->whereHas('estado', fn($q) => $q->where('nombre', 'Vencida'));
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Genera código único para la solicitud
     * Formato: SOL-YYYYMMDD-XXX
     */
    public static function generarCodigoSolicitud(): string
    {
        $fecha = now()->format('Ymd');
        $prefijo = "SOL-{$fecha}-";
        
        $ultimaSolicitud = self::where('codigo_solicitud', 'like', "{$prefijo}%")
            ->orderBy('codigo_solicitud', 'desc')
            ->first();
        
        if ($ultimaSolicitud) {
            $ultimoNumero = (int) substr($ultimaSolicitud->codigo_solicitud, -3);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        return $prefijo . str_pad($nuevoNumero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica si la solicitud está en estado específico
     */
    public function estaEnEstado(string $nombreEstado): bool
    {
        return $this->estado->nombre === $nombreEstado;
    }

    /**
     * Verifica si está abierta
     */
    public function estaAbierta(): bool
    {
        return $this->estaEnEstado('Abierta');
    }

    /**
     * Verifica si está enviada
     */
    public function estaEnviada(): bool
    {
        return $this->estaEnEstado('Enviada');
    }

    /**
     * Verifica si la solicitud ha vencido
     */
    public function haVencido(): bool
    {
        return $this->fecha_vencimiento->isPast();
    }

    /**
     * Verifica si se puede enviar a proveedores
     */
    public function puedeEnviarse(): bool
    {
        return $this->estaAbierta() 
            && $this->detalles()->exists() 
            && !$this->haVencido();
    }

    /**
     * Cantidad de respuestas recibidas
     */
    public function cantidadRespuestas(): int
    {
        return $this->cotizacionesProveedores()
            ->whereNotNull('fecha_respuesta')
            ->count();
    }

    /**
     * Cantidad de proveedores pendientes de responder
     */
    public function cantidadPendientes(): int
    {
        return $this->cotizacionesProveedores()
            ->whereNull('fecha_respuesta')
            ->where('estado_envio', 'Enviado')
            ->count();
    }
}
