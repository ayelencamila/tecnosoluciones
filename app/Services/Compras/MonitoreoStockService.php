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
use App\Models\Configuracion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Servicio: Monitoreo Automático de Stock (CU-20)
 * 
 * Responsabilidades:
 * - Detectar productos bajo punto de reorden o alta rotación
 * - Generar solicitudes de cotización
 * - Enviar automáticamente a proveedores (cuando automatización activa)
 * 
 * Flujo según configuración:
 * - AUTOMATIZACIÓN ACTIVADA: Detecta → Genera → Envía automáticamente
 * - AUTOMATIZACIÓN DESACTIVADA: Solo detecta (usuario crea manualmente)
 * 
 * Lineamientos:
 * - Larman: Creator (crea solicitudes)
 * - Kendall: Automatización de procesos de compras
 */
class MonitoreoStockService
{
    const UMBRAL_ALTA_ROTACION = 10;

    /**
     * Detecta todos los productos bajo stock mínimo
     */
    public function detectarProductosBajoStock(): Collection
    {
        return Stock::with(['producto.proveedorHabitual', 'deposito'])
            ->whereColumn('cantidad_disponible', '<', 'stock_minimo')
            ->where('stock_minimo', '>', 0)
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
     * Detecta productos con alta rotación (muchas ventas en el último mes)
     */
    public function detectarProductosAltaRotacion(int $diasAnalizar = 30, ?int $umbral = null): Collection
    {
        $umbral = $umbral ?? self::UMBRAL_ALTA_ROTACION;
        $fechaDesde = Carbon::now()->subDays($diasAnalizar);

        $ventasPorProducto = DetalleVenta::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->whereHas('venta', function ($query) use ($fechaDesde) {
                $query->where('fecha_venta', '>=', $fechaDesde)
                      ->whereHas('estado', fn($q) => $q->where('nombreEstado', '!=', 'Anulada'));
            })
            ->groupBy('producto_id')
            ->having('total_vendido', '>=', $umbral)
            ->get();

        if ($ventasPorProducto->isEmpty()) {
            return collect();
        }

        $productosIds = $ventasPorProducto->pluck('producto_id')->toArray();

        return Stock::with(['producto.proveedorHabitual', 'deposito'])
            ->whereIn('productoID', $productosIds)
            ->get()
            ->map(function ($stock) use ($ventasPorProducto) {
                $ventasInfo = $ventasPorProducto->firstWhere('producto_id', $stock->productoID);
                $totalVendido = $ventasInfo ? $ventasInfo->total_vendido : 0;
                
                $ventasDiarias = $totalVendido / 30;
                $diasCobertura = $ventasDiarias > 0 ? round($stock->cantidad_disponible / $ventasDiarias, 1) : 999;
                
                // Solo alertar si la cobertura es menor a 14 días
                if ($diasCobertura > 14) return null;

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
            ->filter()
            ->values();
    }

    /**
     * Detecta TODOS los productos que necesitan reposición
     * (stock bajo + alta rotación sin duplicados)
     */
    public function detectarProductosNecesitanReposicion(): Collection
    {
        $bajoStock = $this->detectarProductosBajoStock();
        $altaRotacion = $this->detectarProductosAltaRotacion();

        $productosIds = $bajoStock->pluck('producto_id')->toArray();
        $altaRotacionSinDuplicados = $altaRotacion->filter(function ($item) use ($productosIds) {
            return !in_array($item['producto_id'], $productosIds);
        });

        return $bajoStock->concat($altaRotacionSinDuplicados);
    }

    /**
     * Agrupa productos por proveedor habitual
     */
    public function agruparPorProveedor(Collection $productosBajoStock): Collection
    {
        return $productosBajoStock->groupBy(function ($item) {
            return $item['proveedor_habitual']?->id ?? 'sin_proveedor';
        });
    }

    /**
     * MÉTODO PRINCIPAL: Ejecuta el monitoreo completo
     * 
     * Si automatización ACTIVADA: Genera solicitudes en estado "Abierta" y las ENVÍA
     * Si automatización DESACTIVADA: No hace nada (el usuario crea manualmente)
     * 
     * @return array Resultado de la operación
     */
    public function ejecutarMonitoreoAutomatico(): array
    {
        // Verificar si la automatización está activada
        $automatizacionActiva = Configuracion::get('compras_generacion_automatica', 'false') === 'true';
        
        if (!$automatizacionActiva) {
            return [
                'ejecutado' => false,
                'mensaje' => 'Automatización desactivada. Las solicitudes deben crearse manualmente.',
            ];
        }

        // Obtener configuración de días de vencimiento
        $diasVencimiento = (int) Configuracion::get('compras_dias_vencimiento', 7);

        // Ejecutar generación con envío automático
        return $this->generarSolicitudesAutomaticas(
            userId: null, // Sistema
            diasVencimiento: $diasVencimiento,
            incluirAltaRotacion: true,
            enviarAutomaticamente: true // CLAVE: envía automáticamente
        );
    }

    /**
     * Genera solicitudes de cotización
     * 
     * @param int|null $userId Usuario que ejecuta (null si es sistema)
     * @param int $diasVencimiento Días hasta vencimiento
     * @param bool $incluirAltaRotacion Incluir productos de alta rotación
     * @param bool $enviarAutomaticamente Si true, envía inmediatamente a proveedores
     * @return array Resultado de la operación
     */
    public function generarSolicitudesAutomaticas(
        ?int $userId = null, 
        int $diasVencimiento = 7, 
        bool $incluirAltaRotacion = true,
        bool $enviarAutomaticamente = false
    ): array {
        $productosNecesitan = $incluirAltaRotacion 
            ? $this->detectarProductosNecesitanReposicion()
            : $this->detectarProductosBajoStock();
        
        if ($productosNecesitan->isEmpty()) {
            return [
                'solicitudes_creadas' => 0, 
                'productos_procesados' => 0,
                'enviadas' => 0,
                'mensaje' => 'No hay productos que necesiten reposición',
            ];
        }

        $porProveedor = $this->agruparPorProveedor($productosNecesitan);
        $solicitudesCreadas = [];
        $solicitudesEnviadas = 0;
        $errores = [];
        
        // Excluir productos que ya están en curso
        $productosYaSolicitados = $this->obtenerProductosEnSolicitudesAbiertas();

        DB::beginTransaction();
        try {
            foreach ($porProveedor as $proveedorId => $productos) {
                $productosNuevos = $productos->filter(fn($item) => !in_array($item['producto_id'], $productosYaSolicitados));

                if ($productosNuevos->isEmpty()) continue;

                if ($proveedorId === 'sin_proveedor') {
                    $solicitud = $this->crearSolicitudSinProveedorHabitual($productosNuevos, $userId, $diasVencimiento, $enviarAutomaticamente);
                } else {
                    $solicitud = $this->crearSolicitudParaProveedor($proveedorId, $productosNuevos, $userId, $diasVencimiento, $enviarAutomaticamente);
                }

                if ($solicitud) {
                    $solicitudesCreadas[] = $solicitud;
                    
                    // Si envío automático está activado, enviar ahora
                    if ($enviarAutomaticamente) {
                        try {
                            $solicitudService = app(SolicitudCotizacionService::class);
                            $resultado = $solicitudService->enviarSolicitudAProveedores($solicitud, 'inteligente');
                            if ($resultado['enviados'] > 0) {
                                $solicitudesEnviadas++;
                            }
                        } catch (\Exception $e) {
                            Log::warning("Error enviando solicitud {$solicitud->id}: " . $e->getMessage());
                            $errores[] = "Solicitud {$solicitud->codigo_solicitud}: " . $e->getMessage();
                        }
                    }
                }
            }
            DB::commit();

            $mensaje = $enviarAutomaticamente 
                ? "Se crearon " . count($solicitudesCreadas) . " solicitud(es) y se enviaron {$solicitudesEnviadas} a proveedores."
                : "Se crearon " . count($solicitudesCreadas) . " borrador(es). Requieren aprobación manual.";

            return [
                'solicitudes_creadas' => count($solicitudesCreadas),
                'productos_procesados' => $productosNecesitan->count(),
                'enviadas' => $solicitudesEnviadas,
                'solicitudes' => $solicitudesCreadas,
                'errores' => $errores,
                'mensaje' => count($solicitudesCreadas) > 0 ? $mensaje : 'Sin novedades.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error monitoreo: ' . $e->getMessage());
            return ['solicitudes_creadas' => 0, 'enviadas' => 0, 'errores' => [$e->getMessage()], 'mensaje' => 'Error crítico'];
        }
    }

    protected function obtenerProductosEnSolicitudesAbiertas(): array
    {
        return DetalleSolicitudCotizacion::whereHas('solicitud', function ($query) {
            $query->whereHas('estado', function ($q) {
                // Bloquea si ya está Abierta, Enviada O Pendiente de Revisión
                $q->whereIn('nombre', ['Abierta', 'Enviada', 'Pendiente de Revisión']);
            });
        })->pluck('producto_id')->toArray();
    }

    /**
     * Crea solicitud para un proveedor específico
     * 
     * @param bool $enviarAutomaticamente Si true, usa estado "Abierta" para envío inmediato
     */
    protected function crearSolicitudParaProveedor(
        int $proveedorId, 
        Collection $productos, 
        ?int $userId, 
        int $diasVencimiento,
        bool $enviarAutomaticamente = false
    ): ?SolicitudCotizacion {
        // Estado según modo de operación
        if ($enviarAutomaticamente) {
            // Modo automático: Abierta (lista para enviar)
            $estado = EstadoSolicitud::where('nombre', 'Abierta')->first();
            $observaciones = 'Generada y enviada automáticamente por monitoreo de stock.';
        } else {
            // Modo manual: Pendiente de Revisión (requiere aprobación)
            $estado = EstadoSolicitud::where('nombre', 'Pendiente de Revisión')->first();
            if (!$estado) $estado = EstadoSolicitud::where('nombre', 'Abierta')->first();
            $observaciones = 'Generada automáticamente. Esperando aprobación del usuario.';
        }

        $solicitud = SolicitudCotizacion::create([
            'codigo_solicitud' => SolicitudCotizacion::generarCodigoSolicitud(),
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays($diasVencimiento),
            'estado_id' => $estado->id,
            'user_id' => $userId,
            'observaciones' => $observaciones,
        ]);

        foreach ($productos as $item) {
            DetalleSolicitudCotizacion::create([
                'solicitud_id' => $solicitud->id,
                'producto_id' => $item['producto_id'],
                'cantidad_sugerida' => max($item['faltante'], $item['stock_minimo']),
                'observaciones' => "Stock: {$item['cantidad_actual']} (Min: {$item['stock_minimo']})",
            ]);
        }

        // Crear la relación con el proveedor
        CotizacionProveedor::create([
            'solicitud_id' => $solicitud->id,
            'proveedor_id' => $proveedorId,
            'estado_envio' => 'Pendiente',
        ]);

        return $solicitud;
    }

    /**
     * Crea solicitud para productos sin proveedor habitual (se envía a todos los activos)
     */
    protected function crearSolicitudSinProveedorHabitual(
        Collection $productos, 
        ?int $userId, 
        int $diasVencimiento,
        bool $enviarAutomaticamente = false
    ): ?SolicitudCotizacion {
        // Estado según modo de operación
        if ($enviarAutomaticamente) {
            $estado = EstadoSolicitud::where('nombre', 'Abierta')->first();
            $observaciones = 'Automática (múltiples proveedores). Enviada automáticamente.';
        } else {
            $estado = EstadoSolicitud::where('nombre', 'Pendiente de Revisión')->first();
            if (!$estado) $estado = EstadoSolicitud::where('nombre', 'Abierta')->first();
            $observaciones = 'Automática (sin proveedor definido). Esperando asignación/aprobación.';
        }

        // Buscar proveedores activos
        $proveedores = Proveedor::whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))->get();
        if ($proveedores->isEmpty()) return null;

        $solicitud = SolicitudCotizacion::create([
            'codigo_solicitud' => SolicitudCotizacion::generarCodigoSolicitud(),
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays($diasVencimiento),
            'estado_id' => $estado->id,
            'user_id' => $userId,
            'observaciones' => $observaciones,
        ]);

        foreach ($productos as $item) {
            DetalleSolicitudCotizacion::create([
                'solicitud_id' => $solicitud->id,
                'producto_id' => $item['producto_id'],
                'cantidad_sugerida' => max($item['faltante'], $item['stock_minimo']),
                'observaciones' => "Stock: {$item['cantidad_actual']}",
            ]);
        }

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
     * Obtiene resumen de stock para dashboard
     */
    public function obtenerResumenStock(): array
    {
        $bajoStock = Stock::whereColumn('cantidad_disponible', '<', 'stock_minimo')
            ->where('stock_minimo', '>', 0)
            ->count();

        $sinStock = Stock::where('cantidad_disponible', '<=', 0)
            ->count();

        $pendientesRevision = SolicitudCotizacion::whereHas('estado', function ($q) {
            $q->where('nombre', 'Pendiente de Revisión');
        })->count();

        return [
            'bajo_stock' => $bajoStock,
            'sin_stock' => $sinStock,
            'pendientes_revision' => $pendientesRevision,
        ];
    }

    public function productoBajoStock(int $productoId): bool {
        return Stock::where('productoID', $productoId)
            ->whereColumn('cantidad_disponible', '<', 'stock_minimo')
            ->where('stock_minimo', '>', 0)->exists();
    }
}