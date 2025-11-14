<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';

    protected $fillable = [
        'productoID',
        'tipoMovimiento',
        'cantidad',
        'stockAnterior',
        'stockNuevo',
        'motivo',
        'referenciaID',
        'referenciaTabla',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stockAnterior' => 'integer',
        'stockNuevo' => 'integer',
    ];

    /**
     * Relación: Un movimiento pertenece a un producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'productoID');
    }
}
