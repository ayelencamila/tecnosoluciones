<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDescuento extends Model
{
    use HasFactory;

    protected $table = 'tipos_descuento';
    
    protected $fillable = ['nombre', 'codigo', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
