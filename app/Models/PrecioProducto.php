<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioProducto extends Model
{
    protected $table = 'precios_producto';

    protected $fillable = [
        'productoID',
        'tipoClienteID',
        'precio',
        'fechaDesde',
        'fechaHasta',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fechaDesde' => 'date',
        'fechaHasta' => 'date',
    ];

    /**
     * Relación: Un precio pertenece a un producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'productoID');
    }

    /**
     * Relación: Un precio aplica a un tipo de cliente
     */
    public function tipoCliente(): BelongsTo
    {
        return $this->belongsTo(TipoCliente::class, 'tipoClienteID', 'tipoClienteID');
    }

    /**
     * Verificar si el precio está vigente
     */
    public function esVigente(): bool
    {
        return is_null($this->fechaHasta);
    }
}
