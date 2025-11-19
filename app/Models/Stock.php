<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    // 1. CORRECCIÓN: Definir nombre de tabla explícito (Evita error 'stocks table not found')
    protected $table = 'stock'; 

    protected $primaryKey = 'stock_id';

    protected $fillable = [
        'productoID',
        'deposito_id',
        'cantidad_disponible',
        'stock_minimo',
    ];

    protected $casts = [
        'cantidad_disponible' => 'integer',
        'stock_minimo' => 'integer',
    ];

    // --- RELACIONES ---

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'productoID', 'id');
    }

    // 2. CORRECCIÓN: Agregar la relación 'deposito' (Evita error 'undefined relationship')
    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id', 'deposito_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'stock_id', 'stock_id');
    }
}
