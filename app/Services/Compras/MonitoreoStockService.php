<?php

namespace App\Services\Compras;

use App\Models\Stock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SolicitudCotizacion;
use App\Models\DetalleSolicitudCotizacion;
use App\Models\CotizacionProveedor;
use App\Models\EstadoSolicitud;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio: Monitoreo Automático de Stock (CU-20)
 * 
 * Responsabilidades:
 * - Detectar productos bajo punto de reorden
 * - Generar solicitudes de cotización automáticas
 * - Agrupar productos por proveedor habitual
 * 
 * Lineamientos aplicados:
 * - Larman: Creator (crea solicitudes)
 * - Kendall: Automatización de procesos de negocio
 */
class MonitoreoStockService
{
    /**
     * Detecta todos los productos bajo stock mínimo
     * 
     * @return Collection Productos que necesitan reposición
     */
    public function detectarProductosBajoStock(): Collection
    {
        // Busca registros de stock donde cantidad < stock_minimo
        return Stock::with(['producto.proveedorHabitual', 'deposito'])
            ->whereColumn('cantidad_disponible', '<', 'stock_minimo')
            ->where('stock_minimo', '>', 0) // Solo si tiene mínimo configurado
            ->get()
            ->map(function ($stock) {
                return [
                    'producto_id' => $stock->productoID,
                    'producto' => $stock->producto,
                    'deposito' => $stock->deposito,
                    'cantidad_actual' => $stock->cantidad_disponible,
                    'stock_minimo' => $stock->stock_minimo,
                    'faltante' => $stock->stock_minimo - $stock->cantidad_disponible,
                    'proveedor_habitual' => $stock->producto->proveedorHabitual,
                ];
            });
    }

    /**
     * Agrupa productos bajo stock por proveedor habitual
     * 
     * @param Collection $productosBajoStock
     * @return Collection Productos agrupados por proveedor_id
     */
    public function agruparPorProveedor(Collection $productosBajoStock): Collection
    {
        return $productosBajoStock->groupBy(function ($item) {
            return $item['proveedor_habitual']?->id ?? 'sin_proveedor';
        });
    }

    /**
     * Genera solicitudes de cotización automáticas para productos bajo stock
     * 
     * @param int|null $userId Usuario que ejecuta (null si es proceso automático)
     * @param int $diasVencimiento Días hasta vencimiento de la solicitud
     * @return array Resultado con solicitudes creadas y errores
     */
    public function generarSolicitudesAutomaticas(
        ?int $userId = null, 
        int $diasVencimiento = 7
    ): array {
        $productosBajoStock = $this->detectarProductosBajoStock();
        
        if ($productosBajoStock->isEmpty()) {
            return [
                'solicitudes_creadas' => 0,
                'productos_procesados' => 0,
                'errores' => [],
                'mensaje' => 'No hay productos bajo stock mínimo',
            ];
        }

        $porProveedor = $this->agruparPorProveedor($productosBajoStock);
        $solicitudesCreadas = [];
        $errores = [];
        $productosYaSolicitados = $this->obtenerProductosEnSolicitudesAbiertas();

        DB::beginTransaction();
        
        try {
            foreach ($porProveedor as $proveedorId => $productos) {
                // Filtrar productos que ya tienen solicitud abierta
                $productosNuevos = $productos->filter(function ($item) use ($productosYaSolicitados) {
                    return !in_array($item['producto_id'], $productosYaSolicitados);
                });

                if ($productosNuevos->isEmpty()) {
                    continue;
                }

                // Si es "sin_proveedor", buscar proveedores que tengan estos productos
                if ($proveedorId === 'sin_proveedor') {
                    $solicitud = $this->crearSolicitudSinProveedorHabitual(
                        $productosNuevos, 
                        $userId, 
                        $diasVencimiento
                    );
                } else {
                    $solicitud = $this->crearSolicitudParaProveedor(
                        $proveedorId,
                        $productosNuevos, 
                        $userId, 
                        $diasVencimiento
                    );
                }

                if ($solicitud) {
                    $solicitudesCreadas[] = $solicitud;
                }
            }

            DB::commit();

            return [
                'solicitudes_creadas' => count($solicitudesCreadas),
                'productos_procesados' => $productosBajoStock->count(),
                'solicitudes' => $solicitudesCreadas,
                'errores' => $errores,
                'mensaje' => count($solicitudesCreadas) > 0 
                    ? 'Solicitudes generadas exitosamente'
                    : 'No se generaron solicitudes (productos ya tienen solicitudes abiertas)',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generando solicitudes automáticas: ' . $e->getMessage());
            
            return [
                'solicitudes_creadas' => 0,
                'productos_procesados' => 0,
                'errores' => [$e->getMessage()],
                'mensaje' => 'Error al generar solicitudes',
            ];
        }
    }

    /**
     * Obtiene IDs de productos que ya tienen solicitudes abiertas o enviadas
     */
    protected function obtenerProductosEnSolicitudesAbiertas(): array
    {
        return DetalleSolicitudCotizacion::whereHas('solicitud', function ($query) {
            $query->whereHas('estado', function ($q) {
                $q->whereIn('nombre', ['Abierta', 'Enviada']);
            });
        })->pluck('producto_id')->toArray();
    }

    /**
     * Crea una solicitud para un proveedor específico
     */
    protected function crearSolicitudParaProveedor(
        int $proveedorId,
        Collection $productos,
        ?int $userId,
        int $diasVencimiento
    ): ?SolicitudCotizacion {
        $estadoAbierta = EstadoSolicitud::abierta();
        
        if (!$estadoAbierta) {
            throw new \Exception('No se encontró el estado "Abierta" en estados_solicitud');
        }

        // Crear la solicitud
        $solicitud = SolicitudCotizacion::create([
            'codigo_solicitud' => SolicitudCotizacion::generarCodigoSolicitud(),
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays($diasVencimiento),
            'estado_id' => $estadoAbierta->id,
            'user_id' => $userId,
            'observaciones' => 'Solicitud generada automáticamente por monitoreo de stock',
        ]);

        // Crear detalles
        foreach ($productos as $item) {
            DetalleSolicitudCotizacion::create([
                'solicitud_id' => $solicitud->id,
                'producto_id' => $item['producto_id'],
                'cantidad_sugerida' => max($item['faltante'], $item['stock_minimo']),
                'observaciones' => "Stock actual: {$item['cantidad_actual']}, Mínimo: {$item['stock_minimo']}",
            ]);
        }

        // Crear cotización para el proveedor habitual
        CotizacionProveedor::create([
            'solicitud_id' => $solicitud->id,
            'proveedor_id' => $proveedorId,
            'estado_envio' => 'Pendiente',
        ]);

        return $solicitud;
    }

    /**
     * Crea solicitud para productos sin proveedor habitual
     * Invita a todos los proveedores activos
     */
    protected function crearSolicitudSinProveedorHabitual(
        Collection $productos,
        ?int $userId,
        int $diasVencimiento
    ): ?SolicitudCotizacion {
        $estadoAbierta = EstadoSolicitud::abierta();
        
        if (!$estadoAbierta) {
            throw new \Exception('No se encontró el estado "Abierta" en estados_solicitud');
        }

        // Buscar proveedores activos
        $proveedores = Proveedor::whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))
            ->get();

        if ($proveedores->isEmpty()) {
            return null;
        }

        // Crear la solicitud
        $solicitud = SolicitudCotizacion::create([
            'codigo_solicitud' => SolicitudCotizacion::generarCodigoSolicitud(),
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays($diasVencimiento),
            'estado_id' => $estadoAbierta->id,
            'user_id' => $userId,
            'observaciones' => 'Solicitud automática - Productos sin proveedor habitual asignado',
        ]);

        // Crear detalles
        foreach ($productos as $item) {
            DetalleSolicitudCotizacion::create([
                'solicitud_id' => $solicitud->id,
                'producto_id' => $item['producto_id'],
                'cantidad_sugerida' => max($item['faltante'], $item['stock_minimo']),
                'observaciones' => "Stock actual: {$item['cantidad_actual']}, Mínimo: {$item['stock_minimo']}",
            ]);
        }

        // Invitar a todos los proveedores activos
        foreach ($proveedores as $proveedor) {
            CotizacionProveedor::create([
                'solicitud_id' => $solicitud->id,
                'proveedor_id' => $proveedor->id,
                'estado_envio' => 'Pendiente',
            ]);
        }

        return $solicitud;
    }

    /**
     * Verifica si un producto específico está bajo stock
     */
    public function productoBajoStock(int $productoId): bool
    {
        return Stock::where('productoID', $productoId)
            ->whereColumn('cantidad_disponible', '<', 'stock_minimo')
            ->where('stock_minimo', '>', 0)
            ->exists();
    }

    /**
     * Obtiene resumen del estado de stock
     */
    public function obtenerResumenStock(): array
    {
        $totalProductos = Producto::count();
        $bajoStock = $this->detectarProductosBajoStock()->count();
        $sinStock = Stock::where('cantidad_disponible', '<=', 0)->count();
        $sinMinimoConfigurado = Stock::where('stock_minimo', 0)
            ->orWhereNull('stock_minimo')
            ->count();

        return [
            'total_productos' => $totalProductos,
            'bajo_stock' => $bajoStock,
            'sin_stock' => $sinStock,
            'sin_minimo_configurado' => $sinMinimoConfigurado,
            'porcentaje_bajo_stock' => $totalProductos > 0 
                ? round(($bajoStock / $totalProductos) * 100, 2) 
                : 0,
        ];
    }
}
