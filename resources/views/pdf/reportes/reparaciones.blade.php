<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php $headerColor = '#0891B2'; @endphp
    @include('pdf.reportes._styles')
    <title>Reporte de Reparaciones</title>
</head>
<body>
    <div class="watermark">DOCUMENTO INTERNO</div>
    <div class="documento-no-fiscal">Documento interno — no fiscal · TecnoSoluciones · Generado: {{ now()->format('d/m/Y H:i') }}</div>

    {{-- HEADER --}}
    <div class="report-header">
        <div class="header-top">
            <div class="logo-section">
                <div class="company-name">TecnoSoluciones</div>
                <div class="company-tagline">Venta y reparación de productos tecnológicos</div>
            </div>
            <div class="report-title-section">
                <div class="report-title">Reporte de Reparaciones</div>
                <div class="report-period">{{ $periodo }}</div>
                <div class="report-date">Generado: {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-label">Total Reparaciones</div>
            <div class="kpi-value accent">{{ $kpis['total'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Finalizadas</div>
            <div class="kpi-value positive">{{ $kpis['finalizadas'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Tasa de Éxito</div>
            <div class="kpi-value">{{ $kpis['tasa_exito'] }}%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Ingresos por Servicio</div>
            <div class="kpi-value accent">${{ number_format($kpis['ingresos'], 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="section-title">Detalle de Reparaciones</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha Ingreso</th>
                <th>Cliente</th>
                <th>Equipo</th>
                <th>Técnico</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reparaciones as $rep)
            <tr>
                <td>{{ $rep->codigo_reparacion ?? $rep->reparacionID }}</td>
                <td>{{ $rep->fecha_ingreso->format('d/m/Y') }}</td>
                <td>{{ $rep->cliente->nombre ?? '' }} {{ $rep->cliente->apellido ?? '' }}</td>
                <td>{{ $rep->marca->nombre ?? '' }} {{ $rep->modelo->nombre ?? '' }}</td>
                <td>{{ $rep->tecnico->name ?? 'Sin asignar' }}</td>
                <td class="text-center">
                    <span class="badge badge-info">{{ $rep->estado->nombreEstado ?? 'N/A' }}</span>
                </td>
                <td class="text-right font-bold">${{ number_format($rep->total_final ?? 0, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted">No se encontraron reparaciones.</td></tr>
            @endforelse
        </tbody>
        @if($reparaciones->count() > 0)
        <tfoot>
            <tr>
                <td colspan="6">TOTAL ({{ $reparaciones->count() }} registros)</td>
                <td class="text-right">${{ number_format($reparaciones->sum('total_final'), 2, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="table-summary">Reporte generado con {{ $reparaciones->count() }} registros · Período: {{ $periodo }}</div>
</body>
</html>
