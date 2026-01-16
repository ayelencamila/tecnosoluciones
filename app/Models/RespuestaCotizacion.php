<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo RESPUESTAS_COTIZACION (CU-20)
 * 
 * Representa la respuesta de un proveedor para un producto específico
 * dentro de una solicitud de cotización.
 * 
 * Lineamientos aplicados:
 * - Elmasri 1FN: Datos atómicos
 * - Entidad débil de CotizacionProveedor
 * - Larman: Patrón Experto (sabe calcular subtotales)
 * 
 * @property int $id
 * @property int $cotizacion_proveedor_id FK a cotizacion del proveedor
 * @property int $producto_id FK a producto
 * @property float $precio_unitario Precio ofrecido
 * @property int $cantidad_disponible Cantidad que puede proveer
 * @property int $plazo_entrega_dias Días hábiles de entrega
 * @property string|null $observaciones Notas del proveedor
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read CotizacionProveedor $cotizacionProveedor
 * @property-read Producto $producto
 */
class RespuestaCotizacion extends Model
{
    use HasFactory;

    protected $table = 'respuestas_cotizacion';

    protected $fillable = [
        'cotizacion_proveedor_id',
        'producto_id',
        'precio_unitario',
        'cantidad_disponible',
        'plazo_entrega_dias',
        'observaciones',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'cantidad_disponible' => 'integer',
        'plazo_entrega_dias' => 'integer',
    ];

    // --- RELACIONES ---

    /**
     * Cotización del proveedor a la que pertenece
     */
    public function cotizacionProveedor(): BelongsTo
    {
        return $this->belongsTo(CotizacionProveedor::class, 'cotizacion_proveedor_id');
    }

    /**
     * Producto cotizado
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Calcula el subtotal para esta línea
     */
    public function calcularSubtotal(): float
    {
        return $this->precio_unitario * $this->cantidad_disponible;
    }

    /**
     * Acceso rápido a la solicitud
     */
    public function getSolicitudAttribute()
    {
        return $this->cotizacionProveedor->solicitud;
    }

    /**
     * Acceso rápido al proveedor
     */
    public function getProveedorAttribute()
    {
        return $this->cotizacionProveedor->proveedor;
    }
}
