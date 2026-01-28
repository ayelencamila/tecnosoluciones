<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Auditoria;
use App\Exports\VentaExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReporteVentaController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auditoría
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tablaAfectada' => 'reportes',
            'valorNuevo' => 'Reporte de Ventas',
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Análisis de ventas'
        ]);

        // 2. Filtros
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $clienteId = $request->input('cliente_id');

        // 3. Query Base CORREGIDA
        // Agregamos 'estado' al with() y filtramos por estado_venta_id
        $query = Venta::with(['cliente', 'vendedor', 'estado']) 
            ->where('estado_venta_id', '!=', 3) // 3 = Anulada (Según tu Seeder)
            ->whereBetween('fecha_venta', [ // Usamos fecha_venta, no created_at (mejor precisión)
                Carbon::parse($fechaDesde)->startOfDay(), 
                Carbon::parse($fechaHasta)->endOfDay()
            ]);

        if ($clienteId) {
            $query->where('clienteID', $clienteId); // Corregido: clienteID (según tu migración)
        }

        // 4. Datos para Tabla
        $ventas = (clone $query)->latest('fecha_venta')->paginate(15)->withQueryString();

        // 5. KPIs
        $totalIngresos = (clone $query)->sum('total');
        $cantidadVentas = (clone $query)->count();
        $ticketPromedio = $cantidadVentas > 0 ? $totalIngresos / $cantidadVentas : 0;

        // 6. Gráfico 1: Ventas por Día
        $ventasPorDia = (clone $query)
            ->select(
                DB::raw('DATE(fecha_venta) as fecha'), 
                DB::raw('SUM(total) as total')
            )
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $graficoTiempo = [
            'labels' => $ventasPorDia->pluck('fecha')->map(fn($d) => Carbon::parse($d)->format('d/m')),
            'datasets' => [
                [
                    'label' => 'Ventas ($)',
                    'data' => $ventasPorDia->pluck('total'),
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.2)',
                    'fill' => true,
                    'tension' => 0.4
                ]
            ]
        ];

        // 7. Gráfico 2: Ventas por Vendedor
        $ventasPorVendedor = (clone $query)
            ->select('user_id', DB::raw('count(*) as cantidad'))
            ->groupBy('user_id')
            ->with('vendedor') // El vendedor ya estaba cargado pero el select lo limpia
            ->get();

        $graficoVendedores = [
            'labels' => $ventasPorVendedor->map(fn($v) => $v->vendedor->name ?? 'Sistema'),
            'datasets' => [
                [
                    'data' => $ventasPorVendedor->pluck('cantidad'),
                    'backgroundColor' => ['#10B981', '#3B82F6', '#F59E0B', '#EC4899'],
                ]
            ]
        ];

        // Obtener cliente seleccionado si existe (para mostrar en el buscador)
        $clienteSeleccionado = $clienteId 
            ? Cliente::select('clienteID', 'nombre', 'apellido', 'dni')->find($clienteId) 
            : null;

        return Inertia::render('Reportes/Ventas/Index', [
            'ventas' => $ventas,
            'kpis' => [
                'total_ingresos' => $totalIngresos,
                'cantidad_ventas' => $cantidadVentas,
                'ticket_promedio' => round($ticketPromedio, 2)
            ],
            'graficos' => [
                'tiempo' => $graficoTiempo,
                'vendedores' => $graficoVendedores
            ],
            'filters' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'cliente_id' => $clienteId,
            ],
            'clienteSeleccionado' => $clienteSeleccionado, // Solo el cliente seleccionado, no toda la lista
        ]);
    }

    public function exportar(Request $request)
    {
        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tablaAfectada' => 'reportes',
            'usuarioID' => Auth::id(),
            'motivo' => 'Descarga Excel Ventas'
        ]);

        return Excel::download(new VentaExport($request->all()), 'reporte_ventas.xlsx');
    }
}
