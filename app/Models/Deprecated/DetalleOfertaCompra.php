<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DETALLE_OFERTAS_COMPRA (CU-21)
 * 
 * Representa los productos incluidos en una oferta/cotización del proveedor.
 * Implementa 1FN: Un registro por cada producto-oferta con precio unitario.
 * 
 * Lineamientos aplicados:
 * - Elmasri 1FN: Atomicidad de valores (precio unitario, no total calculado)
 * - Entidad Débil con surrogate key + UNIQUE constraint
 * 
 * @property int $id
 * @property int $oferta_id FK a oferta padre
 * @property int $producto_id FK a producto
 * @property int $cantidad_ofrecida Cantidad que el proveedor puede suministrar
 * @property float $precio_unitario Precio por unidad ofrecido
 * @property bool $disponibilidad_inmediata Si está disponible de inmediato
 * @property int $dias_entrega Plazo de entrega en días
 * @property string|null $observaciones Notas específicas
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read OfertaCompra $oferta
 * @property-read Producto $producto
 * @property-read float $subtotal Accessor calculado
 */
class DetalleOfertaCompra extends Model
{
    use HasFactory;

    protected $table = 'detalle_ofertas_compra';

    protected $fillable = [
        'oferta_id',
        'producto_id',
        'cantidad_ofrecida',
        'precio_unitario',
        'disponibilidad_inmediata',
        'dias_entrega',
        'observaciones',
    ];

    protected $casts = [
        'cantidad_ofrecida' => 'integer',
        'precio_unitario' => 'decimal:2',
        'disponibilidad_inmediata' => 'boolean',
        'dias_entrega' => 'integer',
    ];

    // --- RELACIONES ---

    /**
     * Oferta de compra a la que pertenece
     */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(OfertaCompra::class, 'oferta_id');
    }

    /**
     * Producto ofrecido
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    // --- ACCESSORS ---

    /**
     * Calcula el subtotal (cantidad * precio_unitario)
     * No se almacena en BD (1FN: evitar datos derivados)
     */
    public function getSubtotalAttribute(): float
    {
        return $this->cantidad_ofrecida * $this->precio_unitario;
    }
}
