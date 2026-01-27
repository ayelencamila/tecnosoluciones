<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DETALLE_SOLICITUDES_COTIZACION (CU-20)
 * 
 * Representa los productos incluidos en una solicitud de cotización.
 * Implementa 1FN: Un registro por cada producto-solicitud (no JSON).
 * 
 * Lineamientos aplicados:
 * - Elmasri 1FN: Atomicidad de valores
 * - Entidad Débil con surrogate key + UNIQUE constraint
 * 
 * @property int $id
 * @property int $solicitud_id FK a solicitud padre
 * @property int $producto_id FK a producto
 * @property int $cantidad_sugerida Cantidad a cotizar
 * @property string|null $observaciones Notas específicas del producto
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read SolicitudCotizacion $solicitud
 * @property-read Producto $producto
 */
class DetalleSolicitudCotizacion extends Model
{
    use HasFactory;

    protected $table = 'detalle_solicitudes_cotizacion';

    protected $fillable = [
        'solicitud_id',
        'producto_id',
        'cantidad_sugerida',
        'observaciones',
    ];

    protected $casts = [
        'cantidad_sugerida' => 'integer',
    ];

    // --- RELACIONES ---

    /**
     * Solicitud de cotización a la que pertenece
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCotizacion::class, 'solicitud_id');
    }

    /**
     * Producto solicitado
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
