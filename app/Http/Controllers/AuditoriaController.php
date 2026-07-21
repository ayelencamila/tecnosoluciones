<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consulta y explotación del Log de Auditoría (CU-Consultar Log de Auditoría).
 *
 * Acceso restringido a administradores (gating en routes/web.php).
 *
 * Scoping multi-tenant MANUAL (el proyecto no usa Global Scopes): todas las
 * consultas se filtran por la empresa del usuario autenticado (RNF4).
 *
 * "Auditar al auditor": la consulta y la exportación del log se registran a su
 * vez como eventos de auditoría.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $empresaId = $request->user()->empresa_id;

        // Auditar al auditor: registrar la consulta (evitando spam al paginar).
        $navegandoPaginas = $request->filled('page') && (int) $request->page > 1;
        if (! $navegandoPaginas) {
            $this->registrarConsulta($request, Auditoria::ACCION_CONSULTAR_AUDITORIA, 'Consulta del log de auditoría');
        }

        $auditorias = $this->construirQuery($request, $empresaId)
            ->with(['usuario:id,name,rol_id', 'usuario.rol:rol_id,nombre'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $base = Auditoria::where('empresa_id', $empresaId);

        return Inertia::render('Auditorias/Index', [
            'auditorias' => $auditorias,
            'filtros' => $request->only(['accion', 'tabla', 'usuario', 'fecha_desde', 'fecha_hasta']),
            'accionesDisponibles' => (clone $base)->distinct()->orderBy('accion')->pluck('accion'),
            'tablasDisponibles' => (clone $base)->whereNotNull('tabla_afectada')
                ->distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada'),
        ]);
    }

    /**
     * Exporta el log filtrado a CSV o PDF (salida para evidencia / análisis externo).
     */
    public function exportar(Request $request, string $formato): StreamedResponse|\Illuminate\Http\Response
    {
        $empresaId = $request->user()->empresa_id;

        $this->registrarConsulta(
            $request,
            Auditoria::ACCION_EXPORTAR_AUDITORIA,
            "Exportación del log de auditoría ({$formato})"
        );

        $registros = $this->construirQuery($request, $empresaId)
            ->with(['usuario:id,name,rol_id', 'usuario.rol:rol_id,nombre'])
            ->latest('created_at')
            ->limit(50000) // tope de seguridad
            ->get();

        $timestamp = now()->format('Ymd_His');

        return $formato === 'pdf'
            ? $this->exportarPdf($registros, $timestamp)
            : $this->exportarCsv($registros, $timestamp);
    }

    /**
     * Construye la query base filtrada y scopeada por empresa.
     * Compartida por la consulta en pantalla y la exportación.
     */
    private function construirQuery(Request $request, int $empresaId): Builder
    {
        return Auditoria::where('empresa_id', $empresaId)
            ->when($request->filled('accion'), fn ($q) => $q->where('accion', $request->accion))
            ->when($request->filled('tabla'), fn ($q) => $q->where('tabla_afectada', $request->tabla))
            ->when($request->filled('usuario'), fn ($q) => $q->whereHas(
                'usuario',
                fn ($u) => $u->where('name', 'like', '%'.$request->usuario.'%')
            ))
            ->when($request->filled('fecha_desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->fecha_hasta));
    }

    private function registrarConsulta(Request $request, string $accion, string $motivo): void
    {
        $filtros = array_filter($request->only(['accion', 'tabla', 'usuario', 'fecha_desde', 'fecha_hasta']));

        Auditoria::create([
            'accion' => $accion,
            'tabla_afectada' => 'auditorias',
            'usuarioID' => $request->user()->id,
            'motivo' => $motivo,
            'detalles' => $filtros ? 'Filtros: '.json_encode($filtros, JSON_UNESCAPED_UNICODE) : 'Sin filtros',
        ]);
    }

    private function exportarCsv($registros, string $timestamp): StreamedResponse
    {
        $columnas = ['Fecha/Hora', 'Usuario', 'Rol', 'Acción', 'Módulo', 'Registro', 'IP', 'Motivo', 'Detalle', 'Datos anteriores', 'Datos nuevos'];

        return response()->streamDownload(function () use ($registros, $columnas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para acentos en Excel
            fputcsv($out, $columnas);

            foreach ($registros as $r) {
                fputcsv($out, [
                    optional($r->created_at)->format('d/m/Y H:i:s'),
                    $r->usuario->name ?? 'Sistema',
                    $r->usuario->rol->nombre ?? '',
                    $r->accion,
                    $r->tabla_afectada ?? '',
                    $r->registro_id ?? '',
                    $r->ip ?? '',
                    $r->motivo ?? '',
                    $r->detalles ?? '',
                    $this->datosParaExport($r->datos_anteriores),
                    $this->datosParaExport($r->datos_nuevos),
                ]);
            }
            fclose($out);
        }, "auditoria_{$timestamp}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportarPdf($registros, string $timestamp): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.auditoria', [
            'registros' => $registros,
            'generado' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("auditoria_{$timestamp}.pdf");
    }

    /**
     * Serializa datos JSON para exportación, enmascarando campos sensibles.
     * Defensa para filas antiguas: los registros nuevos ya se guardan enmascarados.
     */
    private function datosParaExport(?array $datos): string
    {
        if (empty($datos)) {
            return '';
        }

        return json_encode(Auditoria::enmascararSensibles($datos), JSON_UNESCAPED_UNICODE);
    }
}
