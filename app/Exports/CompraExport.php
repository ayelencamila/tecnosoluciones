<?php

namespace App\Exports;

use App\Models\OrdenCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CompraExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filtros;

    public function __construct($filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = OrdenCompra::with(['proveedor', 'estado', 'usuario']);

        if (!empty($this->filtros['fecha_desde']) && !empty($this->filtros['fecha_hasta'])) {
            $desde = Carbon::parse($this->filtros['fecha_desde'])->startOfDay();
            $hasta = Carbon::parse($this->filtros['fecha_hasta'])->endOfDay();
            $query->whereBetween('fecha_emision', [$desde, $hasta]);
        }

        if (!empty($this->filtros['proveedor_id'])) {
            $query->where('proveedor_id', $this->filtros['proveedor_id']);
        }

        if (!empty($this->filtros['estado_id'])) {
            $query->where('estado_id', $this->filtros['estado_id']);
        }

        return $query->latest('fecha_emision')->get();
    }

    public function map($orden): array
    {
        return [
            $orden->numero_oc ?? $orden->id,
            $orden->fecha_emision?->format('d/m/Y') ?? $orden->created_at->format('d/m/Y'),
            $orden->proveedor->razon_social ?? 'Desconocido',
            $orden->estado->nombre ?? 'N/A',
            number_format($orden->total_final ?? 0, 2),
            $orden->usuario->name ?? 'Sistema'
        ];
    }

    public function headings(): array
    {
        return [
            'Nro Orden',
            'Fecha Emisión',
            'Proveedor',
            'Estado',
            'Total ($)',
            'Generado Por'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
