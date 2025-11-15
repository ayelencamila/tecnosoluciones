<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar las provincias del sistema
 *
 * Esta clase representa las provincias argentinas y maneja
 * las relaciones con localidades y direcciones.
 *
 * @property int $provinciaID Identificador único de la provincia
 * @property string $nombre Nombre de la provincia
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 */
class Provincia extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'provincias';

    /**
     * Clave primaria personalizada
     *
     * @var string
     */
    protected $primaryKey = 'provinciaID';

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
        'nombre',
    ];

    /**
     * Obtiene todas las localidades que pertenecen a esta provincia
     */
    public function localidades(): HasMany
    {
        return $this->hasMany(Localidad::class, 'provinciaID', 'provinciaID');
    }
}
