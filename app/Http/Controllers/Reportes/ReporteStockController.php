<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\StockExport;
use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReporteStockController extends Controller
{
    public function index(Request $request)
    {
        // 1. Registrar Auditoría de Visualización (Requisito RNF)
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tabla_afectada' => 'reportes',
            'detalles' => 'Reporte de Stock',
            'usuarioID' => Auth::id(),
            'motivo' => 'Visualización de tablero de stock',
        ]);

        // 2. Filtros
        $soloCritico = $request->boolean('bajo_stock');

        // 3. Query Base con relaciones
        $query = Stock::with(['producto.marca', 'producto.categoria']);

        if ($soloCritico) {
            $query->whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        }

        // 4. Obtener datos paginados para la tabla
        $stocks = $query->paginate(20)->withQueryString();

        // 5. Cálculos para KPIs (Tarjetas Superiores)
        // Nota: Hacemos clones del query para no romper la paginación
        $totalItems = (clone $query)->sum('cantidad_disponible');
        $productosCriticos = Stock::whereColumn('cantidad_disponible', '<=', 'stock_minimo')->count();
        $valorizacionAproximada = 0; // Pendiente: Requiere unir con tabla de precios si existe

        // 6. Datos para Gráficos (Chart.js)
        // Gráfico 1: Top 5 Productos con MENOS stock (Riesgo de quiebre)
        $topRiesgo = Stock::with('producto')
            ->orderBy('cantidad_disponible', 'asc')
            ->take(5)
            ->get();

        $graficoRiesgo = [
            'labels' => $topRiesgo->pluck('producto.nombre'),
            'datasets' => [
                [
                    'label' => 'Stock Disponible',
                    'data' => $topRiesgo->pluck('cantidad_disponible'),
                    'backgroundColor' => '#EF4444', // Rojo Laravel
                ],
            ],
        ];

        // Gráfico 2: Stock por Categoría (Top 8)
        $stockPorCategoria = Stock::join('productos', 'stock.productoID', '=', 'productos.id')
            ->join('categorias_producto', 'productos.categoriaProductoID', '=', 'categorias_producto.id')
            ->select('categorias_producto.nombre', DB::raw('SUM(stock.cantidad_disponible) as total'))
            ->groupBy('categorias_producto.id', 'categorias_producto.nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $graficoCategorias = [
            'labels' => $stockPorCategoria->pluck('nombre'),
            'datasets' => [
                [
                    'label' => 'Unidades',
                    'data' => $stockPorCategoria->pluck('total'),
                    'backgroundColor' => ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899', '#14B8A6'],
                ],
            ],
        ];

        return Inertia::render('Reportes/Stock/Index', [
            'stocks' => $stocks,
            'kpis' => [
                'total_unidades' => $totalItems,
                'productos_criticos' => $productosCriticos,
            ],
            'graficos' => [
                'riesgo' => $graficoRiesgo,
                'categorias' => $graficoCategorias,
            ],
            'filters' => [
                'bajo_stock' => $soloCritico,
            ],
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
            'motivo' => "Exportación {$formato} Stock",
        ]);

        switch ($formato) {
            case 'pdf':
                $data = $this->getDataForPdf($request);
                $pdf = Pdf::loadView('pdf.reportes.stock', $data)->setPaper('a4', 'landscape');

                return $pdf->download("reporte_stock_{$timestamp}.pdf");

            case 'csv':
                return Excel::download(
                    new StockExport($request->all()),
                    "reporte_stock_{$timestamp}.csv",
                    \Maatwebsite\Excel\Excel::CSV
                );

            default:
                return Excel::download(
                    new StockExport($request->all()),
                    "reporte_stock_{$timestamp}.xlsx"
                );
        }
    }

    private function getDataForPdf(Request $request): array
    {
        $soloCritico = $request->boolean('bajo_stock');

        $query = Stock::with(['producto.marca', 'producto.categoria']);

        if ($soloCritico) {
            $query->whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        }

        $stocks = $query->get();
        $totalItems = $stocks->sum('cantidad_disponible');
        $productosCriticos = $stocks->where('cantidad_disponible', '<=', $stocks->pluck('stock_minimo'))->count();

        // Recalcular críticos correctamente
        $productosCriticos = Stock::whereColumn('cantidad_disponible', '<=', 'stock_minimo')->count();

        return [
            'stocks' => $stocks,
            'kpis' => [
                'total_unidades' => $totalItems,
                'productos_criticos' => $productosCriticos,
            ],
        ];
    }
}
