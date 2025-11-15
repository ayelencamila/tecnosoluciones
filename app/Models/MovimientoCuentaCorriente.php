<?php

// app/Models/MovimientoCuentaCorriente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCuentaCorriente extends Model
{
    use HasFactory;

    protected $table = 'movimientos_cuenta_corriente';

    protected $primaryKey = 'movimientoCCID';

    protected $fillable = [
        'cuentaCorrienteID',
        'tipoMovimiento',
        'descripcion',
        'monto',
        'fechaEmision',
        'fechaVencimiento',
        'saldoAlMomento',
        'referenciaID',
        'referenciaTabla',
        'observaciones',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldoAlMomento' => 'decimal:2',
        'fechaEmision' => 'date',
        'fechaVencimiento' => 'date',
    ];

    /**
     * Relación: Un movimiento pertenece a una cuenta corriente.
     */
    public function cuentaCorriente(): BelongsTo
    {
        return $this->belongsTo(CuentaCorriente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');
    }

    // Decision a tomar: Puedo agregar relaciones polimórficas si se requiere conectar directamente a la referencia
    // public function referencia()
    // {
    //     return $this->morphTo('referencia');
    // }
}
