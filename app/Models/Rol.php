<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use BelongsToEmpresa;

    protected $table = 'roles';

    protected $primaryKey = 'rol_id';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'es_admin_empresa',
        'descripcion',
        'permisos',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_admin_empresa' => 'boolean',
        'permisos' => 'array',
    ];

    /**
     * Usuarios que tienen este rol.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id', 'rol_id');
    }
}
