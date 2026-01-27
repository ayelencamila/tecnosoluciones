<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Modelo RECEPCIONES_MERCADERIA (CU-23)
 * 
 * Representa el registro de recepción de mercadería vinculada a una OC.
 * 
 * Lineamientos aplicados:
 * - Larman: Trazabilidad completa del proceso de compras
 * - Kendall: Numeración correlativa única
 * - Elmasri 3FN: Relaciones normalizadas
 * 
 * @property int $id
 * @property string $numero_recepcion
 * @property int $orden_compra_id
 * @property int $user_id
 * @property \Carbon\Carbon $fecha_recepcion
 * @property string|null $observaciones
 * @property string $tipo ('parcial' o 'total')
 */
class RecepcionMercaderia extends Model
{
    use HasFactory;

    protected $table = 'recepciones_mercaderia';

    protected $fillable = [
        'numero_recepcion',
        'orden_compra_id',
        'user_id',
        'fecha_recepcion',
        'observaciones',
        'tipo',
    ];

    protected $casts = [
        'fecha_recepcion' => 'datetime',
    ];

    // --- CONSTANTES ---
    public const TIPO_PARCIAL = 'parcial';
    public const TIPO_TOTAL = 'total';

    // --- RELACIONES ---

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleRecepcion::class, 'recepcion_id');
    }

    // --- MÉTODOS DE NEGOCIO ---

    /**
     * Genera el siguiente número de recepción correlativo
     * 
     * Formato: REC-YYYYMMDD-XXX
     */
    public static function generarNumeroRecepcion(): string
    {
        return DB::transaction(function () {
            $hoy = now()->format('Ymd');
            
            $countHoy = self::whereDate('fecha_recepcion', now()->toDateString())
                ->lockForUpdate()
                ->count();
            
            $secuencia = str_pad($countHoy + 1, 3, '0', STR_PAD_LEFT);
            
            return "REC-{$hoy}-{$secuencia}";
        });
    }

    /**
     * Calcula el total de items recibidos en esta recepción
     */
    public function getTotalItemsRecibidosAttribute(): int
    {
        return $this->detalles->sum('cantidad_recibida');
    }
}
