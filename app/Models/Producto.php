<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\Auditoria; // Importante para CU-27

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    
    // CAMPOS MAESTROS (CU-25 Paso 2)
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'marca',
        'unidadMedida',
        'categoriaProductoID',
        'estadoProductoID',
        'proveedor_habitual_id', // <--- Requerido por CU-25 y CU-26
        // Se elimina la referencia a 'stockActual' del fillable
    ];

    // --- RELACIONES ---

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoriaProductoID', 'id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoProducto::class, 'estadoProductoID', 'id');
    }

    // Relación con Proveedor Habitual (CU-25 Paso 5)
    public function proveedorHabitual(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_habitual_id', 'id');
    }

    public function precios(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'productoID', 'id');
    }

    // Relación con STOCK (Donde están las cantidades)
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'productoID', 'id');
    }

    // --- LÓGICA DE NEGOCIO (EXPERTO) ---

    /**
     * Accessor: Calcula el stock total sumando depósitos.
     * Reemplaza a la columna obsoleta 'stockActual'.
     */
    public function getStockTotalAttribute(): int
    {
        // Se suma la cantidad disponible de todos los depósitos/registros de Stock.
        return (int) $this->stocks()->sum('cantidad_disponible');
    }

    /**
     * Verifica si hay stock suficiente a nivel global (sumando depósitos).
     * (CU-05 Excepción 3a - Delegación del cálculo a 'stocks')
     */
    public function tieneStock(float $cantidadRequerida): bool
    {
        // Usamos el accessor para mantener la lógica centralizada
        return $this->stock_total >= $cantidadRequerida; 
    }

    /**
     * CU-27: Dar de baja (Cambio de estado + Auditoría)
     */
    public function darDeBaja(string $motivo, int $userId): bool
    {
        return DB::transaction(function () use ($motivo, $userId) {
            $estadoInactivo = EstadoProducto::where('nombre', 'Inactivo')->firstOrFail();
            
            if ($this->estadoProductoID == $estadoInactivo->id) {
                 throw new \Exception("El producto ya se encuentra inactivo. (CU-27 Excepción 4a)");
            }

            $datosAnteriores = $this->toArray();
            $this->update(['estadoProductoID' => $estadoInactivo->id]);

            // Registrar Auditoría (CU-27 Paso 10)
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
}