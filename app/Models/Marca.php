<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];
    
    // Relación: Una Marca tiene muchos Modelos 
    public function modelos()
    {
        return $this->hasMany(Modelo::class);
    }

    // Relación: Una Marca tiene muchos Productos
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    // Relación: Una Marca tiene muchas Reparaciones
    public function reparaciones()
    {
        return $this->hasMany(Reparacion::class, 'marca_id');
    }
}
