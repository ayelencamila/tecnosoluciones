<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Stock;
use App\Models\Auditoria;
use App\Models\Deposito;
use App\Exports\StockExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReporteStockController extends Controller
{
    public function index(Request $request)
    {
        // 1. Registrar Auditoría de Visualización (Requisito RNF)
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tablaAfectada' => 'reportes',
            'registroId' => null,
            'valorAnterior' => null,
            'valorNuevo' => 'Reporte de Stock',
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Visualización de tablero de stock'
        ]);

        // 2. Filtros
        $depositoId = $request->input('deposito_id');
        $soloCritico = $request->boolean('bajo_stock');

        // 3. Query Base con relaciones
        $query = Stock::with(['producto.marca', 'producto.categoria', 'deposito']);

        if ($depositoId) {
            $query->where('deposito_id', $depositoId);
        }
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
                ]
            ]
        ];

        // Gráfico 2: Distribución por Depósito (si hay más de uno)
        $stockPorDeposito = Stock::select('deposito_id', DB::raw('sum(cantidad_disponible) as total'))
            ->groupBy('deposito_id')
            ->with('deposito')
            ->get();

        $graficoDepositos = [
            'labels' => $stockPorDeposito->map(fn($item) => $item->deposito->nombre ?? 'General'),
            'datasets' => [
                [
                    'label' => 'Unidades',
                    'data' => $stockPorDeposito->pluck('total'),
                    'backgroundColor' => ['#3B82F6', '#10B981', '#F59E0B'], 
                ]
            ]
        ];

        return Inertia::render('Reportes/Stock/Index', [
            'stocks' => $stocks,
            'kpis' => [
                'total_unidades' => $totalItems,
                'productos_criticos' => $productosCriticos,
            ],
            'graficos' => [
                'riesgo' => $graficoRiesgo,
                'depositos' => $graficoDepositos
            ],
            'filters' => [
                'deposito_id' => $depositoId,
                'bajo_stock' => $soloCritico,
            ],
            'depositos' => Deposito::all(['deposito_id', 'nombre']), 
        ]);
    }

    public function exportar(Request $request)
    {
        // Registrar Auditoría de Exportación
        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tablaAfectada' => 'reportes',
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Descarga Excel Stock'
        ]);

        return Excel::download(new StockExport($request->all()), 'reporte_stock_' . now()->format('Ymd_His') . '.xlsx');
    }
}
