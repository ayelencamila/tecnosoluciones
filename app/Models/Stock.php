<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    protected $table = 'stock'; // Tabla singular

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

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id', 'deposito_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'stock_id', 'stock_id');
    }

    // --- LÓGICA DE NEGOCIO (GRASP: Experto en Información) ---
    // Estos son los métodos que faltaban y causaban el error 500

    /**
     * Aumenta la cantidad disponible (Entradas o Ajustes positivos)
     */
    public function incrementar(int $cantidad): void
    {
        $this->increment('cantidad_disponible', $cantidad);
    }

    /**
     * Disminuye la cantidad disponible (Salidas)
     */
    public function descontar(int $cantidad): void
    {
        $this->decrement('cantidad_disponible', $cantidad);
    }

    /**
     * Verifica si hay suficiente stock para una salida
     */
    public function tieneDisponibilidad(int $cantidad): bool
    {
        return $this->cantidad_disponible >= $cantidad;
    }
}