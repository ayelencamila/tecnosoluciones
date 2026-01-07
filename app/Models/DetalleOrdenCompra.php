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
 * - Elmasri: Entidad Débil con PK compuesta (orden_compra_id, producto_id)
 * - Relación 1:N Identificada
 * - CU-23: Soporte para recepción parcial
 * 
 * @property int $orden_compra_id (PK compuesta parte 1)
 * @property int $producto_id (PK compuesta parte 2)
 * @property int $cantidad_pedida
 * @property int $cantidad_recibida
 * @property float $precio_unitario_pactado
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read OrdenCompra $ordenCompra
 * @property-read Producto $producto
 */
class DetalleOrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'detalle_orden_compra';
    
    /**
     * Elmasri: PK Compuesta (Entidad Débil)
     * La clave primaria es una combinación de orden_compra_id y producto_id
     */
    protected $primaryKey = ['orden_compra_id', 'producto_id'];
    
    /**
     * Indica que NO es auto-incremental (PK compuesta)
     */
    public $incrementing = false;

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'cantidad_pedida',
        'cantidad_recibida',
        'precio_unitario_pactado',
    ];

    protected $casts = [
        'cantidad_pedida' => 'integer',
        'cantidad_recibida' => 'integer',
        'precio_unitario_pactado' => 'decimal:2',
    ];

    // --- RELACIONES (Elmasri: Entidad Débil) ---

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Override para manejar PK compuesta en búsquedas
     * 
     * @param array $ids ['orden_compra_id' => x, 'producto_id' => y]
     * @return static|null
     */
    public static function findByCompoundKey(int $ordenCompraId, int $productoId)
    {
        return static::where('orden_compra_id', $ordenCompraId)
                     ->where('producto_id', $productoId)
                     ->first();
    }

    /**
     * Override setKeysForSaveQuery para manejar PK compuesta en updates
     * 
     * CRÍTICO: Eloquent nativo lucha con PKs compuestas al hacer updates.
     * Este método garantiza que use ambas columnas en el WHERE.
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the value for a given key
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }

    /**
     * Registra la recepción de productos (CU-23)
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

    /**
     * Obtiene la cantidad pendiente
     */
    public function getCantidadPendienteAttribute(): int
    {
        return max(0, $this->cantidad_pedida - $this->cantidad_recibida);
    }

    /**
     * Calcula el subtotal del detalle
     */
    public function getSubtotalAttribute(): float
    {
        return $this->cantidad_pedida * $this->precio_unitario_pactado;
    }
}
