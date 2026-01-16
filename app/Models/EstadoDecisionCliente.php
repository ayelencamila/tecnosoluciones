<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoDecisionCliente extends Model
{
    protected $table = 'estados_decision_cliente';
    protected $primaryKey = 'estado_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'contexto',
    ];

    public $timestamps = true;

    /**
     * Bonificaciones con este estado de decisión
     */
    public function bonificaciones(): HasMany
    {
        return $this->hasMany(BonificacionReparacion::class, 'estado_decision_id', 'estado_id');
    }

    /**
     * Scope para filtrar por contexto
     */
    public function scopeContexto($query, string $contexto)
    {
        return $query->where('contexto', $contexto);
    }

    /**
     * Obtener estado por nombre y contexto
     */
    public static function obtenerPorNombre(string $nombre, string $contexto = 'bonificacion'): ?self
    {
        return static::where('nombre', $nombre)
            ->where('contexto', $contexto)
            ->first();
    }
}
