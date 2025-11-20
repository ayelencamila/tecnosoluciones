<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory; 

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

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_id', 'deposito_id');
    }

    public function movimientos(): HasMany
    {
        // Movimientos de este registro de stock (Deposito + Producto)
        return $this->hasMany(MovimientoStock::class, 'stock_id', 'stock_id'); 
    }

    // --- LÓGICA DE NEGOCIO (GRASP: Experto en Información) ---

    /**
     * Aumenta la cantidad disponible (Entradas / CU-06: Anulación de Venta)
     * Implementación atómica.
     */
    public function incrementar(int $cantidad): void
    {
        $this->increment('cantidad_disponible', $cantidad);
    }

    /**
     * Disminuye la cantidad disponible (Salidas / CU-05: Registro de Venta)
     * Implementación atómica.
     */
    public function descontar(int $cantidad): void
    {
        $this->decrement('cantidad_disponible', $cantidad);
    }

    /**
     * Verifica si hay suficiente stock para una salida (Paso 9 de CU-05)
     */
    public function tieneDisponibilidad(int $cantidad): bool
    {
        // Se asegura que no haya stock negativo (CRQ–02)
        return $this->cantidad_disponible >= $cantidad;
    }
}