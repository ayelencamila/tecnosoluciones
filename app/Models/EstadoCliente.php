<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar los estados de cliente del sistema
 * 
 * Esta clase representa los diferentes estados en los que puede
 * encontrarse un cliente (ej: Activo, Inactivo, Suspendido, etc.)
 * 
 * @package App\Models
 * @property int $estadoClienteID Identificador único del estado de cliente
 * @property string $nombreEstado Nombre del estado del cliente
 * @property string|null $descripcion Descripción detallada del estado
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes Clientes que tienen este estado
 */
class EstadoCliente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * 
     * @var string
     */
    protected $table = 'estados_cliente';
    
    /**
     * Clave primaria personalizada
     * 
     * @var string
     */
    protected $primaryKey = 'estadoClienteID';
    
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
     * Obtiene todos los clientes que tienen este estado
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'estadoClienteID', 'estadoClienteID');
    }
}
