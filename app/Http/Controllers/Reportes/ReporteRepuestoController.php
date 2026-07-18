<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\RepuestoExport;
use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\DetalleReparacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReporteRepuestoController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auditoría
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tabla_afectada' => 'reportes',
            'detalles' => 'Reporte de Uso de Repuestos',
            'usuarioID' => Auth::id(),
            'motivo' => 'Análisis de uso de repuestos en reparaciones',
        ]);

        // 2. Filtros
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $tecnicoId = $request->input('tecnico_id');

        $desde = Carbon::parse($fechaDesde)->startOfDay();
        $hasta = Carbon::parse($fechaHasta)->endOfDay();

        // 3. Query base: detalles de reparación (repuestos usados)
        $queryBase = DetalleReparacion::query()
            ->whereHas('reparacion', function ($q) use ($desde, $hasta, $tecnicoId) {
                $q->whereBetween('fecha_ingreso', [$desde, $hasta]);
                if ($tecnicoId) {
                    $q->where('tecnico_id', $tecnicoId);
                }
            });

        // 4. Detalle paginado
        $detalles = (clone $queryBase)
            ->with(['producto.categoria', 'reparacion.tecnico', 'reparacion.cliente'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        // 5. KPIs
        $totalUnidades = (clone $queryBase)->sum('cantidad');
        $costoTotal = (clone $queryBase)->sum('subtotal');
        $repuestosDistintos = (clone $queryBase)->distinct('producto_id')->count('producto_id');
        $reparacionesConRepuestos = (clone $queryBase)->distinct('reparacion_id')->count('reparacion_id');

        // 6. Top 10 repuestos más usados (gráfico de barras horizontal)
        $topRepuestos = DetalleReparacion::query()
            ->join('productos', 'detalle_reparaciones.producto_id', '=', 'productos.id')
            ->join('reparaciones', 'detalle_reparaciones.reparacion_id', '=', 'reparaciones.reparacionID')
            ->whereBetween('reparaciones.fecha_ingreso', [$desde, $hasta])
            ->when($tecnicoId, fn ($q) => $q->where('reparaciones.tecnico_id', $tecnicoId))
            ->select(
                'productos.nombre',
                'productos.codigo',
                DB::raw('SUM(detalle_reparaciones.cantidad) as total_cantidad'),
                DB::raw('SUM(detalle_reparaciones.subtotal) as costo_total'),
                DB::raw('AVG(detalle_reparaciones.precio_unitario) as precio_promedio')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderByDesc('total_cantidad')
            ->limit(10)
            ->get();

        $graficoTopRepuestos = [
            'labels' => $topRepuestos->pluck('nombre'),
            'datasets' => [
                [
                    'label' => 'Unidades Utilizadas',
                    'data' => $topRepuestos->pluck('total_cantidad'),
                    'backgroundColor' => [
                        '#BE185D', '#DB2777', '#EC4899', '#F472B6', '#F9A8D4',
                        '#7C3AED', '#8B5CF6', '#A78BFA', '#C4B5FD', '#DDD6FE',
                    ],
                    'borderColor' => '#BE185D',
                    'borderWidth' => 1,
                ],
            ],
        ];

        // 7. Consumo por técnico (gráfico doughnut)
        $usoPorTecnico = DetalleReparacion::query()
            ->join('reparaciones', 'detalle_reparaciones.reparacion_id', '=', 'reparaciones.reparacionID')
            ->join('users', 'reparaciones.tecnico_id', '=', 'users.id')
            ->whereBetween('reparaciones.fecha_ingreso', [$desde, $hasta])
            ->when($tecnicoId, fn ($q) => $q->where('reparaciones.tecnico_id', $tecnicoId))
            ->select(
                'users.name as nombre',
                DB::raw('COUNT(DISTINCT reparaciones.reparacionID) as reparaciones'),
                DB::raw('SUM(detalle_reparaciones.cantidad) as total_unidades'),
                DB::raw('SUM(detalle_reparaciones.subtotal) as costo_total')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('costo_total')
            ->get();

        $graficoTecnicos = [
            'labels' => $usoPorTecnico->pluck('nombre'),
            'datasets' => [
                [
                    'label' => 'Costo Total ($)',
                    'data' => $usoPorTecnico->pluck('costo_total'),
                    'backgroundColor' => ['#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#06B6D4'],
                ],
            ],
        ];

        // 8. Evolución de consumo por día (gráfico de línea)
        $consumoPorDia = DetalleReparacion::query()
            ->join('reparaciones', 'detalle_reparaciones.reparacion_id', '=', 'reparaciones.reparacionID')
            ->whereBetween('reparaciones.fecha_ingreso', [$desde, $hasta])
            ->when($tecnicoId, fn ($q) => $q->where('reparaciones.tecnico_id', $tecnicoId))
            ->select(
                DB::raw('DATE(reparaciones.fecha_ingreso) as fecha'),
                DB::raw('SUM(detalle_reparaciones.cantidad) as total_unidades'),
                DB::raw('SUM(detalle_reparaciones.subtotal) as costo_total')
            )
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $graficoEvolucion = [
            'labels' => $consumoPorDia->pluck('fecha')->map(fn ($d) => Carbon::parse($d)->format('d/m')),
            'datasets' => [
                [
                    'label' => 'Unidades',
                    'data' => $consumoPorDia->pluck('total_unidades'),
                    'borderColor' => '#BE185D',
                    'backgroundColor' => 'rgba(190, 24, 93, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Costo ($)',
                    'data' => $consumoPorDia->pluck('costo_total'),
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
        ];

        // Técnico seleccionado
        $tecnicoSeleccionado = $tecnicoId
            ? User::find($tecnicoId, ['id', 'name', 'rol_id'])
            : null;

        return Inertia::render('Reportes/Repuestos/Index', [
            'detalles' => $detalles,
            'kpis' => [
                'total_unidades' => (int) $totalUnidades,
                'costo_total' => round($costoTotal, 2),
                'repuestos_distintos' => $repuestosDistintos,
                'reparaciones_con_repuestos' => $reparacionesConRepuestos,
            ],
            'graficos' => [
                'top_repuestos' => $graficoTopRepuestos,
                'tecnicos' => $graficoTecnicos,
                'evolucion' => $graficoEvolucion,
            ],
            'filters' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'tecnico_id' => $tecnicoId,
            ],
            'tecnicoSeleccionado' => $tecnicoSeleccionado,
        ]);
    }

    public function exportar(Request $request)
    {
        $formato = $request->input('formato', 'xlsx');
        $timestamp = now()->format('Ymd_His');

        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tabla_afectada' => 'reportes',
            'detalles' => "Reporte Repuestos ({$formato})",
            'usuarioID' => Auth::id(),
            'motivo' => "Exportación {$formato} uso de repuestos",
        ]);

        switch ($formato) {
            case 'pdf':
                $data = $this->getDataForPdf($request);
                $pdf = Pdf::loadView('pdf.reportes.repuestos', $data)->setPaper('a4', 'landscape');

                return $pdf->download("reporte_repuestos_{$timestamp}.pdf");

            case 'csv':
                return Excel::download(
                    new RepuestoExport($request->all()),
                    "reporte_repuestos_{$timestamp}.csv",
                    \Maatwebsite\Excel\Excel::CSV
                );

            default:
                return Excel::download(
                    new RepuestoExport($request->all()),
                    "reporte_repuestos_{$timestamp}.xlsx"
                );
        }
    }

    private function getDataForPdf(Request $request): array
    {
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $tecnicoId = $request->input('tecnico_id');

        $desde = Carbon::parse($fechaDesde)->startOfDay();
        $hasta = Carbon::parse($fechaHasta)->endOfDay();

        $queryBase = DetalleReparacion::query()
            ->whereHas('reparacion', function ($q) use ($desde, $hasta, $tecnicoId) {
                $q->whereBetween('fecha_ingreso', [$desde, $hasta]);
                if ($tecnicoId) {
                    $q->where('tecnico_id', $tecnicoId);
                }
            });

        $totalUnidades = (clone $queryBase)->sum('cantidad');
        $costoTotal = (clone $queryBase)->sum('subtotal');
        $repuestosDistintos = (clone $queryBase)->distinct('producto_id')->count('producto_id');
        $reparacionesConRepuestos = (clone $queryBase)->distinct('reparacion_id')->count('reparacion_id');

        $topRepuestos = DetalleReparacion::query()
            ->join('productos', 'detalle_reparaciones.producto_id', '=', 'productos.id')
            ->join('reparaciones', 'detalle_reparaciones.reparacion_id', '=', 'reparaciones.reparacionID')
            ->whereBetween('reparaciones.fecha_ingreso', [$desde, $hasta])
            ->when($tecnicoId, fn ($q) => $q->where('reparaciones.tecnico_id', $tecnicoId))
            ->select(
                'productos.nombre', 'productos.codigo',
                DB::raw('SUM(detalle_reparaciones.cantidad) as total_cantidad'),
                DB::raw('SUM(detalle_reparaciones.subtotal) as costo_total'),
                DB::raw('AVG(detalle_reparaciones.precio_unitario) as precio_promedio')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderByDesc('total_cantidad')
            ->limit(15)
            ->get();

        $usoPorTecnico = DetalleReparacion::query()
            ->join('reparaciones', 'detalle_reparaciones.reparacion_id', '=', 'reparaciones.reparacionID')
            ->join('users', 'reparaciones.tecnico_id', '=', 'users.id')
            ->whereBetween('reparaciones.fecha_ingreso', [$desde, $hasta])
            ->when($tecnicoId, fn ($q) => $q->where('reparaciones.tecnico_id', $tecnicoId))
            ->select(
                'users.name as nombre',
                DB::raw('COUNT(DISTINCT reparaciones.reparacionID) as reparaciones'),
                DB::raw('SUM(detalle_reparaciones.cantidad) as total_unidades'),
                DB::raw('SUM(detalle_reparaciones.subtotal) as costo_total')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('costo_total')
            ->get();

        return [
            'periodo' => Carbon::parse($fechaDesde)->format('d/m/Y').' - '.Carbon::parse($fechaHasta)->format('d/m/Y'),
            'kpis' => [
                'total_unidades' => (int) $totalUnidades,
                'costo_total' => round($costoTotal, 2),
                'repuestos_distintos' => $repuestosDistintos,
                'reparaciones_con_repuestos' => $reparacionesConRepuestos,
            ],
            'topRepuestos' => $topRepuestos,
            'usoPorTecnico' => $usoPorTecnico,
        ];
    }
}
