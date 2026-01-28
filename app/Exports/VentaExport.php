<?php

namespace App\Exports;

use App\Models\Venta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class VentaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filtros;

    public function __construct($filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = Venta::with(['cliente', 'usuario', 'pagos.medioPago'])
            ->where('estado', '!=', 'Anulada'); 

        // Filtro Fechas
        if (!empty($this->filtros['fecha_desde']) && !empty($this->filtros['fecha_hasta'])) {
            $desde = Carbon::parse($this->filtros['fecha_desde'])->startOfDay();
            $hasta = Carbon::parse($this->filtros['fecha_hasta'])->endOfDay();
            $query->whereBetween('created_at', [$desde, $hasta]);
        }

        // Filtro Cliente
        if (!empty($this->filtros['cliente_id'])) {
            $query->where('cliente_id', $this->filtros['cliente_id']);
        }

        return $query->latest()->get();
    }

    public function map($venta): array
    {
        // Obtener medios de pago concatenados
        $medios = $venta->pagos->map(fn($p) => $p->medioPago->nombre ?? '-')->unique()->implode(', ');

        return [
            $venta->id,
            $venta->created_at->format('d/m/Y H:i'),
            $venta->cliente->nombre . ' ' . $venta->cliente->apellido,
            $venta->usuario->name ?? 'Sistema',
            $medios ?: 'Cta. Cte.',
            $venta->items_count ?? $venta->detalles->count(),
            $venta->total,
            $venta->estado,
        ];
    }

    public function headings(): array
    {
        return [
            'ID Venta',
            'Fecha',
            'Cliente',
            'Vendedor',
            'Medio Pago',
            'Items',
            'Total ($)',
            'Estado'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}