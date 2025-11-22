<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleReparacion extends Model
{
    // --- ESTA LÍNEA ES LA SOLUCIÓN ---
    protected $table = 'detalle_reparaciones'; 
    // ---------------------------------

    protected $fillable = [
        'reparacion_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class, 'reparacion_id', 'reparacionID');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
