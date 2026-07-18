<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\CompraExport;
use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReporteCompraController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auditoría
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tabla_afectada' => 'reportes',
            'detalles' => 'Reporte de Compras',
            'usuarioID' => Auth::id(),
            'motivo' => 'Análisis de compras y proveedores',
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
                Carbon::parse($fechaHasta)->endOfDay(),
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
                ],
            ],
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
                ],
            ],
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
                'estados' => $graficoEstados,
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
        $formato = $request->input('formato', 'xlsx');
        $timestamp = now()->format('Ymd_His');

        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tabla_afectada' => 'reportes',
            'usuarioID' => Auth::id(),
            'motivo' => "Exportación {$formato} Compras",
        ]);

        switch ($formato) {
            case 'pdf':
                $data = $this->getDataForPdf($request);
                $pdf = Pdf::loadView('pdf.reportes.compras', $data)->setPaper('a4', 'landscape');

                return $pdf->download("reporte_compras_{$timestamp}.pdf");

            case 'csv':
                return Excel::download(
                    new CompraExport($request->all()),
                    "reporte_compras_{$timestamp}.csv",
                    \Maatwebsite\Excel\Excel::CSV
                );

            default:
                return Excel::download(
                    new CompraExport($request->all()),
                    "reporte_compras_{$timestamp}.xlsx"
                );
        }
    }

    private function getDataForPdf(Request $request): array
    {
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $proveedorId = $request->input('proveedor_id');
        $estadoId = $request->input('estado_id');

        $query = OrdenCompra::with(['proveedor', 'estado', 'usuario'])
            ->whereBetween('fecha_emision', [
                Carbon::parse($fechaDesde)->startOfDay(),
                Carbon::parse($fechaHasta)->endOfDay(),
            ]);

        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }
        if ($estadoId) {
            $query->where('estado_id', $estadoId);
        }

        $ordenes = $query->latest('fecha_emision')->get();
        $totalGastado = $ordenes->sum('total_final');
        $cantidadOrdenes = $ordenes->count();
        $ticketPromedio = $cantidadOrdenes > 0 ? $totalGastado / $cantidadOrdenes : 0;

        return [
            'periodo' => Carbon::parse($fechaDesde)->format('d/m/Y').' - '.Carbon::parse($fechaHasta)->format('d/m/Y'),
            'ordenes' => $ordenes,
            'kpis' => [
                'total_gastado' => $totalGastado,
                'cantidad_ordenes' => $cantidadOrdenes,
                'promedio_orden' => round($ticketPromedio, 2),
            ],
        ];
    }
}
