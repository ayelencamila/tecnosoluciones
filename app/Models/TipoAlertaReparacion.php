<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo TIPOS_ALERTA_REPARACION (CU-14)
 * 
 * Tabla paramétrica para tipos de alertas:
 * - sla_excedido: SLA de reparación excedido
 * - sin_respuesta: Técnico no ha respondido
 * - cliente_sin_decidir: Cliente no ha tomado decisión
 */
class TipoAlertaReparacion extends Model
{
    protected $table = 'tipos_alerta_reparacion';
    protected $primaryKey = 'tipo_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'prioridad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // --- Constantes para IDs de tipos ---
    const SLA_EXCEDIDO = 1;
    const SIN_RESPUESTA = 2;
    const CLIENTE_SIN_DECIDIR = 3;

    // --- Scopes ---
    
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // --- Helpers estáticos ---

    /**
     * Obtiene el ID del tipo por nombre
     */
    public static function getIdPorNombre(string $nombre): ?int
    {
        return static::where('nombre', $nombre)->value('tipo_id');
    }

    /**
     * Obtiene el tipo SLA Excedido
     */
    public static function slaExcedido(): ?self
    {
        return static::where('nombre', 'sla_excedido')->first();
    }

    // --- Relaciones ---

    public function alertas(): HasMany
    {
        return $this->hasMany(AlertaReparacion::class, 'tipo_alerta_id', 'tipo_id');
    }
}
