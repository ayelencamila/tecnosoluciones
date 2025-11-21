<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ImagenReparacion extends Model
{
    protected $table = 'imagenes_reparacion';

    protected $fillable = [
        'reparacion_id',
        'ruta_archivo',
        'etapa', // 'ingreso', 'proceso', 'salida'
        'nombre_original'
    ];

    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class, 'reparacion_id', 'reparacionID');
    }

    // Accessor opcional: para obtener la URL completa fácilmente en el frontend
    // Uso: $imagen->url
    public function getUrlAttribute()
    {
        return Storage::url($this->ruta_archivo);
    }
}
