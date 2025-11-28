<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_ventas';
    protected $primaryKey = 'detalle_venta_id';
    protected $guarded = ['detalle_venta_id'];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'descuento_item' => 'decimal:2',
        'subtotal_neto' => 'decimal:2',
    ];

    /**
     * Relación: Un detalle pertenece a una Venta.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id', 'venta_id');
    }

    /**
     * Relación: Un detalle referencia a un Producto.
     * CORREGIDO: Usamos 'producto_id' para estandarizar con el resto del sistema.
     */
    public function producto(): BelongsTo
    {
        // Antes: 'productoID' -> Ahora: 'producto_id'
        return $this->belongsTo(Producto::class, 'producto_id', 'id');
    }

    /**
     * Relación: Precio histórico usado.
     */
    public function precioProducto(): BelongsTo
    {
        return $this->belongsTo(PrecioProducto::class, 'precio_producto_id', 'id');
    }

    /**
     * Relación N:M: Descuentos aplicados a este ítem.
     */
    public function descuentos(): BelongsToMany
    {
        return $this->belongsToMany(
            Descuento::class,
            'descuento_detalle_venta',
            'detalle_venta_id',
            'descuento_id'
        )->withPivot('monto_aplicado_item');
    }
}
