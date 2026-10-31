<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ReparacionExport;
use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\EstadoReparacion;
use App\Models\Reparacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReporteReparacionController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auditoría
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tabla_afectada' => 'reportes',
            'detalles' => 'Reporte de Reparaciones',
            'usuarioID' => Auth::id(),
            'motivo' => 'Análisis de eficiencia técnica',
        ]);

        // 2. Filtros
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $tecnicoId = $this->tecnicoIdEfectivo($request);
        $estadoId = $request->input('estado_id');

        // 3. Query Base
        $query = Reparacion::with(['cliente', 'tecnico', 'estado', 'marca', 'modelo'])
            ->whereBetween('fecha_ingreso', [
                Carbon::parse($fechaDesde)->startOfDay(),
                Carbon::parse($fechaHasta)->endOfDay(),
            ]);

        if ($tecnicoId) {
            $query->where('tecnico_id', $tecnicoId);
        }
        if ($estadoId) {
            $query->where('estado_reparacion_id', $estadoId);
        }

        // 4. Datos Tabla
        $reparaciones = (clone $query)->latest('fecha_ingreso')->paginate(15)->withQueryString();

        // 5. KPIs
        $totalReparaciones = (clone $query)->count();
        $finalizadas = (clone $query)->whereNotNull('fecha_entrega_real')->count();
        $tasaExito = $totalReparaciones > 0 ? ($finalizadas / $totalReparaciones) * 100 : 0;
        $ingresosTecnicos = (clone $query)->sum('total_final');

        // 6. Gráfico 1: Estado de Reparaciones (Optimizado con Join)
        $porEstado = (clone $query)
            ->join('estados_reparacion', 'reparaciones.estado_reparacion_id', '=', 'estados_reparacion.estadoReparacionID')
            ->select('estados_reparacion.nombreEstado as nombre', DB::raw('count(*) as total'))
            ->groupBy('estados_reparacion.nombreEstado')
            ->get();

        $graficoEstados = [
            'labels' => $porEstado->pluck('nombre'),
            'datasets' => [
                [
                    'data' => $porEstado->pluck('total'),
                    'backgroundColor' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#6366F1', '#8B5CF6'],
                ],
            ],
        ];

        // 7. Gráfico 2: Reparaciones por Técnico (Optimizado con Join)
        $porTecnico = (clone $query)
            ->whereNotNull('tecnico_id')
            ->join('users', 'reparaciones.tecnico_id', '=', 'users.id')
            ->select('users.name as nombre', DB::raw('count(*) as total'))
            ->groupBy('users.name')
            ->get();

        $graficoTecnicos = [
            'labels' => $porTecnico->pluck('nombre'),
            'datasets' => [
                [
                    'label' => 'Asignaciones',
                    'data' => $porTecnico->pluck('total'),
                    'backgroundColor' => '#0EA5E9',
                ],
            ],
        ];

        // Obtener técnico seleccionado si existe (para mostrar en el buscador)
        $tecnicoSeleccionado = $tecnicoId
            ? User::with('rol')->find($tecnicoId, ['id', 'name', 'rol_id'])
            : null;

        return Inertia::render('Reportes/Reparaciones/Index', [
            'reparaciones' => $reparaciones,
            'kpis' => [
                'total' => $totalReparaciones,
                'finalizadas' => $finalizadas,
                'tasa_exito' => round($tasaExito, 1),
                'ingresos' => $ingresosTecnicos,
            ],
            'graficos' => [
                'estados' => $graficoEstados,
                'tecnicos' => $graficoTecnicos,
            ],
            'filters' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'tecnico_id' => $tecnicoId,
                'estado_id' => $estadoId,
            ],
            'tecnicoSeleccionado' => $tecnicoSeleccionado,
            'estados' => EstadoReparacion::orderBy('estadoReparacionID')
                ->get()
                ->map(fn ($e) => ['id' => $e->estadoReparacionID, 'nombre' => $e->nombreEstado]),
        ]);
    }

    public function exportar(Request $request)
    {
        $formato = $request->input('formato', 'xlsx');
        $timestamp = now()->format('Ymd_His');

        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tabla_afectada' => 'reportes',
            'usuarioID' => Auth::id(),
            'motivo' => "Exportación {$formato} Reparaciones",
        ]);

        switch ($formato) {
            case 'pdf':
                $data = $this->getDataForPdf($request);
                $pdf = Pdf::loadView('pdf.reportes.reparaciones', $data)->setPaper('a4', 'landscape');

                return $pdf->download("reporte_reparaciones_{$timestamp}.pdf");

            case 'csv':
                return Excel::download(
                    new ReparacionExport($this->filtrosRestringidos($request)),
                    "reporte_reparaciones_{$timestamp}.csv",
                    \Maatwebsite\Excel\Excel::CSV
                );

            default:
                return Excel::download(
                    new ReparacionExport($this->filtrosRestringidos($request)),
                    "reporte_reparaciones_{$timestamp}.xlsx"
                );
        }
    }

    private function getDataForPdf(Request $request): array
    {
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $tecnicoId = $this->tecnicoIdEfectivo($request);
        $estadoId = $request->input('estado_id');

        $query = Reparacion::with(['cliente', 'tecnico', 'estado', 'marca', 'modelo'])
            ->whereBetween('fecha_ingreso', [
                Carbon::parse($fechaDesde)->startOfDay(),
                Carbon::parse($fechaHasta)->endOfDay(),
            ]);

        if ($tecnicoId) {
            $query->where('tecnico_id', $tecnicoId);
        }
        if ($estadoId) {
            $query->where('estado_reparacion_id', $estadoId);
        }

        $reparaciones = $query->latest('fecha_ingreso')->get();
        $totalReparaciones = $reparaciones->count();
        $finalizadas = $reparaciones->whereNotNull('fecha_entrega_real')->count();
        $tasaExito = $totalReparaciones > 0 ? ($finalizadas / $totalReparaciones) * 100 : 0;
        $ingresos = $reparaciones->sum('total_final');

        return [
            'periodo' => Carbon::parse($fechaDesde)->format('d/m/Y').' - '.Carbon::parse($fechaHasta)->format('d/m/Y'),
            'reparaciones' => $reparaciones,
            'kpis' => [
                'total' => $totalReparaciones,
                'finalizadas' => $finalizadas,
                'tasa_exito' => round($tasaExito, 1),
                'ingresos' => $ingresos,
            ],
        ];
    }

    /**
     * Técnico por el que se filtra el reporte, respetando la restricción del
     * módulo de reparaciones.
     *
     * Quien no tiene `reparaciones.ver_todas` ve solo las propias en el
     * listado; sin esto, el mismo dato quedaría disponible agregado en el
     * reporte, que es la puerta de atrás del módulo. El valor se FUERZA: no
     * alcanza con ignorar el `tecnico_id` recibido, hay que reemplazarlo,
     * porque si no al no mandar ninguno se verían todas.
     *
     * Para quien sí tiene el permiso, el filtro sigue siendo opcional y lo
     * elige desde la pantalla.
     */
    private function tecnicoIdEfectivo(Request $request): ?int
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->puedeVerTodasLasReparaciones()) {
            return $usuario->id;
        }

        $tecnicoId = $request->input('tecnico_id');

        return $tecnicoId ? (int) $tecnicoId : null;
    }

    /**
     * Filtros de exportación con el técnico ya restringido.
     *
     * El export arma su propia consulta a partir de este arreglo, así que la
     * restricción tiene que viajar adentro: de nada sirve acotar la pantalla
     * si el archivo descargado trae el taller completo.
     */
    private function filtrosRestringidos(Request $request): array
    {
        $filtros = $request->all();
        $filtros['tecnico_id'] = $this->tecnicoIdEfectivo($request);

        return $filtros;
    }
}
