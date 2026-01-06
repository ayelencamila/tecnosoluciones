<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoReparacion extends Model
{
    protected $table = 'estados_reparacion';
    protected $primaryKey = 'estadoReparacionID';

    protected $fillable = ['nombreEstado', 'descripcion'];

    // Accessor para compatibilidad con código que usa 'nombre'
    public function getNombreAttribute()
    {
        return $this->nombreEstado;
    }

    // Relación inversa
    public function reparaciones(): HasMany
    {
        return $this->hasMany(Reparacion::class, 'estado_reparacion_id', 'estadoReparacionID');
    }
    
    /**
     * Verifica si este estado es final (no permite más modificaciones)
     * Los estados finales son: Entregado, Anulado
     */
    public function esFinal(): bool
    {
        return in_array($this->nombreEstado, ['Entregado', 'Anulado']);
    }
    
    /**
     * Obtiene un estado por su nombre
     */
    public static function porNombre(string $nombre): ?self
    {
        return static::where('nombreEstado', $nombre)->first();
    }
}