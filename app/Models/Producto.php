<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\Auditoria;

class Producto extends Model
{
    use HasFactory, SoftDeletes; 

    protected $table = 'productos';
    
    // CAMPOS MAESTROS (CU-25 Paso 2)
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'marca_id',
        'unidad_medida_id',
        'categoriaProductoID',
        'estadoProductoID',
        'proveedor_habitual_id',
    ];
    protected $dates = ['deleted_at']; 

    // --- RELACIONES ---

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoriaProductoID', 'id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoProducto::class, 'estadoProductoID', 'id');
    }

    public function proveedorHabitual(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_habitual_id', 'id');
    }

    public function precios(): HasMany
    {
        return $this->hasMany(PrecioProducto::class, 'productoID', 'id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'productoID', 'id');
    }

    public function usosEnReparaciones(): HasMany
    {
        return $this->hasMany(DetalleReparacion::class, 'producto_id'); 
    }

    /**
     * Detalles de órdenes de compra que incluyen este producto (CU-23)
     */
    public function detallesOrdenCompra(): HasMany
    {
        return $this->hasMany(DetalleOrdenCompra::class, 'producto_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    // --- LÓGICA DE NEGOCIO (EXPERTO) ---

    public function getStockTotalAttribute(): int
    {
        return (int) $this->stocks()->sum('cantidad_disponible');
    }

    public function tieneStock(float $cantidadRequerida): bool
    {
        return $this->stock_total >= $cantidadRequerida; 
    }

    /**
     * CU-27: Dar de baja (Cambio de estado LÓGICO).
     */
    public function darDeBaja(string $motivo, int $userId): bool
    {
        return DB::transaction(function () use ($motivo, $userId) {
            // Buscamos el estado 'Inactivo' o similar según el seeder
            $estadoInactivo = EstadoProducto::where('nombre', 'Inactivo')
                                            ->orWhere('nombre', 'Descontinuado') 
                                            ->firstOrFail();
            
            if ($this->estadoProductoID == $estadoInactivo->id) {
                 throw new \Exception("El producto ya se encuentra inactivo.");
            }

            $datosAnteriores = $this->toArray();
            $this->update(['estadoProductoID' => $estadoInactivo->id]);

            // Registrar Auditoría
            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $this->id,
                'accion' => 'BAJA_PRODUCTO_LOGICA', 
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($this->fresh()->toArray()),
                'motivo' => $motivo,
                'usuarioID' => $userId,
                'detalles' => "Producto pasado a estado Inactivo: {$this->nombre}"
            ]);
            
            return true;
        });
    }
}