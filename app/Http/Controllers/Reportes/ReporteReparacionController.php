<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Reparacion;
use App\Models\User;
use App\Models\Rol; 
use App\Models\EstadoReparacion;
use App\Models\Auditoria;
use App\Exports\ReparacionExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReporteReparacionController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auditoría
        Auditoria::create([
            'accion' => 'CONSULTA',
            'tablaAfectada' => 'reportes',
            'valorNuevo' => 'Reporte de Reparaciones',
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Análisis de eficiencia técnica'
        ]);

        // 2. Filtros
        $fechaDesde = $request->input('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $tecnicoId = $request->input('tecnico_id');
        $estadoId = $request->input('estado_id');

        // 3. Query Base
        $query = Reparacion::with(['cliente', 'tecnico', 'estado', 'marca', 'modelo'])
            ->whereBetween('fecha_ingreso', [
                Carbon::parse($fechaDesde)->startOfDay(), 
                Carbon::parse($fechaHasta)->endOfDay()
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
                ]
            ]
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
                ]
            ]
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
                'ingresos' => $ingresosTecnicos
            ],
            'graficos' => [
                'estados' => $graficoEstados,
                'tecnicos' => $graficoTecnicos
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
                ->map(fn($e) => ['id' => $e->estadoReparacionID, 'nombre' => $e->nombreEstado]),
        ]);
    }

    public function exportar(Request $request)
    {
        Auditoria::create([
            'accion' => 'EXPORTACION',
            'tablaAfectada' => 'reportes',
            'usuarioID' => Auth::id(),
            'motivo' => 'Descarga Excel Reparaciones'
        ]);

        return Excel::download(new ReparacionExport($request->all()), 'reporte_reparaciones.xlsx');
    }
}