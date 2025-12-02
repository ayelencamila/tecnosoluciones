<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonificacionReparacion extends Model
{
    use HasFactory;

    protected $table = 'bonificaciones_reparacion';
    protected $primaryKey = 'bonificacionID';

    protected $fillable = [
        'reparacionID',
        'porcentaje_sugerido',
        'porcentaje_aprobado',
        'monto_original',
        'monto_bonificado',
        'dias_excedidos',
        'justificacion_tecnico',
        'motivoDemoraID',
        'estado',
        'aprobada_por',
        'fecha_aprobacion',
        'observaciones_aprobacion',
    ];

    protected $casts = [
        'porcentaje_sugerido' => 'decimal:2',
        'porcentaje_aprobado' => 'decimal:2',
        'monto_original' => 'decimal:2',
        'monto_bonificado' => 'decimal:2',
        'fecha_aprobacion' => 'datetime',
    ];

    /**
     * Get the route key name for Laravel's implicit model binding.
     */
    public function getRouteKeyName()
    {
        return 'bonificacionID';
    }

    /**
     * Reparación asociada
     */
    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class, 'reparacionID', 'reparacionID');
    }

    /**
     * Usuario que aprobó la bonificación
     */
    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobada_por', 'id');
    }

    /**
     * Alias para aprobador (para mantener compatibilidad)
     */
    public function aprobadaPor(): BelongsTo
    {
        return $this->aprobador();
    }

    /**
     * Motivo de demora asociado
     */
    public function motivoDemora(): BelongsTo
    {
        return $this->belongsTo(MotivoDemoraReparacion::class, 'motivoDemoraID', 'motivoDemoraID');
    }

    /**
     * Aprobar bonificación
     */
    public function aprobar(int $usuarioID, ?string $observaciones = null): void
    {
        $this->update([
            'estado' => 'aprobada',
            'porcentaje_aprobado' => $this->porcentaje_sugerido,
            'monto_bonificado' => $this->monto_original * (1 - $this->porcentaje_sugerido / 100),
            'aprobada_por' => $usuarioID,
            'fecha_aprobacion' => now(),
            'observaciones_aprobacion' => $observaciones,
        ]);
    }

    /**
     * Rechazar bonificación
     */
    public function rechazar(int $usuarioID, string $motivo): void
    {
        $this->update([
            'estado' => 'rechazada',
            'aprobada_por' => $usuarioID,
            'fecha_aprobacion' => now(),
            'observaciones_aprobacion' => $motivo,
        ]);
    }

    /**
     * Aprobar con porcentaje personalizado
     */
    public function aprobarConPorcentaje(int $usuarioID, float $porcentaje, ?string $observaciones = null): void
    {
        $montoConDescuento = $this->monto_original * (1 - $porcentaje / 100);
        
        $this->update([
            'estado' => 'aprobada',
            'porcentaje_aprobado' => $porcentaje,
            'monto_bonificado' => $montoConDescuento,
            'aprobada_por' => $usuarioID,
            'fecha_aprobacion' => now(),
            'observaciones_aprobacion' => $observaciones,
        ]);
    }

    /**
     * Scope para bonificaciones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para bonificaciones aprobadas
     */
    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobada');
    }
}
