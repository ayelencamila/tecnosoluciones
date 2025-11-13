<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB; // Para transacciones

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    // Asumo que tu PK es 'id' (autoincremental), ya que tu controlador usa $producto->id
    // Si tu PK es 'productoID', debes agregar: protected $primaryKey = 'productoID';

    /**
     * Atributos que se pueden asignar masivamente (StoreProductoRequest)
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'marca',
        'unidadMedida',
        'categoriaProductoID',
        'estadoProductoID',
        'stockActual',
        'stockMinimo',
    ];

    /**
     * Conversión de tipos de datos
     */
    protected $casts = [
        'stockActual' => 'integer',
        'stockMinimo' => 'integer',
    ];

    // --- RELACIONES ---

    /**
     * Un Producto pertenece a una Categoría
     */
    public function categoria(): BelongsTo
    {
        // Tu controlador usa 'categoriaProductoID' como FK
        return $this->belongsTo(CategoriaProducto::class, 'categoriaProductoID', 'id');
    }

    /**
     * Un Producto tiene un Estado
     */
    public function estado(): BelongsTo
    {
        // Tu controlador usa 'estadoProductoID' como FK
        return $this->belongsTo(EstadoProducto::class, 'estadoProductoID', 'id');
    }

    /**
     * Un Producto tiene muchos Precios (historial)
     */
    public function precios(): HasMany
    {
        // Tu controlador usa 'productoID' como FK en la tabla precios
        return $this->hasMany(PrecioProducto::class, 'productoID', 'id');
    }

    // --- LÓGICA DE STOCK (DEPÓSITO ÚNICO - CORREGIDO) ---

    /**
     * Verifica si hay stock suficiente en la columna 'stockActual'.
     * (CU-05 Excepción 3a)
     */
    public function tieneStock(float $cantidadRequerida): bool
    {
        // CORREGIDO: Lee 'stockActual' del modelo
        return $this->stockActual >= $cantidadRequerida;
    }

    /**
     * Accessor para asegurar que 'stockActual' se trate como float/int.
     * Tu controlador lo usa en el 'create'
     */
    public function getStockActualAttribute($value): float
    {
        // CORREGIDO: Devuelve el valor de la columna 'stockActual'
        // (Si tu BD tiene 'stockActual' como columna, 'attributes['stockActual']' es más seguro)
        return (float) ($this->attributes['stockActual'] ?? 0);
    }

    // --- LÓGICA DE PRECIOS (EXPERTO LARMAN) ---

    /**
     * Busca el precio vigente para un tipo de cliente específico.
     * (Lógica movida desde el ProductoController@update)
     */
    public function precioVigente(int $tipoClienteID): ?PrecioProducto
    {
        return $this->precios()
            ->where('tipoClienteID', $tipoClienteID)
            ->whereNull('fechaHasta') // Solo los vigentes
            ->where('fechaDesde', '<=', now())
            ->orderBy('fechaDesde', 'desc') // El más reciente
            ->first();
    }

    // --- LÓGICA DE ESTADO (EXPERTO LARMAN) ---

    /**
     * Da de baja un producto (CU-27).
     * Cambia el estado a "Inactivo" de forma atómica y audita.
     */
    public function darDeBaja(string $motivo, int $userId): bool
    {
        // Usamos una transacción por si falla la auditoría (aunque es poco probable)
        return DB::transaction(function () use ($motivo, $userId) {
            
            $estadoInactivo = EstadoProducto::where('nombre', 'Inactivo')->first();
            if (!$estadoInactivo) {
                // Si no existe el estado 'Inactivo', creamos uno o fallamos
                // $estadoInactivo = EstadoProducto::create(['nombre' => 'Inactivo']);
                throw new \Exception("No se encontró el estado 'Inactivo' para productos.");
            }

            if ($this->estadoProductoID == $estadoInactivo->id) {
                 throw new \Exception("El producto ya se encuentra inactivo.");
            }

            $datosAnteriores = $this->toArray();

            // 1. Cambiar estado
            $this->update(['estadoProductoID' => $estadoInactivo->id]);

            // 2. Registrar Auditoría (Como lo hacía tu controlador)
            // Asumo que tu modelo Auditoria tiene un método 'registrar'
            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $this->id,
                'accion' => 'BAJA_PRODUCTO', // (Deberías agregar esta constante a tu modelo Auditoria)
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($this->fresh()->toArray()),
                'motivo' => $motivo,
                'usuarioID' => $userId,
                'detalles' => "Producto dado de baja: {$this->nombre}"
            ]);

            return true;
        });
    }

    // (Tu controlador no tiene lógica de 'boot()' para Producto,
    // así que lo dejamos sin ella por ahora, ya que los Servicios manejan la auditoría)
}
