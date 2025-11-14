<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoProducto extends Model
{
    protected $table = 'estados_producto';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * Relación: Un estado puede aplicar a muchos productos
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'estadoProductoID');
    }
}
