<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Descuento extends Model
{
    use HasFactory;

    /**
     * La clave primaria asociada con la tabla.
     */
    protected $primaryKey = 'descuento_id';

    /**
     * Los atributos que no son asignables masivamente.
     */
    protected $guarded = ['descuento_id'];

    protected $casts = [
        'valor' => 'decimal:2',
        'activo' => 'boolean',
        'valido_desde' => 'date',
        'valido_hasta' => 'date',
    ];

    /**
     * Relación N:M: Ventas donde este descuento se aplicó al TOTAL.
     */
    public function ventas(): BelongsToMany
    {
        return $this->belongsToMany(
            Venta::class,
            'descuento_venta',
            'descuento_id',
            'venta_id'
        )->withPivot('monto_aplicado');
    }

    /**
     * Relación N:M: Items donde este descuento se aplicó.
     */
    public function detalleVentas(): BelongsToMany
    {
        return $this->belongsToMany(
            DetalleVenta::class,
            'descuento_detalle_venta',
            'descuento_id',
            'detalle_venta_id'
        )->withPivot('monto_aplicado_item');
    }

    /**
     * Lógica de dominio (Information Expert)
     * Calcula el monto a descontar basado en un subtotal.
     */
    public function calcularMonto(float $montoBase): float
    {
        if ($this->tipo === 'porcentaje') {
            return ($montoBase * (float) $this->valor) / 100;
        }

        if ($this->tipo === 'monto_fijo') {
            // El descuento no puede ser mayor que el monto base
            return min((float) $this->valor, $montoBase);
        }

        return 0;
    }
}
