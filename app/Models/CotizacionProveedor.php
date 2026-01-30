<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Modelo COTIZACIONES_PROVEEDORES (CU-20)
 * 
 * Representa la relación entre una solicitud de cotización y un proveedor.
 * Implementa el patrón Magic Link: cada registro tiene un token único
 * que permite al proveedor acceder a un portal público sin autenticación.
 * 
 * Lineamientos aplicados:
 * - Elmasri: Tabla intermedia N:M (solicitud-proveedor)
 * - Kendall: Magic Link para acceso externo seguro
 * - Larman: Patrón Experto (conoce si puede responder)
 * 
 * @property int $id
 * @property int $solicitud_id FK a solicitud de cotización
 * @property int $proveedor_id FK a proveedor
 * @property string $token_unico Token UUID para Magic Link
 * @property string $estado_envio Pendiente|Enviado|Fallido
 * @property Carbon|null $fecha_envio Cuándo se envió el link
 * @property Carbon|null $fecha_respuesta Cuándo respondió el proveedor
 * @property string|null $motivo_rechazo Si rechazó, por qué
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read SolicitudCotizacion $solicitud
 * @property-read Proveedor $proveedor
 * @property-read \Illuminate\Database\Eloquent\Collection<RespuestaCotizacion> $respuestas
 */
class CotizacionProveedor extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones_proveedores';

    protected $fillable = [
        'solicitud_id',
        'proveedor_id',
        'token_unico',
        'estado_envio',
        'fecha_envio',
        'fecha_respuesta',
        'motivo_rechazo',
        'elegida',
        'recordatorios_enviados',
        'ultimo_recordatorio',
        // Campos agregados al simplificar modelo (antes en ofertas_compra)
        'total_estimado',
        'validez_hasta',
        'archivo_adjunto',
        'observaciones_respuesta',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_respuesta' => 'datetime',
        'ultimo_recordatorio' => 'datetime',
        'recordatorios_enviados' => 'integer',
        'elegida' => 'boolean',
        'total_estimado' => 'decimal:2',
        'validez_hasta' => 'date',
    ];

    // --- BOOT (Generar token automáticamente) ---

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token_unico)) {
                $model->token_unico = Str::uuid()->toString();
            }
        });
    }

    // --- RELACIONES ---

    /**
     * Solicitud de cotización
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCotizacion::class, 'solicitud_id');
    }

    /**
     * Proveedor invitado a cotizar
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Respuestas con precios por producto
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaCotizacion::class, 'cotizacion_proveedor_id');
    }

    /**
     * Orden de compra generada desde esta cotización (si fue elegida)
     */
    public function ordenCompra(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrdenCompra::class, 'cotizacion_proveedor_id');
    }

    // --- SCOPES ---

    /**
     * Cotizaciones pendientes de envío
     */
    public function scopePendientesEnvio($query)
    {
        return $query->where('estado_envio', 'Pendiente');
    }

    /**
     * Cotizaciones enviadas esperando respuesta
     */
    public function scopeEsperandoRespuesta($query)
    {
        return $query->where('estado_envio', 'Enviado')
                     ->whereNull('fecha_respuesta');
    }

    /**
     * Cotizaciones con respuesta
     */
    public function scopeConRespuesta($query)
    {
        return $query->whereNotNull('fecha_respuesta');
    }

    // --- MÉTODOS DE NEGOCIO ---

    /**
     * Genera la URL del Magic Link
     */
    public function generarMagicLink(): string
    {
        // Usar NGROK_URL si está disponible (para desarrollo) o APP_URL en producción
        $baseUrl = config('app.ngrok_url') ?: config('app.url');
        return $baseUrl . route('portal.cotizacion', ['token' => $this->token_unico], false);
    }

    /**
     * Marca como enviado
     */
    public function marcarEnviado(): void
    {
        $this->update([
            'estado_envio' => 'Enviado',
            'fecha_envio' => now(),
        ]);
    }

    /**
     * Marca como fallido el envío
     */
    public function marcarEnvioFallido(): void
    {
        $this->update(['estado_envio' => 'Fallido']);
    }

    /**
     * Verifica si puede responder
     */
    public function puedeResponder(): bool
    {
        // Solo puede responder si fue enviado, no ha respondido y no ha vencido
        return $this->estado_envio === 'Enviado'
            && is_null($this->fecha_respuesta)
            && !$this->solicitud->haVencido();
    }

    /**
     * Registra la respuesta del proveedor
     */
    public function registrarRespuesta(): void
    {
        $this->update(['fecha_respuesta' => now()]);
    }

    /**
     * Registra rechazo del proveedor
     */
    public function registrarRechazo(string $motivo): void
    {
        $this->update([
            'fecha_respuesta' => now(),
            'motivo_rechazo' => $motivo,
        ]);
    }

    /**
     * Calcula el total cotizado por este proveedor
     */
    public function totalCotizado(): float
    {
        return $this->respuestas->sum(function ($respuesta) {
            return $respuesta->precio_unitario * $respuesta->cantidad_disponible;
        });
    }

    /**
     * Verifica si cotizó todos los productos
     */
    public function cotizoTodosLosProductos(): bool
    {
        $productosRequeridos = $this->solicitud->detalles->pluck('producto_id')->toArray();
        $productosCotizados = $this->respuestas->pluck('producto_id')->toArray();
        
        return count(array_diff($productosRequeridos, $productosCotizados)) === 0;
    }

    /**
     * Encuentra cotización por token
     */
    public static function buscarPorToken(string $token): ?self
    {
        return self::where('token_unico', $token)->first();
    }
}
