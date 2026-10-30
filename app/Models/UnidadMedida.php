<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'unidades_medida'; // Especificamos la tabla plural

    protected $fillable = ['nombre', 'abreviatura', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación: Una unidad de medida tiene muchos productos
     */
    public function productos()
    {
        return $this->hasMany(Producto::class, 'unidadMedidaID');
    }
}
