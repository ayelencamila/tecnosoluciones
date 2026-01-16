<?php

namespace App\Services\Compras;

use App\Models\Stock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SolicitudCotizacion;
use App\Models\DetalleSolicitudCotizacion;
use App\Models\CotizacionProveedor;
use App\Models\EstadoSolicitud;
use App\Models\DetalleVenta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Servicio: Monitoreo Automático de Stock (CU-20)
 * 
 * Responsabilidades:
 * - Detectar productos bajo punto de reorden
 * - Detectar productos con alta rotación (muchas ventas)
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
     * Umbral de ventas para considerar alta rotación (configurable)
     * Si un producto vendió más de X unidades en el último mes
     */
    const UMBRAL_ALTA_ROTACION = 10;
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
                    'motivo' => 'stock_bajo',
                ];
            });
    }

    /**
     * Detecta productos con alta rotación en el último mes
     * Productos que vendieron mucho y podrían necesitar reposición preventiva
     * 
     * @param int $diasAnalizar Días hacia atrás para analizar ventas (default: 30)
     * @param int|null $umbral Cantidad mínima de ventas para considerar alta rotación
     * @return Collection Productos con alta rotación
     */
    public function detectarProductosAltaRotacion(int $diasAnalizar = 30, ?int $umbral = null): Collection
    {
        $umbral = $umbral ?? self::UMBRAL_ALTA_ROTACION;
        $fechaDesde = Carbon::now()->subDays($diasAnalizar);

        // Obtener productos con muchas ventas en el período
        $ventasPorProducto = DetalleVenta::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->whereHas('venta', function ($query) use ($fechaDesde) {
                $query->where('fecha', '>=', $fechaDesde)
                      ->whereHas('estado', fn($q) => $q->where('nombre', '!=', 'Anulada'));
            })
            ->groupBy('producto_id')
            ->having('total_vendido', '>=', $umbral)
            ->get();

        if ($ventasPorProducto->isEmpty()) {
            return collect();
        }

        $productosIds = $ventasPorProducto->pluck('producto_id')->toArray();

        // Obtener información de stock de estos productos
        return Stock::with(['producto.proveedorHabitual', 'deposito'])
            ->whereIn('productoID', $productosIds)
            ->get()
            ->map(function ($stock) use ($ventasPorProducto) {
                $ventasInfo = $ventasPorProducto->firstWhere('producto_id', $stock->productoID);
                $totalVendido = $ventasInfo ? $ventasInfo->total_vendido : 0;
                
                // Calcular cobertura: cuántos días de stock quedan basado en ritmo de ventas
                $ventasDiarias = $totalVendido / 30;
                $diasCobertura = $ventasDiarias > 0 ? round($stock->cantidad_disponible / $ventasDiarias, 1) : 999;
                
                // Solo incluir si tiene menos de 14 días de cobertura
                if ($diasCobertura > 14) {
                    return null;
                }

                return [
                    'producto_id' => $stock->productoID,
                    'producto' => $stock->producto,
                    'deposito' => $stock->deposito,
                    'cantidad_actual' => $stock->cantidad_disponible,
                    'stock_minimo' => $stock->stock_minimo,
                    'faltante' => max(0, ($stock->stock_minimo ?: $totalVendido) - $stock->cantidad_disponible),
                    'proveedor_habitual' => $stock->producto->proveedorHabitual,
                    'motivo' => 'alta_rotacion',
                    'ventas_mes' => $totalVendido,
                    'dias_cobertura' => $diasCobertura,
                ];
            })
            ->filter() // Eliminar nulls
            ->values();
    }

    /**
     * Detecta todos los productos que necesitan reposición
     * Combina: stock bajo + alta rotación (sin duplicados)
     * 
     * @return Collection Todos los productos que necesitan atención
     */
    public function detectarProductosNecesitanReposicion(): Collection
    {
        $bajoStock = $this->detectarProductosBajoStock();
        $altaRotacion = $this->detectarProductosAltaRotacion();

        // Combinar sin duplicados (priorizando el de stock bajo si existe en ambos)
        $productosIds = $bajoStock->pluck('producto_id')->toArray();
        
        $altaRotacionSinDuplicados = $altaRotacion->filter(function ($item) use ($productosIds) {
            return !in_array($item['producto_id'], $productosIds);
        });

        return $bajoStock->concat($altaRotacionSinDuplicados);
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
     * Genera solicitudes de cotización automáticas para productos que necesitan reposición
     * Incluye: stock bajo + productos de alta rotación
     * 
     * @param int|null $userId Usuario que ejecuta (null si es proceso automático)
     * @param int $diasVencimiento Días hasta vencimiento de la solicitud
     * @param bool $incluirAltaRotacion Incluir productos de alta rotación
     * @return array Resultado con solicitudes creadas y errores
     */
    public function generarSolicitudesAutomaticas(
        ?int $userId = null, 
        int $diasVencimiento = 7,
        bool $incluirAltaRotacion = true
    ): array {
        // Detectar todos los productos que necesitan atención
        $productosNecesitan = $incluirAltaRotacion 
            ? $this->detectarProductosNecesitanReposicion()
            : $this->detectarProductosBajoStock();
        
        if ($productosNecesitan->isEmpty()) {
            return [
                'solicitudes_creadas' => 0,
                'productos_procesados' => 0,
                'productos_bajo_stock' => 0,
                'productos_alta_rotacion' => 0,
                'errores' => [],
                'mensaje' => 'No hay productos que necesiten reposición',
            ];
        }

        // Conteo por motivo
        $bajoStock = $productosNecesitan->where('motivo', 'stock_bajo')->count();
        $altaRotacion = $productosNecesitan->where('motivo', 'alta_rotacion')->count();

        $porProveedor = $this->agruparPorProveedor($productosNecesitan);
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
                'productos_procesados' => $productosNecesitan->count(),
                'productos_bajo_stock' => $bajoStock,
                'productos_alta_rotacion' => $altaRotacion,
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
                'productos_bajo_stock' => 0,
                'productos_alta_rotacion' => 0,
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
