<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Stock;
use App\Models\CuentaCorriente;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. KPIs (Tarjetas Superiores)
        
        // Ventas de Hoy (Suma del total de ventas registradas hoy)
        $ventasHoy = Venta::whereDate('created_at', Carbon::today())->sum('total');

        // Stock Crítico (Productos por debajo del mínimo)
        // Usamos whereColumn para comparar dos columnas de la misma tabla
        $stockCriticoCount = Stock::whereColumn('cantidad_disponible', '<=', 'stock_minimo')->count();

        // Cuentas por Cobrar (Suma de saldos positivos de clientes)
        // Asumimos que saldo > 0 es deuda del cliente hacia nosotros
        $deudaTotal = CuentaCorriente::where('saldo', '>', 0)->sum('saldo');

        // Cantidad de Productos Activos
        $totalProductos = Producto::whereHas('estado', function($q) {
            $q->where('nombre', 'Activo');
        })->count();

        // 2. Tablas de Resumen (Filas Centrales)

        // Últimas 5 ventas para ver movimiento reciente
        $ultimasVentas = Venta::with(['cliente', 'vendedor'])
                            ->latest()
                            ->take(5)
                            ->get()
                            ->map(function ($venta) {
                                return [
                                    'id' => $venta->id,
                                    'cliente' => $venta->cliente ? $venta->cliente->nombre_completo : 'Consumidor Final',
                                    'total' => $venta->total,
                                    'fecha' => $venta->created_at->format('H:i'), // Solo la hora
                                    'estado' => $venta->estado, // Asumiendo que tienes un campo estado o similar
                                ];
                            });

        // Top 5 productos con stock crítico para reposición urgente
        $productosCriticos = Stock::with('producto')
                                ->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
                                ->orderBy('cantidad_disponible', 'asc')
                                ->take(5)
                                ->get()
                                ->map(function ($stock) {
                                    return [
                                        'producto' => $stock->producto->nombre,
                                        'cantidad' => $stock->cantidad_disponible,
                                        'minimo' => $stock->stock_minimo,
                                        'deposito' => $stock->deposito->nombre ?? 'General',
                                    ];
                                });

        return Inertia::render('Dashboard', [
            'kpis' => [
                'ventasHoy' => $ventasHoy,
                'stockCritico' => $stockCriticoCount,
                'deudaTotal' => $deudaTotal,
                'totalProductos' => $totalProductos,
            ],
            'tablas' => [
                'ultimasVentas' => $ultimasVentas,
                'productosCriticos' => $productosCriticos,
            ]
        ]);
    }
}