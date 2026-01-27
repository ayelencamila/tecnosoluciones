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
 * @property int $detalle_orden_id
 * @property int $producto_id
 * @property int $cantidad_recibida
 * @property string|null $observacion_item
 */
class DetalleRecepcion extends Model
{
    protected $table = 'detalle_recepciones';

    protected $fillable = [
        'recepcion_id',
        'detalle_orden_id',
        // producto_id se obtiene vía detalleOrden (3FN)
        'cantidad_recibida',
        'observacion_item',
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
     * Accessor para obtener el producto vía detalleOrden (3FN)
     * Uso: $detalleRecepcion->producto
     */
    public function producto(): BelongsTo
    {
        // Relación through para acceder al producto vía detalle de orden
        return $this->detalleOrden->producto();
    }

    /**
     * Accessor directo al producto (atajo conveniente)
     */
    public function getProductoAttribute()
    {
        return $this->detalleOrden?->producto;
    }
}
