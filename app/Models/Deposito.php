<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposito extends Model
{
    use BelongsToEmpresa;

    protected $table = 'depositos';

    protected $primaryKey = 'deposito_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'direccion',
        'activo',
        'esPrincipal',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'esPrincipal' => 'boolean',
    ];

    /**
     * Relación: Un depósito tiene muchos registros de stock
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'deposito_id', 'deposito_id');
    }

    /**
     * Scope: Solo depósitos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener el depósito principal
     */
    public static function principal()
    {
        return self::where('esPrincipal', true)->first();
    }
}
