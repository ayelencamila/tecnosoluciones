<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    /**
     * La clave primaria asociada con la tabla.
     */
    protected $primaryKey = 'venta_id';

    /**
     * Los atributos que no son asignables masivamente.
     */
    protected $guarded = ['venta_id'];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'fecha_vencimiento' => 'date', // <--- AGREGAR ESTA LÍNEA
        'subtotal' => 'decimal:2',
        'total_descuentos' => 'decimal:2',
        'total' => 'decimal:2',
        'anulada' => 'boolean',
    ];

    /**
     * Relación: Una venta pertenece a un Cliente.
     * FK: 'clienteID' (en ventas) -> PK: 'clienteID' (en clientes)
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'clienteID', 'clienteID');
    }

    /**
     * Relación: Una venta fue registrada por un Usuario (vendedor).
     * FK: 'user_id' (en ventas) -> PK: 'id' (en users)
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación: Una venta tiene muchos items (detalles).
     * FK: 'venta_id' (en detalle_ventas) -> PK: 'venta_id' (en ventas)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id', 'venta_id');
    }

    /**
     * Relación N:M: Descuentos aplicados al TOTAL de la venta.
     */
    public function descuentos(): BelongsToMany
    {
        return $this->belongsToMany(
            Descuento::class,       // Modelo destino
            'descuento_venta',      // Tabla pivote
            'venta_id',             // FK en pivote de este modelo
            'descuento_id'          // FK en pivote del modelo destino
        )->withPivot('monto_aplicado'); // Traer el monto "congelado"
    }
}
