<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modelo COMPROBANTES (CU-32)
 * 
 * Representa comprobantes polimórficos del sistema.
 * 
 * Lineamientos aplicados:
 * - Larman: Relación Polimórfica (bajo acoplamiento entre módulos)
 * - Kendall: Motivo obligatorio para anulaciones
 * - Elmasri: Self-reference para historial de reemisiones
 * 
 * @property int $comprobante_id
 * @property string $tipo_entidad
 * @property int $entidad_id
 * @property int $usuario_id
 * @property string $tipo_comprobante
 * @property string $numero_correlativo
 * @property \Carbon\Carbon $fecha_emision
 * @property string|null $ruta_archivo
 * @property string $estado
 * @property string|null $motivo_estado
 * @property int|null $original_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Model $entidad
 * @property-read User $usuario
 * @property-read Comprobante|null $original
 * @property-read \Illuminate\Database\Eloquent\Collection<Comprobante> $reemisiones
 */
class Comprobante extends Model
{
    use HasFactory;

    protected $table = 'comprobantes';
    protected $primaryKey = 'comprobante_id';

    protected $fillable = [
        'tipo_entidad',
        'entidad_id',
        'usuario_id',
        'tipo_comprobante',
        'numero_correlativo',
        'fecha_emision',
        'ruta_archivo',
        'estado',
        'motivo_estado',
        'original_id',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
    ];

    /**
     * Estados válidos según Kendall
     */
    const ESTADO_EMITIDO = 'EMITIDO';
    const ESTADO_ANULADO = 'ANULADO';
    const ESTADO_REEMPLAZADO = 'REEMPLAZADO';

    /**
     * Tipos de comprobante
     */
    const TIPO_TICKET = 'TICKET';
    const TIPO_FACTURA_A = 'FACTURA_A';
    const TIPO_FACTURA_B = 'FACTURA_B';
    const TIPO_ORDEN_SERVICIO = 'ORDEN_SERVICIO';
    const TIPO_RECIBO_PAGO = 'RECIBO_PAGO';
    const TIPO_ORDEN_COMPRA = 'ORDEN_COMPRA';

    // --- RELACIONES ---

    /**
     * Relación polimórfica (Larman: BCE)
     * Puede ser Venta, Reparacion, OrdenCompra, etc.
     */
    public function entidad(): MorphTo
    {
        return $this->morphTo('entidad', 'tipo_entidad', 'entidad_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Self-reference: Comprobante original (Elmasri: trazabilidad)
     */
    public function original(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'original_id', 'comprobante_id');
    }

    /**
     * Reemisiones de este comprobante
     */
    public function reemisiones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comprobante::class, 'original_id', 'comprobante_id');
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Genera el siguiente número correlativo según tipo
     * 
     * CRÍTICO: Usa lockForUpdate() para prevenir condición de carrera
     * cuando múltiples usuarios emiten comprobantes simultáneamente
     */
    public static function generarNumeroCorrelativo(string $tipoComprobante, string $prefijo = null): string
    {
        return \DB::transaction(function () use ($tipoComprobante, $prefijo) {
            if (!$prefijo) {
                $prefijo = match($tipoComprobante) {
                    self::TIPO_TICKET, self::TIPO_FACTURA_A, self::TIPO_FACTURA_B => 'V',
                    self::TIPO_ORDEN_SERVICIO => 'R',
                    self::TIPO_RECIBO_PAGO => 'P',
                    self::TIPO_ORDEN_COMPRA => 'OC',
                    default => 'C',
                };
            }

            // PESSIMISTIC LOCKING: Bloquea la fila hasta que termine la transacción
            // Esto previene que dos usuarios lean el mismo "último número"
            $ultimoComprobante = self::where('tipo_comprobante', $tipoComprobante)
                ->where('numero_correlativo', 'like', $prefijo . '%')
                ->lockForUpdate()  // <--- CRÍTICO: Bloqueo pesimista
                ->latest('comprobante_id')
                ->first();

            if ($ultimoComprobante) {
                // Extraer número del formato "V0001-000045"
                preg_match('/(\d+)-(\d+)/', $ultimoComprobante->numero_correlativo, $matches);
                $numero = isset($matches[2]) ? ((int) $matches[2]) + 1 : 1;
            } else {
                $numero = 1;
            }

            $puntoVenta = '0001';
            return sprintf('%s%s-%06d', $prefijo, $puntoVenta, $numero);
        });
    }

    /**
     * Anula el comprobante (Kendall: motivo obligatorio)
     */
    public function anular(string $motivo): void
    {
        if ($this->estado === self::ESTADO_ANULADO) {
            throw new \Exception('El comprobante ya está anulado');
        }

        if (empty($motivo)) {
            throw new \InvalidArgumentException('El motivo de anulación es obligatorio (Kendall)');
        }

        $this->update([
            'estado' => self::ESTADO_ANULADO,
            'motivo_estado' => $motivo,
        ]);
    }

    /**
     * Reemite el comprobante (genera uno nuevo vinculado)
     */
    public function reemitir(int $usuarioId): self
    {
        if ($this->estado === self::ESTADO_ANULADO) {
            throw new \Exception('No se puede reemitir un comprobante anulado');
        }

        // Crear nuevo comprobante vinculado al original
        $nuevoComprobante = self::create([
            'tipo_entidad' => $this->tipo_entidad,
            'entidad_id' => $this->entidad_id,
            'usuario_id' => $usuarioId,
            'tipo_comprobante' => $this->tipo_comprobante,
            'numero_correlativo' => self::generarNumeroCorrelativo($this->tipo_comprobante),
            'fecha_emision' => now(),
            'estado' => self::ESTADO_EMITIDO,
            'original_id' => $this->comprobante_id,
        ]);

        // Marcar el actual como reemplazado
        $this->update([
            'estado' => self::ESTADO_REEMPLAZADO,
            'motivo_estado' => 'Reemitido como ' . $nuevoComprobante->numero_correlativo,
        ]);

        return $nuevoComprobante;
    }

    /**
     * Verifica si es una reemisión
     */
    public function esReemision(): bool
    {
        return !is_null($this->original_id);
    }
}
