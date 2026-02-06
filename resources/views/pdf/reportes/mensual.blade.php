<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php $headerColor = '#7C3AED'; @endphp
    @include('pdf.reportes._styles')
    <style>
        .planilla-section { margin-bottom: 15px; }
        .planilla-header {
            padding: 8px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .planilla-header.entradas { background: #d1fae5; color: #065f46; }
        .planilla-header.salidas { background: #fee2e2; color: #991b1b; }
        .planilla-total { font-weight: bold; font-size: 11px; }
        .planilla-total.entradas { background: #a7f3d0; color: #065f46; }
        .planilla-total.salidas { background: #fecaca; color: #991b1b; }

        .balance-row {
            padding: 12px 10px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
        .balance-positive { background: #dbeafe; color: #1e40af; }
        .balance-negative { background: #ffedd5; color: #9a3412; }
    </style>
    <title>Reporte Mensual Consolidado</title>
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
                <div class="report-title">Estado de Resultados</div>
                <div class="report-period">{{ $periodo['nombre'] }}</div>
                <div class="report-date">{{ $periodo['inicio'] }} al {{ $periodo['fin'] }} · Generado por {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    {{-- PLANILLA ENTRADAS / SALIDAS / BALANCE --}}
    <div class="planilla-section">
        <div class="planilla-header entradas">Entradas</div>
        <table class="data-table" style="margin-bottom: 0;">
            <tbody>
                @foreach($planilla['entradas'] as $item)
                <tr>
                    <td style="width: 50%;">{{ $item['concepto'] }}</td>
                    <td class="text-center" style="width: 25%;">{{ $item['cantidad'] !== null ? $item['cantidad'] . ' operaciones' : '' }}</td>
                    <td class="text-right font-bold" style="width: 25%; color: #059669;">${{ number_format($item['total'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="planilla-total entradas">
                    <td colspan="2">TOTAL ENTRADAS</td>
                    <td class="text-right">${{ number_format($planilla['total_entradas'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="planilla-section">
        <div class="planilla-header salidas">Salidas</div>
        <table class="data-table" style="margin-bottom: 0;">
            <tbody>
                @foreach($planilla['salidas'] as $item)
                <tr>
                    <td style="width: 50%;">{{ $item['concepto'] }}</td>
                    <td class="text-center" style="width: 25%;">{{ $item['cantidad'] !== null ? $item['cantidad'] . ' operaciones' : '' }}</td>
                    <td class="text-right font-bold" style="width: 25%; color: #dc2626;">${{ number_format($item['total'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="planilla-total salidas">
                    <td colspan="2">TOTAL SALIDAS</td>
                    <td class="text-right">${{ number_format($planilla['total_salidas'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- BALANCE --}}
    <div class="balance-row {{ $planilla['balance'] >= 0 ? 'balance-positive' : 'balance-negative' }}">
        BALANCE DEL MES: ${{ number_format($planilla['balance'], 2, ',', '.') }}
        ({{ $planilla['balance'] >= 0 ? 'Positivo' : 'Negativo' }})
    </div>

    {{-- PAGOS RECIBIDOS --}}
    <div style="background: #f9fafb; padding: 8px 10px; margin-top: 10px; border: 1px solid #e5e7eb; font-size: 10px;">
        <strong>Pagos recibidos de clientes:</strong> 
        {{ $planilla['pagos_recibidos']['cantidad'] }} cobros · 
        ${{ number_format($planilla['pagos_recibidos']['total'], 2, ',', '.') }}
    </div>

    {{-- DETALLE DE GASTOS (si hay) --}}
    @if(count($gastosPorCategoria) > 0)
    <div class="section-title" style="margin-top: 20px;">Detalle de Gastos y Pérdidas por Categoría</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Categoría</th>
                <th class="text-center">Tipo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gastosPorCategoria as $gasto)
            <tr>
                <td class="font-bold">{{ $gasto->nombre }}</td>
                <td class="text-center">
                    <span class="badge {{ $gasto->tipo === 'gasto' ? 'badge-info' : 'badge-danger' }}">
                        {{ $gasto->tipo === 'gasto' ? 'Gasto' : 'Pérdida' }}
                    </span>
                </td>
                <td class="text-center">{{ $gasto->cantidad }}</td>
                <td class="text-right font-bold">${{ number_format($gasto->total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="table-summary">Estado de Resultados del período {{ $periodo['nombre'] }}</div>
</body>
</html>
