<?php

namespace App\Repositories;

use App\Models\OfertaCompra;
use App\Models\EstadoOferta;
use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para Ofertas de Compra
 * 
 * Implementa Patrón Repository (Sommerville)
 * Beneficios:
 * - Encapsula queries complejas
 * - Facilita testing con mocks
 * - Centraliza lógica de filtrado
 */
class OfertaCompraRepository
{
    /**
     * Filtra ofertas con búsqueda por código o proveedor (CU-21)
     * 
     * @param array $criterios Filtros: search, estado_id, proveedor_id, fecha_desde, fecha_hasta
     * @param int $perPage Registros por página
     * @return LengthAwarePaginator
     */
    public function filtrar(array $criterios, int $perPage = 10): LengthAwarePaginator
    {
        $query = OfertaCompra::with([
            'proveedor:id,razon_social,cuit',
            'estado:id,nombre,color',
            'user:id,name',
            'solicitud:id,codigo_solicitud',
        ])
            ->latest('fecha_recepcion');

        // Búsqueda general (código o proveedor)
        if (!empty($criterios['search'])) {
            $term = $criterios['search'];
            $query->where(function($q) use ($term) {
                $q->where('codigo_oferta', 'like', "%{$term}%")
                  ->orWhereHas('proveedor', fn($p) => $p->where('razon_social', 'like', "%{$term}%"));
            });
        }

        // Filtro por estado
        if (!empty($criterios['estado_id'])) {
            $query->where('estado_id', $criterios['estado_id']);
        }

        // Filtro por proveedor específico
        if (!empty($criterios['proveedor_id'])) {
            $query->where('proveedor_id', $criterios['proveedor_id']);
        }

        // Filtro por rango de fechas de recepción
        if (!empty($criterios['fecha_desde'])) {
            $query->whereDate('fecha_recepcion', '>=', $criterios['fecha_desde']);
        }
        if (!empty($criterios['fecha_hasta'])) {
            $query->whereDate('fecha_recepcion', '<=', $criterios['fecha_hasta']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtiene productos con múltiples ofertas pendientes para comparación (CU-21 Paso 10)
     * 
     * @param int $limite Máximo de productos a retornar
     * @return Collection
     */
    public function productosConMultiplesOfertas(int $limite = 10): Collection
    {
        $estadosPendientes = EstadoOferta::whereIn('nombre', ['Pendiente', 'Pre-aprobada'])
            ->pluck('id');

        return Producto::select('productos.id', 'productos.nombre', 'productos.codigo')
            ->join('detalle_ofertas_compra', 'productos.id', '=', 'detalle_ofertas_compra.producto_id')
            ->join('ofertas_compra', 'detalle_ofertas_compra.oferta_id', '=', 'ofertas_compra.id')
            ->whereIn('ofertas_compra.estado_id', $estadosPendientes)
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->havingRaw('COUNT(DISTINCT ofertas_compra.id) > 1')
            ->selectRaw('COUNT(DISTINCT ofertas_compra.id) as ofertas_count')
            ->orderBy('ofertas_count', 'desc')
            ->limit($limite)
            ->get();
    }

    /**
     * Obtiene ofertas para comparación de un producto específico (CU-21 Paso 10)
     * Ordenadas por precio y plazo según especificación
     * 
     * @param int $productoId
     * @return Collection
     */
    public function ofertasParaComparar(int $productoId): Collection
    {
        $estadosValidos = EstadoOferta::whereIn('nombre', ['Pendiente', 'Pre-aprobada', 'Elegida'])
            ->pluck('id');

        $ofertas = OfertaCompra::with(['proveedor', 'detalles.producto', 'estado'])
            ->whereIn('estado_id', $estadosValidos)
            ->whereHas('detalles', function ($q) use ($productoId) {
                $q->where('producto_id', $productoId);
            })
            ->get();

        // CU-21 Paso 10: Ordenar por precio y plazo
        return $ofertas->map(function ($oferta) use ($productoId) {
            $detalle = $oferta->detalles->firstWhere('producto_id', $productoId);
            $oferta->precio_producto = $detalle ? $detalle->precio_unitario : PHP_INT_MAX;
            $oferta->plazo_producto = $detalle ? ($detalle->disponibilidad_inmediata ? 0 : $detalle->dias_entrega) : PHP_INT_MAX;
            $oferta->score = $oferta->calcularScore();
            return $oferta;
        })->sortBy([
            ['precio_producto', 'asc'],
            ['plazo_producto', 'asc'],
        ])->values();
    }

    /**
     * Obtiene ofertas elegidas pendientes de procesar (generar OC)
     * 
     * @return Collection
     */
    public function elegidas(): Collection
    {
        return OfertaCompra::with(['proveedor', 'solicitud'])
            ->where('estado_id', EstadoOferta::idPorNombre(EstadoOferta::ELEGIDA))
            ->orderBy('fecha_recepcion', 'desc')
            ->get();
    }
}
