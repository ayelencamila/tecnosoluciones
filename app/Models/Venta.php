<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Venta extends Model
{
    use HasFactory;

    protected $primaryKey = 'venta_id';

    protected $fillable = [
        'clienteID',
        'user_id',
        'estado_venta_id',
        'medio_pago_id', 
        // -----------------------
        'numero_comprobante',
        'fecha_venta',
        'subtotal',
        'total_descuentos',
        'total',
        'motivo_anulacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'subtotal' => 'decimal:2',
        'total_descuentos' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // RELACIONES NUEVAS
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoVenta::class, 'estado_venta_id', 'estadoVentaID');
    }

    public function medioPago(): BelongsTo
    {
        return $this->belongsTo(MedioPago::class, 'medio_pago_id', 'medioPagoID');
    }

    // Relaciones existentes (Cliente, Vendedor, Detalles...)
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'clienteID', 'clienteID');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id', 'venta_id');
    }

    public function descuentos(): BelongsToMany
    {
        return $this->belongsToMany(Descuento::class, 'descuento_venta', 'venta_id', 'descuento_id')
                    ->withPivot('monto_aplicado');
    }

    public function pagos()
    {
        return $this->belongsToMany(Pago::class, 'pago_venta_imputacion', 'venta_id', 'pago_id')
                    ->withPivot('monto_imputado')
                    ->withTimestamps();
    }

    public function getSaldoPendienteAttribute(): float
    {
        $pagado = $this->pagos()->sum('pago_venta_imputacion.monto_imputado');
        return max(0, $this->total - $pagado);
    }

    /**
     * Comprobantes asociados a esta venta (Larman: Relación Polimórfica)
     */
    public function comprobantes(): MorphMany
    {
        return $this->morphMany(Comprobante::class, 'entidad', 'tipo_entidad', 'entidad_id');
    }
}