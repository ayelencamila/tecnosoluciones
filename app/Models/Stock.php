<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
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

    /**
     * Relación: Stock pertenece a un Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'productoID', 'id');
    }

    /**
     * Relación: Stock pertenece a un Depósito
     */
    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id', 'deposito_id');
    }

    /**
     * Relación: Stock tiene muchos movimientos
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'stock_id', 'stock_id');
    }

    /**
     * Verificar si hay stock disponible
     */
    public function tieneDisponibilidad(int $cantidad): bool
    {
        return $this->cantidad_disponible >= $cantidad;
    }

    /**
     * Descontar stock
     */
    public function descontar(int $cantidad): void
    {
        $this->cantidad_disponible -= $cantidad;
        $this->save();
    }

    /**
     * Incrementar stock
     */
    public function incrementar(int $cantidad): void
    {
        $this->cantidad_disponible += $cantidad;
        $this->save();
    }

    /**
     * Verificar si está por debajo del stock mínimo
     */
    public function bajaMinimoStock(): bool
    {
        return $this->cantidad_disponible <= $this->stock_minimo;
    }
}
