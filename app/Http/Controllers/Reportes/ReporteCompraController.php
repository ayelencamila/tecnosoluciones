<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\EstadoOrdenCompra;
use App\Models\Auditoria;
use App\Exports\CompraExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReporteCompraController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auditoría
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tablaAfectada' => 'reportes',
            'valorNuevo' => 'Reporte de Compras',
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Análisis de compras y proveedores'
        ]);

        // 2. Filtros
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $proveedorId = $request->input('proveedor_id');
        $estadoId = $request->input('estado_id');

        // 3. Query Base
        $query = OrdenCompra::with(['proveedor', 'estado', 'usuario'])
            ->whereBetween('fecha_emision', [
                Carbon::parse($fechaDesde)->startOfDay(), 
                Carbon::parse($fechaHasta)->endOfDay()
            ]);

        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }
        if ($estadoId) {
            $query->where('estado_id', $estadoId);
        }

        // 4. Datos Tabla
        $ordenes = (clone $query)->latest('fecha_emision')->paginate(15)->withQueryString();

        // 5. KPIs
        $totalGastado = (clone $query)->sum('total_final');
        $cantidadOrdenes = (clone $query)->count();
        $ticketPromedio = $cantidadOrdenes > 0 ? $totalGastado / $cantidadOrdenes : 0;

        // 6. Gráfico 1: Gasto por Proveedor (Top 5)
        $gastoPorProveedor = (clone $query)
            ->join('proveedores', 'ordenes_compra.proveedor_id', '=', 'proveedores.id')
            ->select('proveedores.razon_social as nombre', DB::raw('SUM(ordenes_compra.total_final) as total'))
            ->groupBy('proveedores.razon_social')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $graficoProveedores = [
            'labels' => $gastoPorProveedor->pluck('nombre'),
            'datasets' => [
                [
                    'label' => 'Total Comprado ($)',
                    'data' => $gastoPorProveedor->pluck('total'),
                    'backgroundColor' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                ]
            ]
        ];

        // 7. Gráfico 2: Estado de Órdenes
        $porEstado = (clone $query)
            ->join('estados_orden_compra', 'ordenes_compra.estado_id', '=', 'estados_orden_compra.id')
            ->select('estados_orden_compra.nombre as nombre', DB::raw('count(*) as total'))
            ->groupBy('estados_orden_compra.nombre')
            ->get();

        $graficoEstados = [
            'labels' => $porEstado->pluck('nombre'),
            'datasets' => [
                [
                    'data' => $porEstado->pluck('total'),
                    'backgroundColor' => ['#6366F1', '#14B8A6', '#F43F5E', '#EAB308'],
                ]
            ]
        ];

        // Obtener proveedor seleccionado si existe (para mostrar en el buscador)
        $proveedorSeleccionado = $proveedorId 
            ? Proveedor::find($proveedorId, ['id', 'razon_social', 'cuit']) 
            : null;

        return Inertia::render('Reportes/Compras/Index', [
            'ordenes' => $ordenes,
            'kpis' => [
                'total_gastado' => $totalGastado,
                'cantidad_ordenes' => $cantidadOrdenes,
                'promedio_orden' => round($ticketPromedio, 2),
            ],
            'graficos' => [
                'proveedores' => $graficoProveedores,
                'estados' => $graficoEstados
            ],
            'filters' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'proveedor_id' => $proveedorId,
                'estado_id' => $estadoId,
            ],
            'proveedorSeleccionado' => $proveedorSeleccionado,
            'estados' => EstadoOrdenCompra::orderBy('orden')->get(['id', 'nombre']),
        ]);
    }

    public function exportar(Request $request)
    {
        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tablaAfectada' => 'reportes',
            'usuarioID' => Auth::id(),
            'motivo' => 'Descarga Excel Compras'
        ]);

        return Excel::download(new CompraExport($request->all()), 'reporte_compras.xlsx');
    }
}