<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php $headerColor = '#4F46E5'; @endphp
    @include('pdf.reportes._styles')
    <title>Reporte de Ventas</title>
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
                <div class="report-title">Reporte de Ventas</div>
                <div class="report-period">{{ $periodo }}</div>
                <div class="report-date">Generado: {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-label">Total Facturado</div>
            <div class="kpi-value accent">${{ number_format($kpis['total_ingresos'], 2, ',', '.') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Cantidad de Ventas</div>
            <div class="kpi-value">{{ $kpis['cantidad_ventas'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Venta Promedio</div>
            <div class="kpi-value">${{ number_format($kpis['ticket_promedio'], 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="section-title">Detalle de Ventas</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Estado</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $venta)
            <tr>
                <td>{{ $venta->venta_id }}</td>
                <td>{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
                <td>{{ $venta->cliente->nombre ?? '' }} {{ $venta->cliente->apellido ?? '' }}</td>
                <td>{{ $venta->vendedor->name ?? 'Sistema' }}</td>
                <td>
                    <span class="badge {{ ($venta->estado->nombreEstado ?? '') === 'Completada' ? 'badge-success' : (($venta->estado->nombreEstado ?? '') === 'Anulada' ? 'badge-danger' : 'badge-warning') }}">
                        {{ $venta->estado->nombreEstado ?? 'N/A' }}
                    </span>
                </td>
                <td class="text-right font-bold">${{ number_format($venta->total, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No se encontraron ventas para los filtros aplicados.</td></tr>
            @endforelse
        </tbody>
        @if($ventas->count() > 0)
        <tfoot>
            <tr>
                <td colspan="5">TOTAL ({{ $ventas->count() }} registros)</td>
                <td class="text-right">${{ number_format($ventas->sum('total'), 2, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="table-summary">Reporte generado con {{ $ventas->count() }} registros · Período: {{ $periodo }}</div>
</body>
</html>
