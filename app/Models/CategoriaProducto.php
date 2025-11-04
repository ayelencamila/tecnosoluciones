<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaProducto extends Model
{
    protected $table = 'categorias_producto';

    protected $fillable = [
        'nombre',
        'tipoProducto',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación: Una categoría tiene muchos productos
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoriaProductoID');
    }
}
