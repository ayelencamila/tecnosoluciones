<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedioPago extends Model
{
    use SoftDeletes;

    protected $table = 'medios_pago';
    protected $primaryKey = 'medioPagoID';

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'medioPagoID';
    }

    protected $fillable = ['nombre', 'recargo_porcentaje', 'activo', 'instrucciones'];

    protected $casts = [
        'activo' => 'boolean',
        'recargo_porcentaje' => 'decimal:2',
    ];

    /**
     * Relación: Un medio de pago tiene muchas ventas
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'medio_pago_id', 'medioPagoID');
    }
}