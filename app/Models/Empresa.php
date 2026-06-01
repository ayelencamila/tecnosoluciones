<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo EMPRESAS - Tenant raiz del sistema multi-empresa.
 *
 * Cada empresa es una entidad fuerte (Elmasri Cap.7) que posee:
 *  - Sus propios usuarios y roles (estos ultimos como entidad debil)
 *  - Sus propios datos operacionales (clientes, productos, ventas, etc.)
 *
 * @property int $id
 * @property string $nombre
 * @property string $slug
 * @property string|null $logo
 * @property string|null $cuit
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $direccion
 * @property bool $activa
 */
class Empresa extends Model
{
    use SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'slug',
        'logo',
        'cuit',
        'telefono',
        'email',
        'direccion',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /**
     * Usuarios que pertenecen a esta empresa.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    /**
     * Roles definidos por esta empresa (entidad debil: cada empresa tiene
     * sus propios roles con permisos personalizados).
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Rol::class, 'empresa_id');
    }
}
