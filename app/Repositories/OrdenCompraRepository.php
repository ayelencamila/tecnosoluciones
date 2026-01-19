<?php

namespace App\Repositories;

use App\Models\OrdenCompra;
use App\Models\EstadoOrdenCompra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repositorio para Órdenes de Compra
 * 
 * Implementa Patrón Repository (Sommerville)
 * Responsabilidad: Encapsular queries complejas y lógica de acceso a datos
 * Beneficios: 
 * - Separación de responsabilidades
 * - Facilita testing (mockeable)
 * - Centraliza lógica de consultas
 */
class OrdenCompraRepository
{
    /**
     * Filtra órdenes de compra según criterios múltiples (CU-24)
     * 
     * @param array $criterios Filtros: numero_oc, proveedor_id, estado_id, fecha_desde, fecha_hasta
     * @param int $perPage Registros por página
     * @return LengthAwarePaginator
     */
    public function filtrar(array $criterios, int $perPage = 15): LengthAwarePaginator
    {
        $query = OrdenCompra::with([
            'proveedor:id,razon_social,cuit',
            'oferta.solicitud:id,codigo_solicitud',
            'estado:id,nombre,color',
            'usuario:id,name',
        ])
            ->orderByDesc('fecha_emision');

        // Filtro por número de OC (búsqueda parcial)
        if (!empty($criterios['numero_oc'])) {
            $query->where('numero_oc', 'like', '%' . $criterios['numero_oc'] . '%');
        }

        // Filtro por proveedor
        if (!empty($criterios['proveedor_id'])) {
            $query->where('proveedor_id', $criterios['proveedor_id']);
        }

        // Filtro por estado
        if (!empty($criterios['estado_id'])) {
            $query->where('estado_id', $criterios['estado_id']);
        }

        // Filtro por rango de fechas
        if (!empty($criterios['fecha_desde'])) {
            $query->whereDate('fecha_emision', '>=', $criterios['fecha_desde']);
        }
        if (!empty($criterios['fecha_hasta'])) {
            $query->whereDate('fecha_emision', '<=', $criterios['fecha_hasta']);
        }

        // Filtro por producto (búsqueda en detalles)
        if (!empty($criterios['producto_id'])) {
            $query->whereHas('detalles', function ($q) use ($criterios) {
                $q->where('producto_id', $criterios['producto_id']);
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtiene estadísticas rápidas de órdenes
     * 
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        $estadoEnviada = EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::ENVIADA);
        $estadoConfirmada = EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::CONFIRMADA);
        $estadoFallido = EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::ENVIO_FALLIDO);

        return [
            'total' => OrdenCompra::count(),
            'enviadas' => OrdenCompra::where('estado_id', $estadoEnviada)->count(),
            'confirmadas' => OrdenCompra::where('estado_id', $estadoConfirmada)->count(),
            'fallidas' => OrdenCompra::where('estado_id', $estadoFallido)->count(),
            'total_monto' => OrdenCompra::sum('total_final'),
        ];
    }

    /**
     * Obtiene estados activos para filtros
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerEstadosActivos()
    {
        return EstadoOrdenCompra::where('activo', true)
            ->orderBy('orden')
            ->get(['id', 'nombre', 'color']);
    }

    /**
     * Busca órdenes pendientes de recepción para un proveedor
     * 
     * @param int $proveedorId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pendientesRecepcion(int $proveedorId)
    {
        $estadosPermitidos = [
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::CONFIRMADA),
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL),
        ];

        return OrdenCompra::with(['detalles.producto'])
            ->where('proveedor_id', $proveedorId)
            ->whereIn('estado_id', $estadosPermitidos)
            ->orderBy('fecha_emision', 'desc')
            ->get();
    }
}
