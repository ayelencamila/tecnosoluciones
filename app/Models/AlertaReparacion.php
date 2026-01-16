<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaReparacion extends Model
{
    use HasFactory;

    protected $table = 'alertas_reparacion';
    protected $primaryKey = 'alertaReparacionID';

    protected $fillable = [
        'reparacionID',
        'tecnicoID',
        'tipo_alerta_id',
        'dias_excedidos',
        'dias_efectivos',
        'sla_vigente',
        'respuesta_tecnico',
        'leida',
        'fecha_lectura',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'fecha_lectura' => 'datetime',
        'respuesta_tecnico' => 'array',
        'dias_excedidos' => 'integer',
        'dias_efectivos' => 'integer',
        'sla_vigente' => 'integer',
    ];

    /**
     * Get the route key name for Laravel's implicit model binding.
     */
    public function getRouteKeyName()
    {
        return 'alertaReparacionID';
    }

    /**
     * Reparación asociada a la alerta
     */
    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class, 'reparacionID', 'reparacionID');
    }

    /**
     * Técnico responsable
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnicoID', 'id');
    }

    /**
     * Tipo de alerta (tabla paramétrica)
     */
    public function tipoAlerta(): BelongsTo
    {
        return $this->belongsTo(TipoAlertaReparacion::class, 'tipo_alerta_id', 'tipo_id');
    }

    /**
     * Marcar alerta como leída
     */
    public function marcarComoLeida(): void
    {
        $this->update([
            'leida' => true,
            'fecha_lectura' => now(),
        ]);
    }

    /**
     * Registrar respuesta del técnico
     */
    public function registrarRespuesta(int $motivoDemoraID, bool $factible, ?string $observaciones): void
    {
        $this->update([
            'respuesta_tecnico' => [
                'motivo_id' => $motivoDemoraID,
                'factible' => $factible,
                'observaciones' => $observaciones ?? '',
                'fecha_respuesta' => now()->toDateTimeString(),
            ],
            'leida' => true,
            'fecha_lectura' => now(),
        ]);
    }

    /**
     * Scope para alertas no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Scope para alertas de un técnico específico
     */
    public function scopeDeTecnico($query, int $tecnicoID)
    {
        return $query->where('tecnicoID', $tecnicoID);
    }
}
