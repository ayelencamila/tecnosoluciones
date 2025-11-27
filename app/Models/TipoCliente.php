<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoCliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_cliente';
    protected $primaryKey = 'tipoClienteID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombreTipo',
        'descripcion',
        'activo',
    ];

    /* * --- COMENTAMOS ESTO PARA PERMITIR ABM ---
     * public const TIPOS_VALIDOS = ['Mayorista', 'Minorista'];
     * protected static function boot() { ... }
     */

    // Helpers para lógica de código (si se mantienen los IDs fijos en seeder)
    public function esMayorista(): bool
    {
        return $this->nombreTipo === 'Mayorista';
    }

    public function esMinorista(): bool
    {
        return $this->nombreTipo === 'Minorista';
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'tipoClienteID', 'tipoClienteID');
    }
}