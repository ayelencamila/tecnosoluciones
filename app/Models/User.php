<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Relación: Un usuario pertenece a un rol.
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }

    /**
     * Reparaciones asignadas a este usuario (técnico).
     */
    public function reparacionesAsignadas(): HasMany
    {
        return $this->hasMany(Reparacion::class, 'tecnico_id');
    }

    /**
     * Accessor para obtener el nombre del rol (para compatibilidad con frontend).
     * Lee directamente de la BD sin necesidad de modelo Rol.
     */
    public function getRoleAttribute(): ?string
    {
        if (!$this->rol_id) {
            return null;
        }

        // Cache en el objeto para evitar múltiples queries
        if (!isset($this->attributes['_cached_role'])) {
            $this->attributes['_cached_role'] = \DB::table('roles')
                ->where('rol_id', $this->rol_id)
                ->value('nombre');
        }

        return $this->attributes['_cached_role'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
