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
        'proveedor_habitual_id', // <-- CORRECCIÓN 1: Añadido
    ];

    /**
     * Conversión de tipos de datos
     */
    protected $casts = [
        // (Correcto, 'stockActual' y 'stockMinimo' ya no están aquí)
    ];

    // --- RELACIONES ---

    /**
     * Un Producto pertenece a una Categoría
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoriaProductoID', 'id');
    }

    /**
     * Un Producto tiene un Estado
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoProducto::class, 'estadoProductoID', 'id');
    }

    /**
     * Un Producto tiene muchos Precios (historial)
     */
    public function precios(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'productoID', 'id');
    }

    /**
     * Un Producto tiene existencias en uno o muchos depósitos.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'productoID', 'id');
    }

    /**
     * Un Producto tiene un Proveedor Habitual (opcional)
     * (CU-25, CU-26, CU-28)
     */
    public function proveedorHabitual(): BelongsTo // <-- CORRECCIÓN 2: Añadido
    {
        // Asume que el modelo Proveedor existe o lo crearás pronto
        return $this->belongsTo(Proveedor::class, 'proveedor_habitual_id', 'id');
    }

    // --- CORRECCIÓN 3: ELIMINADO EL BLOQUE DUPLICADO DE 'stocks()' ---
    // (El bloque que estaba aquí, de la línea 72 a la 81, se eliminó)
    

    // --- LÓGICA DE PRECIOS (EXPERTO LARMAN) ---

    /**
     * Busca el precio vigente para un tipo de cliente específico.
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
        return DB::transaction(function () use ($motivo, $userId) {
            
            $estadoInactivo = EstadoProducto::where('nombre', 'Inactivo')->first();
            if (!$estadoInactivo) {
                throw new \Exception("No se encontró el estado 'Inactivo' para productos.");
            }

            if ($this->estadoProductoID == $estadoInactivo->id) {
                 throw new \Exception("El producto ya se encuentra inactivo.");
            }

            $datosAnteriores = $this->toArray();

            // 1. Cambiar estado
            $this->update(['estadoProductoID' => $estadoInactivo->id]);

            // 2. Registrar Auditoría
            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $this->id,
                'accion' => 'BAJA_PRODUCTO', 
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($this->fresh()->toArray()),
                'motivo' => $motivo,
                'usuarioID' => $userId,
                'detalles' => "Producto dado de baja: {$this->nombre}"
            ]);

            return true;
        });
    }

    // (El resto de la lógica que tenías está perfecta)
}