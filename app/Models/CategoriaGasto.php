<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaGasto extends Model
{
    use HasFactory;

    protected $table = 'categorias_gasto';
    protected $primaryKey = 'categoria_gasto_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'categoria_gasto_id', 'categoria_gasto_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeGastos($query)
    {
        return $query->where('tipo', 'gasto');
    }

    public function scopePerdidas($query)
    {
        return $query->where('tipo', 'perdida');
    }
}
