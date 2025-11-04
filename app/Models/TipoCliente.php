<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar los tipos de cliente del sistema
 * 
 * Esta clase representa las categorías de clientes disponibles.
 * Solo se permiten dos tipos: Mayorista y Minorista.
 * 
 * @package App\Models
 * @property int $tipoClienteID Identificador único del tipo de cliente
 * @property string $nombreTipo Nombre del tipo de cliente (Mayorista|Minorista)
 * @property string|null $descripcion Descripción detallada del tipo de cliente
 * @property bool $activo Indica si el tipo de cliente está activo
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes Clientes que pertenecen a este tipo
 */
class TipoCliente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * 
     * @var string
     */
    protected $table = 'tipos_cliente';
    
    /**
     * Clave primaria personalizada
     * 
     * @var string
     */
    protected $primaryKey = 'tipoClienteID';
    
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
        'nombreTipo',
        'descripcion',
        'activo',
    ];

    /**
     * Tipos de cliente válidos en el sistema
     * 
     * @var array<string>
     */
    public const TIPOS_VALIDOS = [
        'Mayorista',
        'Minorista',
    ];

    /**
     * Eventos del modelo para validaciones
     */
    protected static function boot()
    {
        parent::boot();
        
        // Validar antes de crear
        static::creating(function ($tipoCliente) {
            if (!in_array($tipoCliente->nombreTipo, self::TIPOS_VALIDOS)) {
                throw new \InvalidArgumentException(
                    'El tipo de cliente debe ser: ' . implode(' o ', self::TIPOS_VALIDOS)
                );
            }
        });
        
        // Validar antes de actualizar
        static::updating(function ($tipoCliente) {
            if (!in_array($tipoCliente->nombreTipo, self::TIPOS_VALIDOS)) {
                throw new \InvalidArgumentException(
                    'El tipo de cliente debe ser: ' . implode(' o ', self::TIPOS_VALIDOS)
                );
            }
        });
    }

    /**
     * Verifica si el tipo de cliente es mayorista
     * 
     * @return bool
     */
    public function esMayorista(): bool
    {
        return $this->nombreTipo === 'Mayorista';
    }

    /**
     * Verifica si el tipo de cliente es minorista
     * 
     * @return bool
     */
    public function esMinorista(): bool
    {
        return $this->nombreTipo === 'Minorista';
    }

    /**
     * Obtiene todos los clientes que pertenecen a este tipo de cliente
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'tipoClienteID', 'tipoClienteID');
    }
}