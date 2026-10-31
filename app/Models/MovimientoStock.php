<?php

namespace App\Models;

use App\Models\Concerns\TenantPorRelacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    use TenantPorRelacion;

    /** Empresa heredada de producto: ver TenantPorRelacion. */
    protected static string $relacionTenant = 'producto';

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'stock_id',       
        'productoID',
        'tipo_movimiento_id',
        'cantidad',
        'stockAnterior',
        'stockNuevo',
        'motivo',
        'referenciaID',
        'referenciaTabla',
        'user_id',        // Nos aseguramos que esté
        'fecha_movimiento', 
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stockAnterior' => 'integer',
        'stockNuevo' => 'integer',
        // Faltaba: sin el cast, `fecha_movimiento` llegaba como string y
        // cualquier codigo que la tratara como fecha fallaba.
        'fecha_movimiento' => 'datetime',
    ];

    // Relaciones
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'productoID');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(TipoMovimientoStock::class, 'tipo_movimiento_id');
    }
}