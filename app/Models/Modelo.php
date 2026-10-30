<?php

namespace App\Models;

use App\Models\Concerns\TenantPorRelacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    use HasFactory, TenantPorRelacion;

    /** Empresa heredada de marca: ver TenantPorRelacion. */
    protected static string $relacionTenant = 'marca';


    // AQUÍ ESTABA EL ERROR: Faltaba 'marca_id' en esta lista
    protected $fillable = [
        'marca_id', 
        'nombre', 
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relación Inversa
    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    // Relación: Un Modelo tiene muchas Reparaciones
    public function reparaciones()
    {
        return $this->hasMany(Reparacion::class, 'modelo_id');
    }
}