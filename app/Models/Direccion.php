<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar las direcciones del sistema
 *
 * Esta clase representa las direcciones físicas completas de los clientes,
 * incluyendo calle, altura, piso/depto, barrio y código postal.
 * Cada dirección pertenece a una localidad específica.
 *
 * @property int $direccionID Identificador único de la dirección
 * @property string $calle Nombre de la calle
 * @property string $altura Número de la altura/numeración
 * @property string|null $pisoDepto Piso y/o departamento (opcional)
 * @property string|null $barrio Nombre del barrio
 * @property string|null $codigoPostal Código postal de la dirección
 * @property int $localidadID Identificador de la localidad
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * @property-read \App\Models\Localidad $localidad Localidad a la que pertenece la dirección
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes Clientes que tienen esta dirección
 */
class Direccion extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'direcciones';

    /**
     * Clave primaria personalizada
     *
     * @var string
     */
    protected $primaryKey = 'direccionID';

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
        'calle',
        'altura',
        'pisoDepto',
        'barrio',
        'codigoPostal',
        'localidadID',
    ];

    /**
     * Obtiene la localidad a la que pertenece esta dirección
     */
    public function localidad(): BelongsTo
    {
        return $this->belongsTo(Localidad::class, 'localidadID', 'localidadID');
    }

    /**
     * Obtiene todos los clientes que tienen esta dirección
     *
     * Una dirección puede estar asociada a múltiples clientes.
     * Útil para familias, empresas con múltiples contactos, etc.
     */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'direccionID', 'direccionID');
    }

    /**
     * Genera la dirección completa formateada como string
     *
     * @return string Dirección completa formateada
     */
    public function getDireccionCompletaAttribute(): string
    {
        $direccion = "{$this->calle} {$this->altura}";

        if ($this->pisoDepto) {
            $direccion .= ", {$this->pisoDepto}";
        }

        if ($this->barrio) {
            $direccion .= ", {$this->barrio}";
        }

        if ($this->codigoPostal) {
            $direccion .= " (CP: {$this->codigoPostal})";
        }

        return $direccion;
    }

    /**
     * Obtiene la dirección completa incluyendo localidad y provincia
     *
     * @return string Dirección completa con ubicación geográfica
     */
    public function getDireccionCompletaConUbicacionAttribute(): string
    {
        $direccion = $this->direccion_completa;

        if ($this->localidad) {
            $direccion .= ", {$this->localidad->nombre}";

            if ($this->localidad->provincia) {
                $direccion .= ", {$this->localidad->provincia->nombre}";
            }
        }

        return $direccion;
    }
}
