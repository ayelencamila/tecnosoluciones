<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Venta;
use App\Models\Pago;
use App\Models\Reparacion;
use App\Models\OrdenCompra;
use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\Auditoria;
use App\Models\EstadoVenta;
use App\Models\EstadoOrdenCompra;
use App\Models\RecepcionMercaderia;
use App\Models\DetalleRecepcion;
use App\Exports\ReporteMensualExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReporteMensualController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auditoría
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tablaAfectada' => 'reportes',
            'valorNuevo' => 'Reporte Mensual Consolidado',
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Análisis financiero mensual'
        ]);

        // 2. Filtros de período
        $mes = $request->input('mes', Carbon::now()->month);
        $anio = $request->input('anio', Carbon::now()->year);
        $tipoGrafico = $request->input('tipo_grafico', 'ventas');
        
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        // ================================================================
        // BALANCE MENSUAL — Formato Libro Diario (Entradas / Salidas)
        // Buenas prácticas: Partida doble, principio de devengado,
        // separación clara de flujos de fondos.
        // ================================================================

        // ==================== ENTRADAS ====================
        // Todo ingreso de dinero al negocio en el período

        // 1. Ventas de productos (no anuladas)
        $totalVentas = Venta::where('estado_venta_id', '!=', EstadoVenta::ANULADA)
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->sum('total');
        $cantidadVentas = Venta::where('estado_venta_id', '!=', EstadoVenta::ANULADA)
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->count();

        // 2. Servicios de reparación (entregadas y cobradas)
        $totalReparaciones = Reparacion::where('anulada', false)
            ->whereNotNull('fecha_entrega_real')
            ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])
            ->sum('total_final');
        $cantidadReparaciones = Reparacion::where('anulada', false)
            ->whereNotNull('fecha_entrega_real')
            ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])
            ->count();

        // 3. Cobranzas de Cuenta Corriente (pagos recibidos de clientes CC)
        $totalCobranzas = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->sum('monto');
        $cantidadCobranzas = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->count();

        // Desglose de cobranzas por medio de pago
        $cobranzasPorMedioPago = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->join('medios_pago', 'pagos.medioPagoID', '=', 'medios_pago.medioPagoID')
            ->select(
                'medios_pago.nombre as concepto',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(pagos.monto) as total')
            )
            ->groupBy('medios_pago.medioPagoID', 'medios_pago.nombre')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn($item) => [
                'concepto' => 'Cobranza CC — ' . $item->concepto,
                'cantidad' => (int) $item->cantidad,
                'total' => (float) $item->total,
            ])
            ->toArray();

        $totalEntradas = $totalVentas + $totalReparaciones + $totalCobranzas;

        // ==================== SALIDAS ====================
        // Todo egreso de dinero del negocio en el período

        // 1. Compras a proveedores (OC recibidas)
        $estadosRecibidos = [
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL),
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_TOTAL),
        ];

        $totalComprasOC = OrdenCompra::whereIn('estado_id', $estadosRecibidos)
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->sum('total_final');
        $cantidadComprasOC = OrdenCompra::whereIn('estado_id', $estadosRecibidos)
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->count();

        // 2. Compras directas (reposiciones sin OC)
        $recepcionesDirectas = RecepcionMercaderia::whereNull('orden_compra_id')
            ->whereBetween('fecha_recepcion', [$fechaInicio, $fechaFin])
            ->with('detalles')
            ->get();
        $totalComprasDirectas = $recepcionesDirectas->sum(fn($r) => $r->detalles->sum(fn($d) => $d->cantidad_recibida * $d->precio_unitario));
        $cantidadComprasDirectas = $recepcionesDirectas->count();

        // 3. Gastos operativos (por categoría)
        $totalGastosOperativos = Gasto::activos()
            ->gastos()
            ->delMes($mes, $anio)
            ->sum('monto');

        $gastosOpPorCategoria = Gasto::activos()
            ->gastos()
            ->delMes($mes, $anio)
            ->join('categorias_gasto', 'gastos.categoria_gasto_id', '=', 'categorias_gasto.categoria_gasto_id')
            ->select(
                DB::raw("CONCAT('Gasto — ', categorias_gasto.nombre) as concepto"),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(gastos.monto) as total')
            )
            ->groupBy('categorias_gasto.categoria_gasto_id', 'categorias_gasto.nombre')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn($item) => [
                'concepto' => $item->concepto,
                'cantidad' => (int) $item->cantidad,
                'total' => (float) $item->total,
            ])
            ->toArray();

        // 4. Pérdidas extraordinarias (por categoría)
        $totalPerdidas = Gasto::activos()
            ->perdidas()
            ->delMes($mes, $anio)
            ->sum('monto');

        $perdidasPorCategoria = Gasto::activos()
            ->perdidas()
            ->delMes($mes, $anio)
            ->join('categorias_gasto', 'gastos.categoria_gasto_id', '=', 'categorias_gasto.categoria_gasto_id')
            ->select(
                DB::raw("CONCAT('Pérdida — ', categorias_gasto.nombre) as concepto"),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(gastos.monto) as total')
            )
            ->groupBy('categorias_gasto.categoria_gasto_id', 'categorias_gasto.nombre')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn($item) => [
                'concepto' => $item->concepto,
                'cantidad' => (int) $item->cantidad,
                'total' => (float) $item->total,
            ])
            ->toArray();

        $totalSalidas = $totalComprasOC + $totalComprasDirectas + $totalGastosOperativos + $totalPerdidas;

        // ==================== BALANCE ====================
        $balance = $totalEntradas - $totalSalidas;

        // ==================== GRÁFICOS SEGÚN FILTRO ====================
        
        // Generar array de días del mes
        $diasDelMes = [];
        $current = $fechaInicio->copy();
        while ($current <= $fechaFin) {
            $diasDelMes[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Gráfico de evolución según tipo
        $graficoEvolucion = $this->getGraficoEvolucion($tipoGrafico, $fechaInicio, $fechaFin, $diasDelMes, $mes, $anio);
        
        // Gráfico de distribución según tipo
        $graficoDistribucion = $this->getGraficoDistribucion($tipoGrafico, $fechaInicio, $fechaFin, $mes, $anio);

        // Opciones para el filtro de gráficos
        $tiposGrafico = [
            ['value' => 'ventas', 'label' => 'Ventas'],
            ['value' => 'reparaciones', 'label' => 'Reparaciones'],
            ['value' => 'compras', 'label' => 'Compras'],
            ['value' => 'gastos', 'label' => 'Gastos'],
            ['value' => 'pagos', 'label' => 'Pagos Recibidos'],
        ];

        return Inertia::render('Reportes/Mensual/Index', [
            'filters' => [
                'mes' => (int)$mes,
                'anio' => (int)$anio,
                'tipo_grafico' => $tipoGrafico,
            ],
            'periodo' => [
                'nombre' => $fechaInicio->translatedFormat('F Y'),
                'inicio' => $fechaInicio->format('d/m/Y'),
                'fin' => $fechaFin->format('d/m/Y'),
            ],
            'planilla' => [
                // ENTRADAS (todo ingreso de dinero)
                'entradas' => array_merge(
                    [
                        ['concepto' => 'Ventas de Productos', 'cantidad' => $cantidadVentas, 'total' => $totalVentas],
                        ['concepto' => 'Servicios de Reparación', 'cantidad' => $cantidadReparaciones, 'total' => $totalReparaciones],
                    ],
                    $cobranzasPorMedioPago
                ),
                'total_entradas' => $totalEntradas,

                // SALIDAS (todo egreso de dinero)
                'salidas' => array_merge(
                    [
                        ['concepto' => 'Compras a Proveedores (OC)', 'cantidad' => $cantidadComprasOC, 'total' => $totalComprasOC],
                        ['concepto' => 'Compras Directas (Reposiciones)', 'cantidad' => $cantidadComprasDirectas, 'total' => $totalComprasDirectas],
                    ],
                    $gastosOpPorCategoria,
                    $perdidasPorCategoria
                ),
                'total_salidas' => $totalSalidas,

                // BALANCE
                'balance' => $balance,
            ],
            'graficos' => [
                'evolucion' => $graficoEvolucion,
                'distribucion' => $graficoDistribucion,
            ],
            'tiposGrafico' => $tiposGrafico,
        ]);
    }

    private function getGraficoEvolucion($tipo, $fechaInicio, $fechaFin, $diasDelMes, $mes, $anio)
    {
        $labels = collect($diasDelMes)->map(fn($d) => Carbon::parse($d)->format('d'));
        $data = [];
        $color = '#3B82F6';
        $label = '';

        switch ($tipo) {
            case 'ventas':
                $datosPorDia = Venta::where('estado_venta_id', '!=', EstadoVenta::ANULADA)
                    ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
                    ->select(DB::raw('DATE(fecha_venta) as fecha'), DB::raw('SUM(total) as total'))
                    ->groupBy('fecha')
                    ->pluck('total', 'fecha');
                $color = '#10B981';
                $label = 'Ventas';
                break;

            case 'reparaciones':
                $datosPorDia = Reparacion::where('anulada', false)
                    ->whereNotNull('fecha_entrega_real')
                    ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])
                    ->select(DB::raw('DATE(fecha_entrega_real) as fecha'), DB::raw('SUM(total_final) as total'))
                    ->groupBy('fecha')
                    ->pluck('total', 'fecha');
                $color = '#F59E0B';
                $label = 'Reparaciones';
                break;

            case 'compras':
                $estadosRecibidos = [
                    EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL),
                    EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_TOTAL),
                ];
                // OCs recibidas por día
                $datosPorDiaOC = OrdenCompra::whereIn('estado_id', $estadosRecibidos)
                    ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                    ->select(DB::raw('DATE(fecha_emision) as fecha'), DB::raw('SUM(total_final) as total'))
                    ->groupBy('fecha')
                    ->pluck('total', 'fecha');
                // Compras directas por día
                $datosPorDiaDirectas = DetalleRecepcion::join('recepciones_mercaderia', 'detalle_recepciones.recepcion_id', '=', 'recepciones_mercaderia.id')
                    ->whereNull('recepciones_mercaderia.orden_compra_id')
                    ->whereBetween('recepciones_mercaderia.fecha_recepcion', [$fechaInicio, $fechaFin])
                    ->select(DB::raw('DATE(recepciones_mercaderia.fecha_recepcion) as fecha'), DB::raw('SUM(detalle_recepciones.cantidad_recibida * detalle_recepciones.precio_unitario) as total'))
                    ->groupBy('fecha')
                    ->pluck('total', 'fecha');
                // Merge ambos
                $datosPorDia = collect($diasDelMes)->mapWithKeys(function ($dia) use ($datosPorDiaOC, $datosPorDiaDirectas) {
                    $total = ($datosPorDiaOC[$dia] ?? 0) + ($datosPorDiaDirectas[$dia] ?? 0);
                    return [$dia => $total];
                })->filter(fn($v) => $v > 0);
                $color = '#EF4444';
                $label = 'Compras';
                break;

            case 'gastos':
                $datosPorDia = Gasto::where('anulado', false)
                    ->whereMonth('fecha', $mes)
                    ->whereYear('fecha', $anio)
                    ->select(DB::raw('DATE(fecha) as fecha'), DB::raw('SUM(monto) as total'))
                    ->groupBy('fecha')
                    ->pluck('total', 'fecha');
                $color = '#8B5CF6';
                $label = 'Gastos y Pérdidas';
                break;

            case 'pagos':
                $datosPorDia = Pago::where('anulado', false)
                    ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                    ->select(DB::raw('DATE(fecha_pago) as fecha'), DB::raw('SUM(monto) as total'))
                    ->groupBy('fecha')
                    ->pluck('total', 'fecha');
                $color = '#06B6D4';
                $label = 'Pagos Recibidos';
                break;
        }

        $data = collect($diasDelMes)->map(fn($d) => $datosPorDia[$d] ?? 0);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $data,
                    'borderColor' => $color,
                    'backgroundColor' => $color . '20',
                    'fill' => true,
                    'tension' => 0,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                ]
            ]
        ];
    }

    private function getGraficoDistribucion($tipo, $fechaInicio, $fechaFin, $mes, $anio)
    {
        $labels = [];
        $data = [];
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899', '#14B8A6'];

        switch ($tipo) {
            case 'ventas':
                // Ventas por vendedor
                $resultado = Venta::where('estado_venta_id', '!=', EstadoVenta::ANULADA)
                    ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
                    ->join('users', 'ventas.user_id', '=', 'users.id')
                    ->select('users.name', DB::raw('SUM(ventas.total) as total'))
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total', 'desc')
                    ->limit(8)
                    ->get();
                $labels = $resultado->pluck('name');
                $data = $resultado->pluck('total');
                break;

            case 'reparaciones':
                // Reparaciones por técnico
                $resultado = Reparacion::where('anulada', false)
                    ->whereNotNull('fecha_entrega_real')
                    ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])
                    ->join('users', 'reparaciones.tecnico_id', '=', 'users.id')
                    ->select('users.name', DB::raw('SUM(reparaciones.total_final) as total'))
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total', 'desc')
                    ->limit(8)
                    ->get();
                $labels = $resultado->pluck('name');
                $data = $resultado->pluck('total');
                break;

            case 'compras':
                // Compras por proveedor
                $estadosRecibidos = [
                    EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL),
                    EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_TOTAL),
                ];
                $resultado = OrdenCompra::whereIn('estado_id', $estadosRecibidos)
                    ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                    ->join('proveedores', 'ordenes_compra.proveedor_id', '=', 'proveedores.proveedor_id')
                    ->select(DB::raw('COALESCE(proveedores.razon_social, proveedores.nombre) as nombre'), DB::raw('SUM(ordenes_compra.total_final) as total'))
                    ->groupBy('proveedores.proveedor_id', 'proveedores.razon_social', 'proveedores.nombre')
                    ->orderBy('total', 'desc')
                    ->limit(8)
                    ->get();
                $labels = $resultado->pluck('nombre');
                $data = $resultado->pluck('total');
                break;

            case 'gastos':
                // Gastos por categoría
                $resultado = Gasto::where('anulado', false)
                    ->whereMonth('fecha', $mes)
                    ->whereYear('fecha', $anio)
                    ->join('categorias_gasto', 'gastos.categoria_gasto_id', '=', 'categorias_gasto.categoria_gasto_id')
                    ->select('categorias_gasto.nombre', DB::raw('SUM(gastos.monto) as total'))
                    ->groupBy('categorias_gasto.categoria_gasto_id', 'categorias_gasto.nombre')
                    ->orderBy('total', 'desc')
                    ->limit(8)
                    ->get();
                $labels = $resultado->pluck('nombre');
                $data = $resultado->pluck('total');
                break;

            case 'pagos':
                // Pagos por medio de pago
                $resultado = Pago::where('anulado', false)
                    ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                    ->join('medios_pago', 'pagos.medioPagoID', '=', 'medios_pago.medioPagoID')
                    ->select('medios_pago.nombre', DB::raw('SUM(pagos.monto) as total'))
                    ->groupBy('medios_pago.medioPagoID', 'medios_pago.nombre')
                    ->orderBy('total', 'desc')
                    ->get();
                $labels = $resultado->pluck('nombre');
                $data = $resultado->pluck('total');
                break;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($labels)),
                ]
            ]
        ];
    }

    public function exportar(Request $request)
    {
        $mes = $request->input('mes', Carbon::now()->month);
        $anio = $request->input('anio', Carbon::now()->year);
        $formato = $request->input('formato', 'xlsx');
        $timestamp = now()->format('Ymd_His');

        // Auditoría
        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tablaAfectada' => 'reportes',
            'valorNuevo' => "Reporte Mensual {$mes}/{$anio} ({$formato})",
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => "Exportación {$formato} reporte mensual"
        ]);

        switch ($formato) {
            case 'pdf':
                $data = $this->getDataForPdf($mes, $anio);
                $pdf = Pdf::loadView('pdf.reportes.mensual', $data)->setPaper('a4', 'portrait');
                return $pdf->download("reporte_mensual_{$anio}_{$mes}_{$timestamp}.pdf");

            case 'csv':
                return Excel::download(
                    new ReporteMensualExport($mes, $anio),
                    "reporte_mensual_{$anio}_{$mes}_{$timestamp}.csv",
                    \Maatwebsite\Excel\Excel::CSV
                );

            default:
                return Excel::download(
                    new ReporteMensualExport($mes, $anio),
                    "reporte_mensual_{$anio}_{$mes}_{$timestamp}.xlsx"
                );
        }
    }

    private function getDataForPdf(int $mes, int $anio): array
    {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        // ENTRADAS
        $totalVentas = Venta::where('estado_venta_id', '!=', EstadoVenta::ANULADA)
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])->sum('total');
        $cantidadVentas = Venta::where('estado_venta_id', '!=', EstadoVenta::ANULADA)
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])->count();

        $totalReparaciones = Reparacion::where('anulada', false)->whereNotNull('fecha_entrega_real')
            ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])->sum('total_final');
        $cantidadReparaciones = Reparacion::where('anulada', false)->whereNotNull('fecha_entrega_real')
            ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])->count();

        $totalCobranzas = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])->sum('monto');
        $cantidadCobranzas = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])->count();

        $cobranzasPorMedioPago = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->join('medios_pago', 'pagos.medioPagoID', '=', 'medios_pago.medioPagoID')
            ->select('medios_pago.nombre as concepto', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(pagos.monto) as total'))
            ->groupBy('medios_pago.medioPagoID', 'medios_pago.nombre')
            ->orderBy('total', 'desc')->get()
            ->map(fn($i) => ['concepto' => 'Cobranza CC — ' . $i->concepto, 'cantidad' => (int) $i->cantidad, 'total' => (float) $i->total])
            ->toArray();

        $totalEntradas = $totalVentas + $totalReparaciones + $totalCobranzas;

        // SALIDAS
        $estadosRecibidos = [
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL),
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_TOTAL),
        ];
        $totalComprasOC = OrdenCompra::whereIn('estado_id', $estadosRecibidos)
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])->sum('total_final');
        $cantidadComprasOC = OrdenCompra::whereIn('estado_id', $estadosRecibidos)
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])->count();

        $recepcionesDirectas = RecepcionMercaderia::whereNull('orden_compra_id')
            ->whereBetween('fecha_recepcion', [$fechaInicio, $fechaFin])
            ->with('detalles')->get();
        $totalComprasDirectas = $recepcionesDirectas->sum(fn($r) => $r->detalles->sum(fn($d) => $d->cantidad_recibida * $d->precio_unitario));
        $cantidadComprasDirectas = $recepcionesDirectas->count();

        $totalGastosOp = Gasto::activos()->gastos()->delMes($mes, $anio)->sum('monto');
        $gastosOpPorCategoria = Gasto::activos()->gastos()->delMes($mes, $anio)
            ->join('categorias_gasto', 'gastos.categoria_gasto_id', '=', 'categorias_gasto.categoria_gasto_id')
            ->select(DB::raw("CONCAT('Gasto — ', categorias_gasto.nombre) as concepto"), DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(gastos.monto) as total'))
            ->groupBy('categorias_gasto.categoria_gasto_id', 'categorias_gasto.nombre')
            ->orderBy('total', 'desc')->get()
            ->map(fn($i) => ['concepto' => $i->concepto, 'cantidad' => (int) $i->cantidad, 'total' => (float) $i->total])
            ->toArray();

        $totalPerdidas = Gasto::activos()->perdidas()->delMes($mes, $anio)->sum('monto');
        $perdidasPorCategoria = Gasto::activos()->perdidas()->delMes($mes, $anio)
            ->join('categorias_gasto', 'gastos.categoria_gasto_id', '=', 'categorias_gasto.categoria_gasto_id')
            ->select(DB::raw("CONCAT('Pérdida — ', categorias_gasto.nombre) as concepto"), DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(gastos.monto) as total'))
            ->groupBy('categorias_gasto.categoria_gasto_id', 'categorias_gasto.nombre')
            ->orderBy('total', 'desc')->get()
            ->map(fn($i) => ['concepto' => $i->concepto, 'cantidad' => (int) $i->cantidad, 'total' => (float) $i->total])
            ->toArray();

        $totalSalidas = $totalComprasOC + $totalComprasDirectas + $totalGastosOp + $totalPerdidas;
        $balance = $totalEntradas - $totalSalidas;

        return [
            'periodo' => [
                'nombre' => $fechaInicio->translatedFormat('F Y'),
                'inicio' => $fechaInicio->format('d/m/Y'),
                'fin' => $fechaFin->format('d/m/Y'),
            ],
            'planilla' => [
                'entradas' => array_merge(
                    [
                        ['concepto' => 'Ventas de Productos', 'cantidad' => $cantidadVentas, 'total' => $totalVentas],
                        ['concepto' => 'Servicios de Reparación', 'cantidad' => $cantidadReparaciones, 'total' => $totalReparaciones],
                    ],
                    $cobranzasPorMedioPago
                ),
                'total_entradas' => $totalEntradas,
                'salidas' => array_merge(
                    [
                        ['concepto' => 'Compras a Proveedores (OC)', 'cantidad' => $cantidadComprasOC, 'total' => $totalComprasOC],
                        ['concepto' => 'Compras Directas (Reposiciones)', 'cantidad' => $cantidadComprasDirectas, 'total' => $totalComprasDirectas],
                    ],
                    $gastosOpPorCategoria,
                    $perdidasPorCategoria
                ),
                'total_salidas' => $totalSalidas,
                'balance' => $balance,
            ],
        ];
    }
}
