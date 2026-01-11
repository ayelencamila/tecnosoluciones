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
 * - Kendall: Estados del flujo de selección
 * 
 * @property int $id
 * @property int $proveedor_id
 * @property int|null $solicitud_id
 * @property float $precio_total
 * @property int|null $plazo_entrega_real
 * @property string $estado
 * @property string|null $ruta_adjunto
 * @property string|null $observaciones
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Proveedor $proveedor
 * @property-read SolicitudCotizacion|null $solicitud
 * @property-read \Illuminate\Database\Eloquent\Collection<OrdenCompra> $ordenesCompra
 */
class OfertaCompra extends Model
{
    use HasFactory;

    protected $table = 'ofertas_compra';

    protected $fillable = [
        'proveedor_id',
        'solicitud_id',
        'precio_total',
        'plazo_entrega_real',
        'estado',
        'ruta_adjunto',
        'observaciones',
    ];

    protected $casts = [
        'precio_total' => 'decimal:2',
        'plazo_entrega_real' => 'integer',
    ];

    /**
     * Estados válidos según Kendall
     */
    const ESTADO_PRE_APROBADA = 'Pre-aprobada';
    const ESTADO_ELEGIDA = 'Elegida';
    const ESTADO_PROCESADA = 'Procesada';
    const ESTADO_RECHAZADA = 'Rechazada';

    // --- RELACIONES (Elmasri) ---

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCotizacion::class, 'solicitud_id');
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
        if ($this->estado === self::ESTADO_RECHAZADA) {
            throw new \Exception('No se puede elegir una oferta rechazada');
        }

        $this->update(['estado' => self::ESTADO_ELEGIDA]);
        
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
            'estado' => self::ESTADO_RECHAZADA,
            'observaciones' => $motivo ?? $this->observaciones,
        ]);
    }

    /**
     * Marca como procesada (ya se generó la OC)
     */
    public function marcarProcesada(): void
    {
        $this->update(['estado' => self::ESTADO_PROCESADA]);
    }

    /**
     * Calcula el score para ranking (CU-21)
     * Considera precio y plazo de entrega
     */
    public function calcularScore(): float
    {
        $scorePrecio = 1 / max($this->precio_total, 1); // Menor precio = mayor score
        $scorePlazo = 1 / max($this->plazo_entrega_real ?? 30, 1); // Menor plazo = mayor score
        
        return ($scorePrecio * 0.6) + ($scorePlazo * 0.4);
    }
}
