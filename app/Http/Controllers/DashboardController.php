<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Stock;
use App\Models\CuentaCorriente;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role;

        $kpis = [];
        $tablas = [];

        // KPIs y datos comunes para ADMIN y VENDEDOR
        if (in_array($role, ['administrador', 'vendedor'])) {
            // Ventas de Hoy
            $kpis['ventasHoy'] = Venta::whereDate('created_at', Carbon::today())->sum('total');
            
            // Ventas del mes
            $kpis['ventasMes'] = Venta::whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('total');

            // Cuentas por Cobrar
            $kpis['deudaTotal'] = CuentaCorriente::where('saldo', '>', 0)->sum('saldo');
            $kpis['clientesConDeuda'] = CuentaCorriente::where('saldo', '>', 0)->count();
            $kpis['saldoVencido'] = CuentaCorriente::where('saldo', '>', 0)
                ->get()
                ->sum(fn($cc) => $cc->calcularSaldoVencido());

            // Cantidad de Productos Activos
            $kpis['totalProductos'] = Producto::whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))->count();

            // Clientes activos (para vendedor)
            $kpis['totalClientes'] = Cliente::whereHas('estadoCliente', fn($q) => $q->where('nombreEstado', 'Activo'))->count();

            // Últimas 5 ventas
            $tablas['ultimasVentas'] = Venta::with(['cliente', 'vendedor'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($venta) => [
                    'id' => $venta->id,
                    'cliente' => $venta->cliente ? $venta->cliente->nombre_completo : 'Consumidor Final',
                    'total' => $venta->total,
                    'fecha' => $venta->created_at->format('H:i'),
                    'estado' => $venta->estado ?? 'completada',
                ]);

            // Clientes con mayor deuda (para gestión de cobranzas)
            $tablas['clientesMorosos'] = CuentaCorriente::with('cliente')
                ->where('saldo', '>', 0)
                ->orderBy('saldo', 'desc')
                ->take(5)
                ->get()
                ->map(fn($cc) => [
                    'cliente' => $cc->cliente->nombre_completo ?? 'Sin nombre',
                    'saldo' => $cc->saldo,
                    'diasVencido' => $cc->calcularDiasVencimiento(),
                ]);
        }

        // ============================================
        // KPIs y datos para TÉCNICO Y VENDEDOR (reparaciones)
        // ============================================
        if (in_array($role, ['administrador', 'tecnico', 'vendedor'])) {
            // Estados finales (no se cuentan como "en curso")
            $estadosFinales = ['Entregado', 'Anulado'];
            
            // Reparaciones en curso (no entregadas ni anuladas)
            $kpis['reparacionesEnCurso'] = Reparacion::whereHas('estado', fn($q) => 
                $q->whereNotIn('nombreEstado', $estadosFinales)
            )->count();
            
            // Reparaciones recién ingresadas pendientes de iniciar trabajo (estado "Recibido")
            $kpis['reparacionesPendientes'] = Reparacion::whereHas('estado', fn($q) => 
                $q->where('nombreEstado', 'Recibido')
            )->count();
            
            // Reparaciones listas para entregar (estado "Reparado")
            $kpis['reparacionesListas'] = Reparacion::whereHas('estado', fn($q) => 
                $q->where('nombreEstado', 'Reparado')
            )->count();

            // Los tres contadores de arriba son del taller completo a
            // propósito: son volúmenes de trabajo, no datos de una persona, y
            // saber que hay cinco equipos esperando le sirve al técnico.
            //
            // De acá en adelante sí se separa por usuario. La condición es el
            // permiso `reparaciones.ver_todas` y no el nombre del rol, para
            // que sea la misma regla que aplica el módulo de reparaciones y
            // que cada empresa pueda configurarla.
            $soloPropias = ! $user->puedeVerTodasLasReparaciones();

            if ($soloPropias) {
                $kpis['misReparaciones'] = Reparacion::where('tecnico_id', $user->id)
                    ->whereHas('estado', fn($q) => $q->whereNotIn('nombreEstado', $estadosFinales))
                    ->count();
            }

            // Últimas reparaciones
            $query = Reparacion::with(['cliente', 'tecnico', 'estado']);

            if ($soloPropias) {
                $query->where('tecnico_id', $user->id);
            }

            $tablas['ultimasReparaciones'] = $query
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($rep) => [
                    'id' => $rep->reparacionID,
                    'cliente' => $rep->cliente->nombre_completo ?? 'Sin cliente',
                    'equipo' => $rep->equipo_marca . ' ' . $rep->equipo_modelo,
                    'estado' => $rep->estado->nombreEstado ?? 'Sin estado',
                    'fecha' => $rep->created_at->format('d/m H:i'),
                ]);
        }

        // ============================================
        // Stock crítico - visible para todos los roles
        // ============================================
        $kpis['stockCritico'] = Stock::whereColumn('cantidad_disponible', '<=', 'stock_minimo')->count();

        $tablas['productosCriticos'] = Stock::with('producto')
            ->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
            ->orderBy('cantidad_disponible', 'asc')
            ->take(5)
            ->get()
            ->map(fn($stock) => [
                'producto' => $stock->producto->nombre,
                'cantidad' => $stock->cantidad_disponible,
                'minimo' => $stock->stock_minimo,
                'deposito' => $stock->deposito->nombre ?? 'General',
            ]);

        return Inertia::render('Dashboard', [
            'kpis' => $kpis,
            'tablas' => $tablas,
            'userRole' => $role,
        ]);
    }
}