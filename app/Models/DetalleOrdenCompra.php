<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DETALLE_ORDEN_COMPRA (CU-23)
 * 
 * Representa el detalle de productos en una orden de compra.
 * 
 * Lineamientos aplicados:
 * - Surrogate Key (id) + UNIQUE constraint (orden_compra_id, producto_id)
 * - Patrón alineado con DetalleVenta y DetalleReparacion
 * - CU-23: Soporte para recepción parcial
 * 
 * @property int $id Surrogate Key
 * @property int $orden_compra_id FK a orden de compra
 * @property int $producto_id FK a producto
 * @property int $cantidad_pedida
 * @property int $cantidad_recibida
 * @property float $precio_unitario Precio pactado con proveedor
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read OrdenCompra $ordenCompra
 * @property-read Producto $producto
 * @property-read int $cantidad_pendiente Accessor
 * @property-read float $subtotal Accessor
 */
class DetalleOrdenCompra extends Model
{
    use HasFactory;

    /**
     * Nombre correcto de la tabla (plural)
     */
    protected $table = 'detalle_ordenes_compra';
    
    /**
     * Surrogate Key estándar de Laravel
     * La integridad se garantiza con UNIQUE(orden_compra_id, producto_id)
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'cantidad_pedida',
        'cantidad_recibida',
        'precio_unitario',
    ];

    protected $casts = [
        'cantidad_pedida' => 'integer',
        'cantidad_recibida' => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    // --- RELACIONES ---

    /**
     * Orden de compra a la que pertenece el detalle
     */
    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    /**
     * Producto incluido en el detalle
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    // --- MÉTODOS DE BÚSQUEDA ---

    /**
     * Busca un detalle por la combinación única orden + producto
     * 
     * @param int $ordenCompraId
     * @param int $productoId
     * @return static|null
     */
    public static function findByOrdenProducto(int $ordenCompraId, int $productoId)
    {
        return static::where('orden_compra_id', $ordenCompraId)
                     ->where('producto_id', $productoId)
                     ->first();
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Registra la recepción de productos (CU-23)
     * 
     * @param int $cantidad Cantidad recibida
     * @throws \InvalidArgumentException Si cantidad <= 0
     * @throws \Exception Si excede lo pedido
     */
    public function registrarRecepcion(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0');
        }

        $nuevaCantidad = $this->cantidad_recibida + $cantidad;

        if ($nuevaCantidad > $this->cantidad_pedida) {
            throw new \Exception('No se puede recibir más de lo pedido');
        }

        $this->update(['cantidad_recibida' => $nuevaCantidad]);

        // Actualizar estado de la orden padre
        $this->ordenCompra->actualizarEstadoRecepcion();
    }

    /**
     * Verifica si el detalle está completo
     */
    public function estaCompleto(): bool
    {
        return $this->cantidad_recibida >= $this->cantidad_pedida;
    }

    // --- ACCESSORS ---

    /**
     * Obtiene la cantidad pendiente de recibir
     */
    public function getCantidadPendienteAttribute(): int
    {
        return max(0, $this->cantidad_pedida - $this->cantidad_recibida);
    }

    /**
     * Calcula el subtotal del detalle (cantidad * precio)
     */
    public function getSubtotalAttribute(): float
    {
        return $this->cantidad_pedida * $this->precio_unitario;
    }
}
