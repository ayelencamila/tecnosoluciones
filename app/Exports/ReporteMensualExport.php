<?php

namespace App\Exports;

use App\Models\Venta;
use App\Models\Pago;
use App\Models\Gasto;
use App\Models\Reparacion;
use App\Models\OrdenCompra;
use App\Models\RecepcionMercaderia;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteMensualExport implements WithMultipleSheets
{
    protected $mes;
    protected $anio;

    public function __construct($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function sheets(): array
    {
        return [
            new ResumenSheet($this->mes, $this->anio),
            new VentasSheet($this->mes, $this->anio),
            new ReparacionesSheet($this->mes, $this->anio),
            new PagosSheet($this->mes, $this->anio),
            new ComprasSheet($this->mes, $this->anio),
        ];
    }
}

// ===== HOJA: RESUMEN =====
class ResumenSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $mes;
    protected $anio;

    public function __construct($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function collection()
    {
        $fechaInicio = Carbon::createFromDate($this->anio, $this->mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($this->anio, $this->mes, 1)->endOfMonth();

        $totalVentas = Venta::where('estado_venta_id', '!=', 3)
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->sum('total');

        $totalReparaciones = Reparacion::where('anulada', false)
            ->whereNotNull('fecha_entrega_real')
            ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])
            ->sum('total_final');

        $totalPagos = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->sum('monto');

        $cobranzasPorMedio = Pago::where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->join('medios_pago', 'pagos.medioPagoID', '=', 'medios_pago.medioPagoID')
            ->select('medios_pago.nombre', DB::raw('SUM(pagos.monto) as total'))
            ->groupBy('medios_pago.medioPagoID', 'medios_pago.nombre')
            ->orderBy('total', 'desc')->get();

        $totalEntradas = $totalVentas + $totalReparaciones + $totalPagos;

        // Salidas
        $totalComprasOC = OrdenCompra::whereIn('estado_id', [4, 5])
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->sum('total_final');

        $recepcionesDirectas = RecepcionMercaderia::whereNull('orden_compra_id')
            ->whereBetween('fecha_recepcion', [$fechaInicio, $fechaFin])
            ->with('detalles')->get();
        $totalComprasDirectas = $recepcionesDirectas->sum(fn($r) => $r->detalles->sum(fn($d) => $d->cantidad_recibida * $d->precio_unitario));

        $totalGastosOp = Gasto::activos()->gastos()->delMes($this->mes, $this->anio)->sum('monto');
        $gastosOpPorCat = Gasto::activos()->gastos()->delMes($this->mes, $this->anio)
            ->join('categorias_gasto', 'gastos.categoria_gasto_id', '=', 'categorias_gasto.categoria_gasto_id')
            ->select('categorias_gasto.nombre', DB::raw('SUM(gastos.monto) as total'))
            ->groupBy('categorias_gasto.categoria_gasto_id', 'categorias_gasto.nombre')
            ->orderBy('total', 'desc')->get();

        $totalPerdidas = Gasto::activos()->perdidas()->delMes($this->mes, $this->anio)->sum('monto');
        $perdidasPorCat = Gasto::activos()->perdidas()->delMes($this->mes, $this->anio)
            ->join('categorias_gasto', 'gastos.categoria_gasto_id', '=', 'categorias_gasto.categoria_gasto_id')
            ->select('categorias_gasto.nombre', DB::raw('SUM(gastos.monto) as total'))
            ->groupBy('categorias_gasto.categoria_gasto_id', 'categorias_gasto.nombre')
            ->orderBy('total', 'desc')->get();

        $totalSalidas = $totalComprasOC + $totalComprasDirectas + $totalGastosOp + $totalPerdidas;
        $balance = $totalEntradas - $totalSalidas;

        $rows = collect([
            ['ENTRADAS', ''],
            ['Ventas de Productos', number_format($totalVentas, 2, ',', '.')],
            ['Servicios de Reparación', number_format($totalReparaciones, 2, ',', '.')],
        ]);

        foreach ($cobranzasPorMedio as $c) {
            $rows->push(["Cobranza CC — {$c->nombre}", number_format($c->total, 2, ',', '.')]);
        }

        $rows->push(['Total Entradas', number_format($totalEntradas, 2, ',', '.')]);
        $rows->push(['', '']);
        $rows->push(['SALIDAS', '']);
        $rows->push(['Compras a Proveedores (OC)', number_format($totalComprasOC, 2, ',', '.')]);
        $rows->push(['Compras Directas (Reposiciones)', number_format($totalComprasDirectas, 2, ',', '.')]);

        foreach ($gastosOpPorCat as $g) {
            $rows->push(["Gasto — {$g->nombre}", number_format($g->total, 2, ',', '.')]);
        }
        foreach ($perdidasPorCat as $p) {
            $rows->push(["Pérdida — {$p->nombre}", number_format($p->total, 2, ',', '.')]);
        }

        $rows->push(['Total Salidas', number_format($totalSalidas, 2, ',', '.')]);
        $rows->push(['', '']);
        $rows->push(['RESULTADO', '']);
        $rows->push(['Resultado del Mes', number_format($balance, 2, ',', '.')]);

        return $rows;
    }

    public function headings(): array
    {
        $periodo = Carbon::createFromDate($this->anio, $this->mes, 1)->translatedFormat('F Y');
        return ['Concepto', "Importe ({$periodo})"];
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet)
    {
        // Bold the header row and section titles (ENTRADAS, SALIDAS, RESULTADO)
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true]],
        ];

        // Dynamically bold section headers and totals
        $lastRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $lastRow; $row++) {
            $cell = $sheet->getCell("A{$row}")->getValue();
            if (in_array($cell, ['ENTRADAS', 'SALIDAS', 'RESULTADO']) || str_starts_with($cell ?? '', 'Total ') || $cell === 'Resultado del Mes') {
                $styles[$row] = ['font' => ['bold' => true]];
            }
        }

        return $styles;
    }
}

// ===== HOJA: VENTAS =====
class VentasSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $mes;
    protected $anio;

    public function __construct($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function collection()
    {
        $fechaInicio = Carbon::createFromDate($this->anio, $this->mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($this->anio, $this->mes, 1)->endOfMonth();

        return Venta::with(['cliente', 'vendedor', 'medioPago'])
            ->where('estado_venta_id', '!=', 3)
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_venta')
            ->get()
            ->map(fn($v) => [
                'fecha' => $v->fecha_venta->format('d/m/Y'),
                'comprobante' => $v->numero_comprobante,
                'cliente' => $v->cliente ? "{$v->cliente->nombre} {$v->cliente->apellido}" : 'Consumidor Final',
                'vendedor' => $v->vendedor->name ?? 'Sistema',
                'medio_pago' => $v->medioPago->descripcion ?? '-',
                'subtotal' => number_format($v->subtotal, 2, ',', '.'),
                'descuentos' => number_format($v->total_descuentos, 2, ',', '.'),
                'total' => number_format($v->total, 2, ',', '.'),
            ]);
    }

    public function headings(): array
    {
        return ['Fecha', 'Comprobante', 'Cliente', 'Vendedor', 'Medio de Pago', 'Subtotal', 'Descuentos', 'Total'];
    }

    public function title(): string
    {
        return 'Ventas';
    }
}

// ===== HOJA: REPARACIONES =====
class ReparacionesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $mes;
    protected $anio;

    public function __construct($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function collection()
    {
        $fechaInicio = Carbon::createFromDate($this->anio, $this->mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($this->anio, $this->mes, 1)->endOfMonth();

        return Reparacion::with(['cliente', 'tecnico'])
            ->where('anulada', false)
            ->whereNotNull('fecha_entrega_real')
            ->whereBetween('fecha_entrega_real', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_entrega_real')
            ->get()
            ->map(fn($r) => [
                'codigo' => $r->codigo_reparacion,
                'fecha_ingreso' => $r->fecha_ingreso->format('d/m/Y'),
                'fecha_entrega' => $r->fecha_entrega_real->format('d/m/Y'),
                'cliente' => $r->cliente ? "{$r->cliente->nombre} {$r->cliente->apellido}" : '-',
                'equipo' => "{$r->equipo_marca} {$r->equipo_modelo}",
                'tecnico' => $r->tecnico->name ?? '-',
                'mano_obra' => number_format($r->costo_mano_obra ?? 0, 2, ',', '.'),
                'total' => number_format($r->total_final ?? 0, 2, ',', '.'),
            ]);
    }

    public function headings(): array
    {
        return ['Código', 'Ingreso', 'Entrega', 'Cliente', 'Equipo', 'Técnico', 'Mano de Obra', 'Total'];
    }

    public function title(): string
    {
        return 'Reparaciones';
    }
}

// ===== HOJA: PAGOS =====
class PagosSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $mes;
    protected $anio;

    public function __construct($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function collection()
    {
        $fechaInicio = Carbon::createFromDate($this->anio, $this->mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($this->anio, $this->mes, 1)->endOfMonth();

        return Pago::with(['cliente', 'cajero', 'medioPago'])
            ->where('anulado', false)
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_pago')
            ->get()
            ->map(fn($p) => [
                'fecha' => $p->fecha_pago->format('d/m/Y'),
                'recibo' => $p->numero_recibo,
                'cliente' => $p->cliente ? "{$p->cliente->nombre} {$p->cliente->apellido}" : '-',
                'medio_pago' => $p->medioPago->descripcion ?? '-',
                'cajero' => $p->cajero->name ?? '-',
                'monto' => number_format($p->monto, 2, ',', '.'),
            ]);
    }

    public function headings(): array
    {
        return ['Fecha', 'Recibo', 'Cliente', 'Medio de Pago', 'Cajero', 'Monto'];
    }

    public function title(): string
    {
        return 'Pagos';
    }
}

// ===== HOJA: COMPRAS =====
class ComprasSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $mes;
    protected $anio;

    public function __construct($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function collection()
    {
        $fechaInicio = Carbon::createFromDate($this->anio, $this->mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($this->anio, $this->mes, 1)->endOfMonth();

        return OrdenCompra::with(['proveedor', 'estado'])
            ->whereIn('estado_id', [4, 5])
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_emision')
            ->get()
            ->map(fn($oc) => [
                'fecha' => $oc->fecha_emision->format('d/m/Y'),
                'numero' => $oc->numero_oc,
                'proveedor' => $oc->proveedor->razon_social ?? $oc->proveedor->nombre ?? '-',
                'estado' => $oc->estado->nombre ?? '-',
                'total' => number_format($oc->total_final, 2, ',', '.'),
            ]);
    }

    public function headings(): array
    {
        return ['Fecha', 'Nº OC', 'Proveedor', 'Estado', 'Total'];
    }

    public function title(): string
    {
        return 'Compras';
    }
}
