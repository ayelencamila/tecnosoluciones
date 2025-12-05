<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoDemoraReparacion extends Model
{
    use HasFactory;

    protected $table = 'motivos_demora_reparacion';
    protected $primaryKey = 'motivoDemoraID';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'requiere_bonificacion',
        'pausa_sla',
        'activo',
        'orden',
    ];

    protected $casts = [
        'requiere_bonificacion' => 'boolean',
        'pausa_sla' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Get the route key name for Laravel's implicit model binding.
     */
    public function getRouteKeyName()
    {
        return 'motivoDemoraID';
    }

    /**
     * Scope para motivos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    /**
     * Scope para motivos que califican para bonificación
     */
    public function scopeConBonificacion($query)
    {
        return $query->where('requiere_bonificacion', true);
    }

    /**
     * Scope para motivos que pausan SLA
     */
    public function scopeQuePausanSLA($query)
    {
        return $query->where('pausa_sla', true);
    }
}
