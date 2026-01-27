<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtapaImagenReparacion extends Model
{
    protected $table = 'etapas_imagen_reparacion';
    protected $primaryKey = 'etapa_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenReparacion::class, 'etapa_id', 'etapa_id');
    }
}
