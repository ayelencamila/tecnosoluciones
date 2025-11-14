<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DetalleVenta extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada al modelo.
     */
    protected $table = 'detalle_ventas';

    /**
     * La clave primaria asociada con la tabla.
     */
    protected $primaryKey = 'detalle_venta_id';

    /**
     * Los atributos que no son asignables masivamente.
     */
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
     * FK: 'venta_id' (en detalle_ventas) -> PK: 'venta_id' (en ventas)
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id', 'venta_id');
    }

    /**
     * Relación: Un detalle referencia a un Producto.
     * FK: 'productoID' (en detalle_ventas) -> PK: 'id' (en productos)
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'productoID', 'id');
    }

    /**
     * Relación: Un detalle usó un PrecioProducto específico.
     * FK: 'precio_producto_id' (en detalle_ventas) -> PK: 'id' (en precios_producto)
     */
    public function precioProducto(): BelongsTo
    {
        return $this->belongsTo(PrecioProducto::class, 'precio_producto_id', 'id');
    }

    /**
     * Relación N:M: Descuentos aplicados a ESTE ITEM.
     */
    public function descuentos(): BelongsToMany
    {
        return $this->belongsToMany(
            Descuento::class,
            'descuento_detalle_venta', // La segunda tabla pivote
            'detalle_venta_id',
            'descuento_id'
        )->withPivot('monto_aplicado_item');
    }
}
