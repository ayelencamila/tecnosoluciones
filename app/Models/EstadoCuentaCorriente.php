<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar los estados de cuenta corriente del sistema
 * 
 * Esta clase representa los diferentes estados financieros en los que puede
 * encontrarse una cuenta corriente (ej: Al día, Vencida, En mora, etc.)
 * 
 * @package App\Models
 * @property int $estadoCuentaCorrienteID Identificador único del estado de cuenta corriente
 * @property string $nombreEstado Nombre del estado de la cuenta corriente
 * @property string|null $descripcion Descripción detallada del estado financiero
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CuentaCorriente> $cuentasCorriente Cuentas corrientes que tienen este estado
 */
class EstadoCuentaCorriente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * 
     * @var string
     */
    protected $table = 'estados_cuenta_corriente';
    
    /**
     * Clave primaria personalizada
     * 
     * @var string
     */
    protected $primaryKey = 'estadoCuentaCorrienteID';
    
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
        'nombreEstado',
        'descripcion',
    ];

    /**
     * Obtiene todas las cuentas corrientes que tienen este estado
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cuentasCorrientes(): HasMany
    {
        return $this->hasMany(CuentaCorriente::class, 'estadoCuentaCorrienteID', 'estadoCuentaCorrienteID');
    }
}
