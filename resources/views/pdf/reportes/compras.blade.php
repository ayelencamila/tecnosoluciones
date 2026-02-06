<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php $headerColor = '#D97706'; @endphp
    @include('pdf.reportes._styles')
    <title>Reporte de Compras</title>
</head>
<body>
    <div class="watermark">DOCUMENTO INTERNO</div>
    <div class="documento-no-fiscal">Documento interno — no fiscal · TecnoSoluciones · Generado: {{ now()->format('d/m/Y H:i') }}</div>

    <div class="report-header">
        <div class="header-top">
            <div class="logo-section">
                <div class="company-name">TecnoSoluciones</div>
                <div class="company-tagline">Venta y reparación de productos tecnológicos</div>
            </div>
            <div class="report-title-section">
                <div class="report-title">Reporte de Compras</div>
                <div class="report-period">{{ $periodo }}</div>
                <div class="report-date">Generado: {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-label">Total Invertido</div>
            <div class="kpi-value accent">${{ number_format($kpis['total_gastado'], 2, ',', '.') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Órdenes de Compra</div>
            <div class="kpi-value">{{ $kpis['cantidad_ordenes'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Promedio por Orden</div>
            <div class="kpi-value">${{ number_format($kpis['promedio_orden'], 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="section-title">Detalle de Órdenes de Compra</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>N° Orden</th>
                <th>Fecha Emisión</th>
                <th>Proveedor</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Total</th>
                <th>Generado Por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ordenes as $orden)
            <tr>
                <td>{{ $orden->numero_oc ?? $orden->id }}</td>
                <td>{{ $orden->fecha_emision?->format('d/m/Y') ?? $orden->created_at->format('d/m/Y') }}</td>
                <td>{{ $orden->proveedor->razon_social ?? 'Desconocido' }}</td>
                <td class="text-center">
                    <span class="badge badge-info">{{ $orden->estado->nombre ?? 'N/A' }}</span>
                </td>
                <td class="text-right font-bold">${{ number_format($orden->total_final ?? 0, 2, ',', '.') }}</td>
                <td>{{ $orden->usuario->name ?? 'Sistema' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No se encontraron órdenes de compra.</td></tr>
            @endforelse
        </tbody>
        @if($ordenes->count() > 0)
        <tfoot>
            <tr>
                <td colspan="4">TOTAL ({{ $ordenes->count() }} órdenes)</td>
                <td class="text-right">${{ number_format($ordenes->sum('total_final'), 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="table-summary">Reporte generado con {{ $ordenes->count() }} registros · Período: {{ $periodo }}</div>
</body>
</html>
