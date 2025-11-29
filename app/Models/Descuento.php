<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Descuento extends Model
{
    use HasFactory;

    protected $primaryKey = 'descuento_id';
    protected $guarded = ['descuento_id'];

    protected $casts = [
        'valor' => 'decimal:2',
        'activo' => 'boolean',
        'valido_desde' => 'date',
        'valido_hasta' => 'date',
    ];

    // --- RELACIONES NUEVAS (Configurables) ---
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoDescuento::class, 'tipo_descuento_id');
    }

    public function aplicabilidad(): BelongsTo
    {
        return $this->belongsTo(AplicabilidadDescuento::class, 'aplicabilidad_descuento_id');
    }

    // --- RELACIONES EXISTENTES ---
    public function ventas(): BelongsToMany
    {
        return $this->belongsToMany(Venta::class, 'descuento_venta', 'descuento_id', 'venta_id')
                    ->withPivot('monto_aplicado');
    }

    public function detalleVentas(): BelongsToMany
    {
        return $this->belongsToMany(DetalleVenta::class, 'descuento_detalle_venta', 'descuento_id', 'detalle_venta_id')
                    ->withPivot('monto_aplicado_item');
    }

    /**
     * Lógica de dominio actualizada: usa el código del TipoDescuento
     */
    public function calcularMonto(float $montoBase): float
    {
        // Asumimos que la tabla tipos_descuento tiene columna 'codigo' (PORCENTAJE / FIJO)
        // Usamos strterm para seguridad o carga previa
        if ($this->tipo->codigo === 'PORCENTAJE') {
            return ($montoBase * (float) $this->valor) / 100;
        }

        if ($this->tipo->codigo === 'FIJO') {
            return min((float) $this->valor, $montoBase);
        }

        return 0;
    }
}