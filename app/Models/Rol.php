<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'rol_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'permisos',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Usuarios que tienen este rol.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id', 'rol_id');
    }
}
