<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Modelo ORDENES_COMPRA (CU-22)
 * 
 * Representa órdenes de compra generadas a proveedores.
 * 
 * Lineamientos aplicados:
 * - Larman: Trazabilidad completa (desde oferta hasta recepción)
 * - Kendall: Numeración correlativa única
 * - Elmasri 3FN: estado_id FK a tabla paramétrica
 * 
 * @property int $id
 * @property string $numero_oc
 * @property int $proveedor_id
 * @property int $oferta_id
 * @property int $user_id
 * @property int $estado_id
 * @property float $total_final
 * @property \Carbon\Carbon $fecha_emision
 * @property \Carbon\Carbon|null $fecha_envio
 * @property \Carbon\Carbon|null $fecha_confirmacion
 * @property string|null $observaciones
 * @property string|null $archivo_pdf Ruta del PDF generado
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Proveedor $proveedor
 * @property-read OfertaCompra $oferta
 * @property-read User $usuario
 * @property-read EstadoOrdenCompra $estado
 * @property-read \Illuminate\Database\Eloquent\Collection<DetalleOrdenCompra> $detalles
 */
class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero_oc',
        'proveedor_id',
        'oferta_id',
        'user_id',
        'estado_id',
        'total_final',
        'fecha_emision',
        'fecha_envio',
        'fecha_confirmacion',
        'observaciones',
        'archivo_pdf',
    ];

    protected $casts = [
        'total_final' => 'decimal:2',
        'fecha_emision' => 'datetime',
        'fecha_envio' => 'datetime',
        'fecha_confirmacion' => 'datetime',
    ];

    // --- RELACIONES (Elmasri) ---

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function oferta(): BelongsTo
    {
        return $this->belongsTo(OfertaCompra::class, 'oferta_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Estado de la orden (3FN - FK a tabla paramétrica)
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoOrdenCompra::class, 'estado_id');
    }

    /**
     * Elmasri: Entidad débil (relación 1:N identificada)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOrdenCompra::class, 'orden_compra_id');
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Genera el siguiente número de OC correlativo
     * 
     * Formato: OC-YYYYMMDD-XXX
     * CRÍTICO: Usa lockForUpdate() para prevenir condición de carrera
     */
    public static function generarNumeroOC(): string
    {
        return DB::transaction(function () {
            $hoy = now()->format('Ymd');
            
            // Contar órdenes del día actual
            $countHoy = self::whereDate('fecha_emision', now()->toDateString())
                ->lockForUpdate()
                ->count();
            
            $secuencia = str_pad($countHoy + 1, 3, '0', STR_PAD_LEFT);
            
            return "OC-{$hoy}-{$secuencia}";
        });
    }

    /**
     * Verifica si la orden tiene un estado específico
     */
    public function tieneEstado(string $nombreEstado): bool
    {
        return $this->estado_id === EstadoOrdenCompra::idPorNombre($nombreEstado);
    }

    /**
     * Envía la orden al proveedor (cambia estado)
     */
    public function marcarEnviada(): void
    {
        $this->update([
            'estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::ENVIADA),
            'fecha_envio' => now(),
        ]);
    }

    /**
     * Marca el envío como fallido
     */
    public function marcarEnvioFallido(): void
    {
        $this->update([
            'estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::ENVIO_FALLIDO),
        ]);
    }

    /**
     * Marca la orden como confirmada por el proveedor
     */
    public function marcarConfirmada(): void
    {
        $this->update([
            'estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::CONFIRMADA),
            'fecha_confirmacion' => now(),
        ]);
    }

    /**
     * Verifica el estado de recepción según detalles (CU-23)
     */
    public function actualizarEstadoRecepcion(): void
    {
        $totalPedido = $this->detalles->sum('cantidad_pedida');
        $totalRecibido = $this->detalles->sum('cantidad_recibida');

        if ($totalRecibido === 0) {
            return;
        }

        if ($totalRecibido >= $totalPedido) {
            $this->update(['estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_TOTAL)]);
        } else {
            $this->update(['estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL)]);
        }
    }

    /**
     * Cancela la orden
     */
    public function cancelar(string $motivo): void
    {
        $estadoActual = $this->estado->nombre ?? '';
        
        if (in_array($estadoActual, [EstadoOrdenCompra::RECIBIDA_TOTAL, EstadoOrdenCompra::RECIBIDA_PARCIAL])) {
            throw new \Exception('No se puede cancelar una orden que ya fue recibida');
        }

        $this->update([
            'estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::CANCELADA),
            'observaciones' => $motivo,
        ]);
    }

    /**
     * Obtiene la URL pública del PDF
     */
    public function getUrlPdfAttribute(): ?string
    {
        return $this->archivo_pdf ? asset('storage/' . $this->archivo_pdf) : null;
    }
}
