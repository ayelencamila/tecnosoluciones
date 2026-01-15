<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo ESTADOS_ORDEN_COMPRA (Tabla Paramétrica - CU-22)
 * 
 * Lineamientos aplicados:
 * - Elmasri 3FN: Tabla paramétrica para evitar hardcodeo de estados
 * - Kendall: Estados del ciclo de vida de una orden de compra
 * - Cache: Optimización para evitar queries repetitivas
 * 
 * Estados del flujo:
 * - Borrador: Orden creada pero no enviada
 * - Enviada: Orden enviada al proveedor (WhatsApp/Email)
 * - Envío Fallido: Error al enviar (requiere reintento manual)
 * - Confirmada: Proveedor confirmó la orden
 * - Recibida Parcial: Recepción parcial de productos (CU-23)
 * - Recibida Total: Todos los productos fueron recibidos
 * - Cancelada: Orden cancelada
 */
class EstadoOrdenCompra extends Model
{
    protected $table = 'estados_orden_compra';

    protected $fillable = ['nombre', 'descripcion', 'activo', 'orden'];

    // Constantes de estados (para evitar hardcodeo en el código)
    public const BORRADOR = 'Borrador';
    public const ENVIADA = 'Enviada';
    public const ENVIO_FALLIDO = 'Envío Fallido';
    public const CONFIRMADA = 'Confirmada';
    public const RECIBIDA_PARCIAL = 'Recibida Parcial';
    public const RECIBIDA_TOTAL = 'Recibida Total';
    public const CANCELADA = 'Cancelada';

    /**
     * Obtener ID de un estado por nombre (con cache)
     */
    public static function idPorNombre(string $nombre): ?int
    {
        return Cache::remember("estado_orden_compra_{$nombre}", 3600, function () use ($nombre) {
            return static::where('nombre', $nombre)->value('id');
        });
    }

    /**
     * Obtener todos los estados activos (para selects)
     */
    public static function activos()
    {
        return Cache::remember('estados_orden_compra_activos', 3600, function () {
            return static::where('activo', true)->orderBy('orden')->get();
        });
    }

    /**
     * Relación inversa: Órdenes con este estado
     */
    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'estado_id');
    }
}
