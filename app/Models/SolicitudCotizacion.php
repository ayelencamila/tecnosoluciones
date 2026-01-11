<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo SOLICITUDES_COTIZACION (CU-20)
 * 
 * Representa solicitudes de cotización enviadas a proveedores.
 * 
 * Lineamientos aplicados:
 * - Kendall: Control de estados del flujo de compras
 * - Larman: Patrón Experto (conoce su estado y puede validar)
 * 
 * @property int $id
 * @property int $proveedor_id
 * @property \Carbon\Carbon $fecha_emision
 * @property string $estado
 * @property string|null $hash_whatsapp
 * @property string|null $detalle_productos
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Proveedor $proveedor
 * @property-read \Illuminate\Database\Eloquent\Collection<OfertaCompra> $ofertas
 */
class SolicitudCotizacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_cotizacion';

    protected $fillable = [
        'proveedor_id',
        'fecha_emision',
        'estado',
        'hash_whatsapp',
        'detalle_productos',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'detalle_productos' => 'array',
    ];

    /**
     * Estados válidos según Kendall
     */
    const ESTADO_PENDIENTE = 'Pendiente';
    const ESTADO_RESPONDIDA = 'Respondida';
    const ESTADO_VENCIDA = 'Vencida';
    const ESTADO_CANCELADA = 'Cancelada';

    // --- RELACIONES (Elmasri) ---

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(OfertaCompra::class, 'solicitud_id');
    }

    /**
     * Detalles de productos solicitados (1FN - relación normalizada)
     * Reemplaza el campo JSON detalle_productos
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleSolicitudCotizacion::class, 'solicitud_id');
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Marca la solicitud como respondida
     */
    public function marcarRespondida(): void
    {
        $this->update(['estado' => self::ESTADO_RESPONDIDA]);
    }

    /**
     * Verifica si la solicitud está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Cancela la solicitud
     */
    public function cancelar(): void
    {
        if (!$this->estaPendiente()) {
            throw new \Exception('Solo se pueden cancelar solicitudes pendientes');
        }
        
        $this->update(['estado' => self::ESTADO_CANCELADA]);
    }
}
