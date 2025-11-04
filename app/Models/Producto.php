<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'marca',
        'unidadMedida',
        'categoriaProductoID',
        'estadoProductoID',
        'stockActual',
        'stockMinimo',
    ];

    protected $casts = [
        'stockActual' => 'integer',
        'stockMinimo' => 'integer',
    ];

    /**
     * Relación: Un producto pertenece a una categoría
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoriaProductoID');
    }

    /**
     * Relación: Un producto tiene un estado
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoProducto::class, 'estadoProductoID');
    }

    /**
     * Relación: Un producto tiene muchos precios históricos
     */
    public function precios(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'productoID');
    }

    /**
     * Relación: Un producto tiene muchos movimientos de stock
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'productoID');
    }

    /**
     * Obtener el precio vigente para un tipo de cliente
     */
    public function precioVigente($tipoClienteID)
    {
        return $this->precios()
            ->where('tipoClienteID', $tipoClienteID)
            ->whereNull('fechaHasta')
            ->first();
    }

    /**
     * Verificar si el stock está por debajo del mínimo
     */
    public function stockBajo(): bool
    {
        return $this->stockActual <= $this->stockMinimo;
    }
}
