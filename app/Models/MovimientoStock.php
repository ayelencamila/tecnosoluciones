<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';

    protected $fillable = [
        'stock_id',       
        'productoID',
        'tipoMovimiento',
        'cantidad',
        'stockAnterior',
        'stockNuevo',
        'motivo',
        'referenciaID',
        'referenciaTabla',
        'user_id',        // Nos aseguramos que esté
        'fecha_movimiento', 
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stockAnterior' => 'integer',
        'stockNuevo' => 'integer',
    ];

    // Relaciones
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'productoID');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}