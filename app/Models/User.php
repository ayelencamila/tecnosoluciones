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
        'telefono',
        'password',
        'rol_id',
        'activo',           
        'bloqueado_hasta',
        'foto_perfil',
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
     * Accessor para obtener el nombre del rol.
     */
    public function getRoleAttribute(): ?string
    {
        if (!$this->rol_id) {
            return null;
        }

        return $this->relationLoaded('rol') 
            ? $this->rol?->nombre 
            : \DB::table('roles')->where('rol_id', $this->rol_id)->value('nombre');
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
            'activo' => 'boolean',           
            'bloqueado_hasta' => 'datetime',
        ];
    }
    
    /**
     * Verifica si el usuario está bloqueado por seguridad.
     */
    public function estaBloqueado(): bool
    {
        // Si tiene fecha de bloqueo y esa fecha es EN EL FUTURO, está bloqueado.
        return $this->bloqueado_hasta && $this->bloqueado_hasta->isFuture();
    }

    /**
     * Verifica si el actor está activo (no dado de baja).
     */
    public function estaActivo(): bool
    {
        return $this->activo;
    }
}
