<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AplicabilidadDescuento extends Model
{
    use HasFactory;

    protected $table = 'aplicabilidades_descuento';
    
    protected $fillable = ['nombre', 'codigo', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
