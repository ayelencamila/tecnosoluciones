<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar las localidades del sistema
 *
 * Esta clase representa las localidades/ciudades que pertenecen
 * a una provincia específica y pueden tener múltiples direcciones.
 *
 * @property int $localidadID Identificador único de la localidad
 * @property string $nombre Nombre de la localidad
 * @property int $provinciaID Identificador de la provincia a la que pertenece
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * @property-read \App\Models\Provincia $provincia Provincia a la que pertenece
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Direccion> $direcciones Direcciones en esta localidad
 */
class Localidad extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'localidades';

    /**
     * Clave primaria personalizada
     *
     * @var string
     */
    protected $primaryKey = 'localidadID';

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
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'localidadID';
    }

    /**
     * Atributos que se pueden asignar de forma masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'provinciaID',
    ];

    /**
     * Obtiene la provincia a la que pertenece esta localidad
     */
    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'provinciaID', 'provinciaID');
    }

    /**
     * Obtiene todas las direcciones que pertenecen a esta localidad
     */
    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class, 'localidadID', 'localidadID');
    }
}
