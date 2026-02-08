<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php $headerColor = '#059669'; @endphp
    @include('pdf.reportes._styles')
    <title>Reporte de Stock</title>
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
                <div class="report-title">Reporte de Stock</div>
                <div class="report-period">Corte: {{ now()->format('d/m/Y H:i') }}</div>
                <div class="report-date">Generado por {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-label">Total Unidades</div>
            <div class="kpi-value accent">{{ number_format($kpis['total_unidades']) }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Productos Críticos</div>
            <div class="kpi-value negative">{{ $kpis['productos_criticos'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Estado General</div>
            <div class="kpi-value {{ $kpis['productos_criticos'] > 0 ? 'negative' : 'positive' }}">
                {{ $kpis['productos_criticos'] > 0 ? 'Requiere Atención' : 'Óptimo' }}
            </div>
        </div>
    </div>

    <div class="section-title">Inventario Detallado</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="text-center">Stock Actual</th>
                <th class="text-center">Stock Mínimo</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stocks as $stock)
            <tr>
                <td>{{ $stock->producto->codigo ?? '---' }}</td>
                <td class="font-bold">{{ $stock->producto->nombre ?? 'Producto eliminado' }}</td>
                <td>{{ $stock->producto->categoria->nombre ?? 'Sin categoría' }}</td>
                <td class="text-center font-bold" style="color: {{ $stock->cantidad_disponible <= $stock->stock_minimo ? '#dc2626' : '#059669' }}">
                    {{ $stock->cantidad_disponible }}
                </td>
                <td class="text-center">{{ $stock->stock_minimo }}</td>
                <td class="text-center">
                    @if($stock->cantidad_disponible <= $stock->stock_minimo)
                        <span class="badge badge-danger">CRÍTICO</span>
                    @else
                        <span class="badge badge-success">Normal</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No se encontraron productos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="table-summary">{{ $stocks->count() }} productos listados · Corte al {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
