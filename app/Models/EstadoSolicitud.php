<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo ESTADOS_SOLICITUD (CU-20)
 * 
 * Tabla paramétrica para estados de solicitudes de cotización.
 * Evita hardcodeo de estados en el código de aplicación.
 * 
 * Lineamientos aplicados:
 * - Elmasri 3FN: Tabla paramétrica referenciada por FK
 * - Kendall: Estados configurables del flujo de negocio
 * 
 * Estados predefinidos:
 * - Abierta: Solicitud creada, lista para enviar
 * - Enviada: Enviada a proveedores, esperando respuestas
 * - Cerrada: Se eligió una oferta ganadora
 * - Vencida: Pasó la fecha límite
 * - Cancelada: Cancelada manualmente
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool $activo
 * @property int $orden
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<SolicitudCotizacion> $solicitudes
 */
class EstadoSolicitud extends Model
{
    use HasFactory;

    protected $table = 'estados_solicitud';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'requiere_gestion_ofertas',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'requiere_gestion_ofertas' => 'boolean',
        'orden' => 'integer',
    ];

    // --- RELACIONES ---

    /**
     * Solicitudes con este estado
     */
    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudCotizacion::class, 'estado_id');
    }

    // --- SCOPES ---

    /**
     * Solo estados activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Ordenados por campo orden
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    /**
     * Estados que requieren gestión de ofertas (CU-21)
     * Parametrizable desde la BD sin hardcodeo
     */
    public function scopeRequierenGestionOfertas($query)
    {
        return $query->where('requiere_gestion_ofertas', true);
    }

    // --- MÉTODOS DE CONSULTA ---

    /**
     * Obtiene el ID del estado por nombre
     */
    public static function obtenerIdPorNombre(string $nombre): ?int
    {
        return self::where('nombre', $nombre)->value('id');
    }

    /**
     * Obtiene el estado "Abierta"
     */
    public static function abierta(): ?self
    {
        return self::where('nombre', 'Abierta')->first();
    }

    /**
     * Obtiene el estado "Enviada"
     */
    public static function enviada(): ?self
    {
        return self::where('nombre', 'Enviada')->first();
    }

    /**
     * Obtiene el estado "Cerrada"
     */
    public static function cerrada(): ?self
    {
        return self::where('nombre', 'Cerrada')->first();
    }

    /**
     * Obtiene el estado "Vencida"
     */
    public static function vencida(): ?self
    {
        return self::where('nombre', 'Vencida')->first();
    }

    /**
     * Obtiene el estado "Cancelada"
     */
    public static function cancelada(): ?self
    {
        return self::where('nombre', 'Cancelada')->first();
    }
}
