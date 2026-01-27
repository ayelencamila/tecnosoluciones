<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo OFERTAS_COMPRA (CU-21)
 * 
 * Representa ofertas/cotizaciones recibidas de proveedores.
 * 
 * Lineamientos aplicados:
 * - Larman: Trazabilidad total (vincula solicitud con orden)
 * - Kendall: Estados del flujo de selección (FK a estados_oferta)
 * - Elmasri 3FN: estado_id en lugar de string hardcodeado
 * 
 * @property int $id
 * @property string $codigo_oferta Código único OF-YYYYMM-XXXX
 * @property int $proveedor_id FK a proveedores
 * @property int|null $solicitud_id FK a solicitudes_cotizacion
 * @property int|null $cotizacion_proveedor_id FK si vino por Magic Link
 * @property int $user_id Usuario que registró
 * @property \Carbon\Carbon $fecha_recepcion
 * @property \Carbon\Carbon|null $validez_hasta
 * @property string|null $archivo_adjunto Ruta del PDF/imagen
 * @property int $estado_id FK a estados_oferta
 * @property string|null $observaciones
 * @property float $total_estimado Suma de detalles
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Proveedor $proveedor
 * @property-read SolicitudCotizacion|null $solicitud
 * @property-read User $user
 * @property-read EstadoOferta $estado
 * @property-read \Illuminate\Database\Eloquent\Collection<OrdenCompra> $ordenesCompra
 * @property-read \Illuminate\Database\Eloquent\Collection<DetalleOfertaCompra> $detalles
 */
class OfertaCompra extends Model
{
    use HasFactory;

    protected $table = 'ofertas_compra';

    protected $fillable = [
        'codigo_oferta',
        'proveedor_id',
        'solicitud_id',
        'cotizacion_proveedor_id',
        'user_id',
        'fecha_recepcion',
        'validez_hasta',
        'archivo_adjunto',
        'estado_id',
        'observaciones',
        'total_estimado',
    ];

    protected $casts = [
        'fecha_recepcion' => 'datetime',
        'validez_hasta' => 'datetime',
        'total_estimado' => 'decimal:2',
    ];

    // --- RELACIONES (Elmasri) ---

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCotizacion::class, 'solicitud_id');
    }

    /**
     * Estado de la oferta (3FN - FK a tabla paramétrica)
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoOferta::class, 'estado_id');
    }

    /**
     * Usuario que registró la oferta
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'oferta_id');
    }

    /**
     * Detalles de productos ofrecidos (1FN - relación normalizada)
     * Contiene precios unitarios por producto
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOfertaCompra::class, 'oferta_id');
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Marca la oferta como elegida para generar OC
     */
    public function elegir(): void
    {
        $estadoRechazadaId = EstadoOferta::idPorNombre(EstadoOferta::RECHAZADA);
        
        if ($this->estado_id === $estadoRechazadaId) {
            throw new \Exception('No se puede elegir una oferta rechazada');
        }

        $this->update(['estado_id' => EstadoOferta::idPorNombre(EstadoOferta::ELEGIDA)]);
        
        // Actualizar solicitud si existe
        if ($this->solicitud) {
            $this->solicitud->marcarRespondida();
        }
    }

    /**
     * Rechaza la oferta
     */
    public function rechazar(string $motivo = null): void
    {
        $this->update([
            'estado_id' => EstadoOferta::idPorNombre(EstadoOferta::RECHAZADA),
            'observaciones' => $motivo ?? $this->observaciones,
        ]);
    }

    /**
     * Marca como procesada (ya se generó la OC)
     */
    public function marcarProcesada(): void
    {
        $this->update(['estado_id' => EstadoOferta::idPorNombre(EstadoOferta::PROCESADA)]);
    }

    /**
     * Verifica si la oferta está en un estado específico
     */
    public function tieneEstado(string $nombreEstado): bool
    {
        return $this->estado_id === EstadoOferta::idPorNombre($nombreEstado);
    }

    /**
     * Calcula el score para ranking (CU-21)
     * Considera precio y plazo máximo de entrega de los detalles
     */
    public function calcularScore(): float
    {
        $total = $this->total_estimado ?: $this->detalles->sum('subtotal');
        $plazoMax = $this->detalles->max('dias_entrega') ?? 30;
        
        $scorePrecio = 1 / max($total, 1); // Menor precio = mayor score
        $scorePlazo = 1 / max($plazoMax, 1); // Menor plazo = mayor score
        
        return ($scorePrecio * 0.6) + ($scorePlazo * 0.4);
    }
}
