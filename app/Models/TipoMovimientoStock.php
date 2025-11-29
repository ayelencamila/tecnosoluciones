<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoMovimientoStock extends Model
{
    protected $table = 'tipos_movimiento_stock';
    protected $fillable = ['nombre', 'signo', 'activo'];
    
    protected $casts = [
        'activo' => 'boolean',
        'signo' => 'integer',
    ];
}
