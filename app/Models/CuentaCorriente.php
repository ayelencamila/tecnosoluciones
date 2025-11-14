<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar las cuentas corrientes del sistema
 * 
 * Esta clase representa las cuentas corrientes de los clientes,
 * manejando saldos, límites de crédito, días de gracia y estados financieros.
 * 
 * @package App\Models
 * @property int $cuentaCorrienteID Identificador único de la cuenta corriente
 * @property float $saldo Saldo actual de la cuenta corriente
 * @property float $limiteCredito Límite de crédito otorgado al cliente
 * @property int $diasGracia Días de gracia para pagos después del vencimiento
 * @property int $estadoCuentaCorrienteID Identificador del estado de la cuenta corriente
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * 
 * @property-read \App\Models\EstadoCuentaCorriente $estadoCuentaCorriente Estado actual de la cuenta corriente
 * @property-read \App\Models\Cliente|null $cliente Cliente propietario de esta cuenta corriente
 */
class CuentaCorriente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * 
     * @var string
     */
    protected $table = 'cuentas_corriente';
    
    /**
     * Clave primaria personalizada
     * 
     * @var string
     */
    protected $primaryKey = 'cuentaCorrienteID';
    
    /**
     * Indica que la clave primaria es auto-incremental
     * 
     * @var bool
     */
    public $incrementing = true;
    
    /**
     * Tipo de la clave primaria
     * 
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Atributos que se pueden asignar de forma masiva
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'saldo',
        'limiteCredito',
        'diasGracia',
        'estadoCuentaCorrienteID',
    ];

    /**
     * Obtiene el estado actual de esta cuenta corriente
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function estadoCuentaCorriente(): BelongsTo
    {
        return $this->belongsTo(EstadoCuentaCorriente::class, 'estadoCuentaCorrienteID', 'estadoCuentaCorrienteID');
    }

    /**
     * Obtiene el cliente propietario de esta cuenta corriente
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');
    }
}