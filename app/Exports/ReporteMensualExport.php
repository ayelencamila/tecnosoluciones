<?php

namespace App\Exports;

use App\Models\Venta;
use App\Models\Pago;
use App\Models\Reparacion;
use App\Models\OrdenCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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

        $totalCompras = OrdenCompra::whereIn('estado_id', [4, 5])
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->sum('total_final');

        $totalIngresos = $totalVentas + $totalReparaciones;
        $balance = $totalIngresos - $totalCompras;

        return collect([
            ['INGRESOS', ''],
            ['Ventas', number_format($totalVentas, 2, ',', '.')],
            ['Reparaciones', number_format($totalReparaciones, 2, ',', '.')],
            ['Total Ingresos', number_format($totalIngresos, 2, ',', '.')],
            ['', ''],
            ['EGRESOS', ''],
            ['Compras', number_format($totalCompras, 2, ',', '.')],
            ['Total Egresos', number_format($totalCompras, 2, ',', '.')],
            ['', ''],
            ['RESULTADO', ''],
            ['Balance del Mes', number_format($balance, 2, ',', '.')],
            ['', ''],
            ['COBRANZAS', ''],
            ['Total Pagos Recibidos', number_format($totalPagos, 2, ',', '.')],
        ]);
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
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true]],
            7 => ['font' => ['bold' => true]],
            11 => ['font' => ['bold' => true]],
            14 => ['font' => ['bold' => true]],
        ];
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
