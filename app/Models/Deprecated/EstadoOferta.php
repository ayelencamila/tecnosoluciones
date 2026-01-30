<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo ESTADOS_OFERTA (Paramétrica - CU-21)
 * 
 * Estados del flujo de evaluación de ofertas de compra.
 * 
 * Lineamientos aplicados:
 * - Elmasri 3FN: Tabla paramétrica (evita hardcodeo de strings)
 * - Kendall: Estados del flujo de compras
 * 
 * Estados predefinidos:
 * - Pendiente: Registrada, pendiente de evaluación
 * - Pre-aprobada: Revisada, lista para consideración
 * - Elegida: Seleccionada para generar OC
 * - Procesada: Ya convertida en OC
 * - Rechazada: Descartada
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool $activo
 * @property int $orden
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<OfertaCompra> $ofertas
 */
class EstadoOferta extends Model
{
    use HasFactory;

    protected $table = 'estados_oferta';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // --- CONSTANTES para facilitar queries ---
    const PENDIENTE = 'Pendiente';
    const PRE_APROBADA = 'Pre-aprobada';
    const ELEGIDA = 'Elegida';
    const PROCESADA = 'Procesada';
    const RECHAZADA = 'Rechazada';

    // --- RELACIONES ---

    /**
     * Ofertas con este estado
     */
    public function ofertas(): HasMany
    {
        return $this->hasMany(OfertaCompra::class, 'estado_id');
    }

    // --- MÉTODOS ESTÁTICOS (Helpers) ---

    /**
     * Obtiene el ID de un estado por nombre
     * Con caché para evitar queries repetitivos
     */
    public static function idPorNombre(string $nombre): ?int
    {
        static $cache = [];
        
        if (!isset($cache[$nombre])) {
            $cache[$nombre] = static::where('nombre', $nombre)->value('id');
        }
        
        return $cache[$nombre];
    }

    /**
     * Scopes para filtrar estados activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
