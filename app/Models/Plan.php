<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan comercial.
 *
 * No lleva el trait BelongsToEmpresa: los planes son del PROVEEDOR, no de
 * una empresa cliente. Son el catálogo que se les vende.
 */
class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'precio_mensual', 'activo', 'orden'];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function limites(): HasMany
    {
        return $this->hasMany(PlanLimite::class);
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    /**
     * Límite de este plan para una clave, o null si el plan no lo define
     * (que se interpreta como sin límite).
     */
    public function limite(string $clave): ?PlanLimite
    {
        return $this->limites->firstWhere('clave', $clave);
    }
}
