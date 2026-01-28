<?php

namespace App\Exports;

use App\Models\Reparacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ReparacionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filtros;

    public function __construct($filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = Reparacion::with(['cliente', 'tecnico', 'estado', 'marca', 'modelo']);

        if (!empty($this->filtros['fecha_desde']) && !empty($this->filtros['fecha_hasta'])) {
            $desde = Carbon::parse($this->filtros['fecha_desde'])->startOfDay();
            $hasta = Carbon::parse($this->filtros['fecha_hasta'])->endOfDay();
            $query->whereBetween('fecha_ingreso', [$desde, $hasta]);
        }

        if (!empty($this->filtros['tecnico_id'])) {
            $query->where('tecnico_id', $this->filtros['tecnico_id']);
        }

        if (!empty($this->filtros['estado_id'])) {
            $query->where('estado_reparacion_id', $this->filtros['estado_id']);
        }

        return $query->latest('fecha_ingreso')->get();
    }

    public function map($reparacion): array
    {
        // Si tiene fecha_entrega_real, usamos esa. Si no, usamos hoy.
        $inicio = Carbon::parse($reparacion->fecha_ingreso);
        $fin = $reparacion->fecha_entrega_real 
            ? Carbon::parse($reparacion->fecha_entrega_real) 
            : now();
        $dias = $inicio->diffInDays($fin);

        // Equipo: puede ser marca/modelo relación o campos de texto
        $equipo = $reparacion->marca && $reparacion->modelo
            ? $reparacion->marca->nombre . ' ' . $reparacion->modelo->nombre
            : trim($reparacion->equipo_marca . ' ' . $reparacion->equipo_modelo);

        return [
            $reparacion->codigo_reparacion ?? $reparacion->reparacionID,
            $reparacion->fecha_ingreso->format('d/m/Y'),
            $reparacion->cliente 
                ? $reparacion->cliente->nombre . ' ' . $reparacion->cliente->apellido 
                : 'Sin cliente',
            $equipo ?: 'No especificado',
            $reparacion->falla_declarada ?? '-',
            $reparacion->tecnico->name ?? 'Sin asignar',
            $reparacion->estado->nombre ?? 'Desconocido',
            number_format($reparacion->total_final ?? 0, 2),
            $dias . ' días'
        ];
    }

    public function headings(): array
    {
        return [
            'Código',
            'Fecha Ingreso',
            'Cliente',
            'Equipo',
            'Problema',
            'Técnico',
            'Estado Actual',
            'Costo ($)',
            'Tiempo en Taller'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
