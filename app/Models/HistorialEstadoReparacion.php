<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para historial de cambios de estado en reparaciones
 * 
 * Permite calcular días efectivos correctamente, pausando el conteo
 * cuando la reparación está en estados específicos (ej: esperando repuesto)
 */
class HistorialEstadoReparacion extends Model
{
    use HasFactory;

    protected $table = 'historial_estados_reparacion';
    protected $primaryKey = 'historial_id';

    protected $fillable = [
        'reparacion_id',
        'estado_anterior_id',
        'estado_nuevo_id',
        'fecha_cambio',
        'usuario_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];

    /**
     * Reparación asociada
     */
    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class, 'reparacion_id', 'reparacionID');
    }

    /**
     * Estado anterior
     */
    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(EstadoReparacion::class, 'estado_anterior_id', 'estadoReparacionID');
    }

    /**
     * Estado nuevo
     */
    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(EstadoReparacion::class, 'estado_nuevo_id', 'estadoReparacionID');
    }

    /**
     * Usuario que realizó el cambio
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    /**
     * Verifica si el estado nuevo pausa el SLA
     */
    public function pausaSLA(): bool
    {
        $estadosPausa = Configuracion::get('estados_pausa_sla', '');
        $estadosArray = array_filter(array_map('trim', explode(',', $estadosPausa)));

        $nombreEstado = $this->estadoNuevo?->nombreEstado;
        
        return $nombreEstado && in_array($nombreEstado, $estadosArray);
    }

    /**
     * Scope: Historial de una reparación específica
     */
    public function scopeDeReparacion($query, int $reparacionId)
    {
        return $query->where('reparacion_id', $reparacionId);
    }

    /**
     * Scope: Cambios en un rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_cambio', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope: Solo cambios que pausan SLA
     */
    public function scopeQuePausanSLA($query)
    {
        $estadosPausa = Configuracion::get('estados_pausa_sla', '');
        $estadosArray = array_filter(array_map('trim', explode(',', $estadosPausa)));

        if (empty($estadosArray)) {
            return $query->whereRaw('1 = 0'); // No hay estados que pauset
        }

        return $query->whereHas('estadoNuevo', function ($q) use ($estadosArray) {
            $q->whereIn('nombreEstado', $estadosArray);
        });
    }
}
