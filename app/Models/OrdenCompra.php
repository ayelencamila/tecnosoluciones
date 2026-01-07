<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo ORDENES_COMPRA (CU-22)
 * 
 * Representa órdenes de compra generadas a proveedores.
 * 
 * Lineamientos aplicados:
 * - Larman: Trazabilidad completa (desde oferta hasta recepción)
 * - Kendall: Numeración correlativa única
 * - Elmasri: Relación 1:1 con oferta elegida
 * 
 * @property int $id
 * @property string $numero_oc
 * @property int $proveedor_id
 * @property int $oferta_id
 * @property int $user_id
 * @property float $total
 * @property string $estado
 * @property \Carbon\Carbon $fecha_emision
 * @property \Carbon\Carbon|null $fecha_envio
 * @property string|null $observaciones
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Proveedor $proveedor
 * @property-read OfertaCompra $oferta
 * @property-read User $usuario
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
        'total',
        'estado',
        'fecha_emision',
        'fecha_envio',
        'observaciones',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'fecha_emision' => 'datetime',
        'fecha_envio' => 'datetime',
    ];

    /**
     * Estados válidos según Kendall (CU-22)
     */
    const ESTADO_BORRADOR = 'Borrador';
    const ESTADO_ENVIADA = 'Enviada';
    const ESTADO_ACEPTADA = 'Aceptada';
    const ESTADO_RECIBIDA_PARCIAL = 'Recibida Parcial';
    const ESTADO_RECIBIDA_TOTAL = 'Recibida Total';
    const ESTADO_CANCELADA = 'Cancelada';

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
     * CRÍTICO: Usa lockForUpdate() para prevenir condición de carrera
     * cuando múltiples usuarios generan OCs simultáneamente
     */
    public static function generarNumeroOC(): string
    {
        return \DB::transaction(function () {
            // PESSIMISTIC LOCKING: Bloquea la fila hasta que termine la transacción
            $ultimaOrden = self::lockForUpdate()
                ->latest('id')
                ->first();
            
            $numero = $ultimaOrden ? ((int) substr($ultimaOrden->numero_oc, 3)) + 1 : 1;
            
            return 'OC-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Envía la orden al proveedor
     */
    public function enviar(): void
    {
        if ($this->estado !== self::ESTADO_BORRADOR) {
            throw new \Exception('Solo se pueden enviar órdenes en estado Borrador');
        }

        $this->update([
            'estado' => self::ESTADO_ENVIADA,
            'fecha_envio' => now(),
        ]);

        // Marcar oferta como procesada
        $this->oferta->marcarProcesada();
    }

    /**
     * Marca la orden como aceptada por el proveedor
     */
    public function marcarAceptada(): void
    {
        $this->update(['estado' => self::ESTADO_ACEPTADA]);
    }

    /**
     * Verifica el estado de recepción según detalles (CU-23)
     */
    public function actualizarEstadoRecepcion(): void
    {
        $totalPedido = $this->detalles->sum('cantidad_pedida');
        $totalRecibido = $this->detalles->sum('cantidad_recibida');

        if ($totalRecibido === 0) {
            // No se ha recibido nada, mantener estado actual
            return;
        }

        if ($totalRecibido >= $totalPedido) {
            $this->update(['estado' => self::ESTADO_RECIBIDA_TOTAL]);
        } else {
            $this->update(['estado' => self::ESTADO_RECIBIDA_PARCIAL]);
        }
    }

    /**
     * Cancela la orden
     */
    public function cancelar(string $motivo): void
    {
        if (in_array($this->estado, [self::ESTADO_RECIBIDA_TOTAL, self::ESTADO_RECIBIDA_PARCIAL])) {
            throw new \Exception('No se puede cancelar una orden que ya fue recibida');
        }

        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'observaciones' => $motivo,
        ]);
    }
}
