<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedioPago extends Model
{
    use SoftDeletes;

    protected $table = 'medios_pago';
    protected $primaryKey = 'medioPagoID';

    protected $fillable = ['nombre', 'recargo_porcentaje', 'activo', 'instrucciones'];

    protected $casts = [
        'activo' => 'boolean',
        'recargo_porcentaje' => 'decimal:2',
    ];
}