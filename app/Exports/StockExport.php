<?php

namespace App\Exports;

use App\Models\Stock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filtros;

    public function __construct($filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = Stock::with(['producto.categoria', 'producto.marca']);

        if (!empty($this->filtros['bajo_stock'])) {
            $query->whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        }

        return $query->orderBy('productoID', 'asc')->get();
    }

    public function map($stock): array
    {
        $estado = $stock->cantidad_disponible <= $stock->stock_minimo ? 'CRÍTICO' : 'Normal';

        return [
            $stock->producto->codigo ?? '---',
            $stock->producto->nombre ?? 'Producto eliminado',
            $stock->producto->categoria->nombre ?? 'Sin categoría',
            $stock->producto->marca->nombre ?? '-',
            $stock->cantidad_disponible,
            $stock->stock_minimo,
            $estado,
        ];
    }

    public function headings(): array
    {
        return [
            'Código',
            'Producto',
            'Categoría',
            'Marca',
            'Stock Actual',
            'Stock Mínimo',
            'Estado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
