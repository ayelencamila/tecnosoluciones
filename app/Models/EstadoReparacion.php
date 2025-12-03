<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoReparacion extends Model
{
    protected $table = 'estados_reparacion';
    protected $primaryKey = 'estadoReparacionID';

    protected $fillable = ['nombreEstado', 'descripcion'];

    // Accessor para compatibilidad con código que usa 'nombre'
    public function getNombreAttribute()
    {
        return $this->nombreEstado;
    }

    // --- CONSTANTES DE ESTADO (Single Source of Truth) ---
    // Usamos estas constantes en todo el código en lugar de escribir el texto a mano.
    public const RECIBIDO = 'Recibido';
    public const DIAGNOSTICO = 'Diagnóstico';
    public const EN_REPARACION = 'En Reparación';
    public const ESPERANDO_REPUESTO = 'Esperando Repuesto';
    public const DEMORADO = 'Demorado';
    public const LISTO = 'Listo';
    public const ENTREGADO = 'Entregado';
    public const CANCELADO = 'Cancelado';
    public const ANULADO = 'Anulado';

    // Relación inversa
    public function reparaciones(): HasMany
    {
        return $this->hasMany(Reparacion::class, 'estado_reparacion_id', 'estadoReparacionID');
    }
}