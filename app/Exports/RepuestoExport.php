<?php

namespace App\Exports;

use App\Models\DetalleReparacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class RepuestoExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filtros;

    public function __construct($filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = DetalleReparacion::with(['producto.categoria', 'reparacion.tecnico', 'reparacion.cliente']);

        if (!empty($this->filtros['fecha_desde']) && !empty($this->filtros['fecha_hasta'])) {
            $desde = Carbon::parse($this->filtros['fecha_desde'])->startOfDay();
            $hasta = Carbon::parse($this->filtros['fecha_hasta'])->endOfDay();
            $query->whereHas('reparacion', fn($q) => $q->whereBetween('fecha_ingreso', [$desde, $hasta]));
        }

        if (!empty($this->filtros['tecnico_id'])) {
            $query->whereHas('reparacion', fn($q) => $q->where('tecnico_id', $this->filtros['tecnico_id']));
        }

        return $query->latest('created_at')->get();
    }

    public function map($detalle): array
    {
        return [
            $detalle->reparacion->codigo_reparacion ?? $detalle->reparacion_id,
            $detalle->reparacion->fecha_ingreso?->format('d/m/Y') ?? '-',
            $detalle->producto->nombre ?? 'Producto eliminado',
            $detalle->producto->codigo ?? '---',
            $detalle->producto->categoria->nombre ?? '-',
            $detalle->cantidad,
            number_format($detalle->precio_unitario, 2),
            number_format($detalle->subtotal, 2),
            $detalle->reparacion->tecnico->name ?? 'Sin asignar',
            $detalle->reparacion->cliente
                ? ($detalle->reparacion->cliente->nombre . ' ' . $detalle->reparacion->cliente->apellido)
                : 'Sin cliente',
        ];
    }

    public function headings(): array
    {
        return [
            'Cód. Reparación',
            'Fecha Ingreso',
            'Repuesto',
            'Código Producto',
            'Categoría',
            'Cantidad',
            'Precio Unit. ($)',
            'Subtotal ($)',
            'Técnico',
            'Cliente',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
