<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoReparacion extends Model
{
    protected $table = 'estados_reparacion';
    protected $primaryKey = 'estadoReparacionID';

    protected $fillable = ['nombreEstado', 'descripcion'];

    // Relación inversa: Un estado puede tener muchas reparaciones
    public function reparaciones(): HasMany
    {
        return $this->hasMany(Reparacion::class, 'estado_reparacion_id', 'estadoReparacionID');
    }
}
