<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DETALLE_RECEPCIONES (CU-23)
 * 
 * Entidad débil que registra los productos y cantidades recibidas.
 * 
 * @property int $id
 * @property int $recepcion_id
 * @property int|null $detalle_orden_id
 * @property int|null $producto_id
 * @property int $cantidad_recibida
 * @property float|null $precio_unitario
 * @property string|null $observacion_item
 */
class DetalleRecepcion extends Model
{
    protected $table = 'detalle_recepciones';

    protected $fillable = [
        'recepcion_id',
        'detalle_orden_id',
        'producto_id',
        'cantidad_recibida',
        'precio_unitario',
        'observacion_item',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
    ];

    // --- RELACIONES ---

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(RecepcionMercaderia::class, 'recepcion_id');
    }

    public function detalleOrden(): BelongsTo
    {
        return $this->belongsTo(DetalleOrdenCompra::class, 'detalle_orden_id');
    }

    /**
     * Relación directa con producto (para recepciones sin OC)
     */
    public function productoDirecto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Accessor para obtener el producto (desde detalle OC o directo)
     */
    public function getProductoAttribute()
    {
        // Si tiene producto_id directo, usarlo
        if ($this->producto_id) {
            return $this->productoDirecto;
        }
        // Sino obtener vía detalleOrden (3FN)
        return $this->detalleOrden?->producto;
    }
}
