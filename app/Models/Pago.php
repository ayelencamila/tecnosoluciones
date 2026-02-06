<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';
    protected $primaryKey = 'pagoID'; 

    protected $fillable = [
        'clienteID',
        'user_id',
        'monto',
        'medioPagoID', 
        'fecha_pago',
        'numero_recibo',
        'observaciones',
        'anulado'
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2',
        'anulado' => 'boolean',
    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'clienteID', 'clienteID');
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function medioPago(): BelongsTo
    {
        return $this->belongsTo(MedioPago::class, 'medioPagoID', 'medioPagoID');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pago) {
            if (empty($pago->numero_recibo)) {
                $pago->numero_recibo = 'REC-' . date('Ymd-His') . '-' . substr(microtime(), 2, 6);
            }
            if (empty($pago->fecha_pago)) {
                $pago->fecha_pago = now();
            }
        });
    }

    public function ventasImputadas()
    {
        return $this->belongsToMany(Venta::class, 'pago_venta_imputacion', 'pago_id', 'venta_id')
                    ->withPivot('monto_imputado')
                    ->withTimestamps();
    }

    public function reparacionesImputadas()
    {
        return $this->belongsToMany(Reparacion::class, 'pago_reparacion_imputacion', 'pago_id', 'reparacion_id')
                    ->withPivot('monto_imputado')
                    ->withTimestamps();
    }

    /**
     * Total imputado a ventas + reparaciones
     */
    public function getTotalImputadoAttribute(): float
    {
        $ventasImputado = $this->ventasImputadas->sum('pivot.monto_imputado');
        $reparacionesImputado = $this->reparacionesImputadas->sum('pivot.monto_imputado');
        return (float) ($ventasImputado + $reparacionesImputado);
    }
}