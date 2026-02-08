<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php $headerColor = '#BE185D'; @endphp
    @include('pdf.reportes._styles')
    <title>Reporte de Uso de Repuestos</title>
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
                <div class="report-title">Uso de Repuestos</div>
                <div class="report-period">{{ $periodo }}</div>
                <div class="report-date">Generado: {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-label">Total Unidades Usadas</div>
            <div class="kpi-value accent">{{ number_format($kpis['total_unidades']) }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Costo Total Repuestos</div>
            <div class="kpi-value negative">${{ number_format($kpis['costo_total'], 2, ',', '.') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Repuestos Distintos</div>
            <div class="kpi-value">{{ $kpis['repuestos_distintos'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Reparaciones con Repuestos</div>
            <div class="kpi-value">{{ $kpis['reparaciones_con_repuestos'] }}</div>
        </div>
    </div>

    {{-- TOP REPUESTOS --}}
    <div class="section-title">Top 15 Repuestos Más Utilizados</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Repuesto</th>
                <th>Código</th>
                <th class="text-center">Cantidad Total</th>
                <th class="text-right">Costo Unitario Prom.</th>
                <th class="text-right">Costo Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topRepuestos as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="font-bold">{{ $item->nombre }}</td>
                <td>{{ $item->codigo ?? '---' }}</td>
                <td class="text-center font-bold">{{ $item->total_cantidad }}</td>
                <td class="text-right">${{ number_format($item->precio_promedio, 2, ',', '.') }}</td>
                <td class="text-right font-bold">${{ number_format($item->costo_total, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No se encontraron repuestos usados en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- USO POR TÉCNICO --}}
    @if(count($usoPorTecnico) > 0)
    <div class="section-title">Consumo de Repuestos por Técnico</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Técnico</th>
                <th class="text-center">Reparaciones</th>
                <th class="text-center">Unidades Usadas</th>
                <th class="text-right">Costo Total</th>
                <th class="text-right">Costo Prom. / Reparación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usoPorTecnico as $tecnico)
            <tr>
                <td class="font-bold">{{ $tecnico->nombre }}</td>
                <td class="text-center">{{ $tecnico->reparaciones }}</td>
                <td class="text-center">{{ $tecnico->total_unidades }}</td>
                <td class="text-right font-bold">${{ number_format($tecnico->costo_total, 2, ',', '.') }}</td>
                <td class="text-right">
                    ${{ $tecnico->reparaciones > 0 ? number_format($tecnico->costo_total / $tecnico->reparaciones, 2, ',', '.') : '0,00' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="table-summary">Período: {{ $periodo }} · {{ $kpis['total_unidades'] }} unidades en {{ $kpis['reparaciones_con_repuestos'] }} reparaciones</div>
</body>
</html>
