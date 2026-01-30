<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra {{ $orden->numero_oc }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-top {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }

        .company-tagline {
            font-size: 10px;
            color: #6b7280;
        }

        .oc-section {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }

        .oc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1f2937;
        }

        .oc-number {
            font-size: 16px;
            color: #2563eb;
            font-weight: bold;
        }

        .oc-date {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-box {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
        }

        .info-box:first-child {
            margin-right: 4%;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .info-box p {
            margin-bottom: 3px;
        }

        .info-box strong {
            color: #1f2937;
        }

        .table-container {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #2563eb;
        }

        th {
            color: white;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
        }

        th:last-child,
        th:nth-child(3),
        th:nth-child(4) {
            text-align: right;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }

        td:last-child,
        td:nth-child(3),
        td:nth-child(4) {
            text-align: right;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 20px;
        }

        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }

        .totals-label {
            display: table-cell;
            text-align: left;
            color: #6b7280;
        }

        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }

        .totals-row.grand-total {
            border-top: 2px solid #2563eb;
            margin-top: 5px;
            padding-top: 10px;
        }

        .totals-row.grand-total .totals-label,
        .totals-row.grand-total .totals-value {
            font-size: 14px;
            color: #1f2937;
        }

        .observations {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .observations-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 8px;
        }

        .observations-content {
            color: #78350f;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 20px;
            font-size: 10px;
            color: #9ca3af;
        }

        .footer-row {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 50%;
        }

        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 40px;
        }

        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding-top: 50px;
        }

        .signature-line {
            border-top: 1px solid #374151;
            padding-top: 5px;
            font-size: 10px;
            color: #6b7280;
        }

        .estado-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .estado-borrador { background: #e5e7eb; color: #374151; }
        .estado-enviada { background: #dbeafe; color: #1e40af; }
        .estado-confirmada { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <!-- ENCABEZADO -->
    <div class="header">
        <div class="header-top">
            <div class="logo-section">
                <div class="company-name">TecnoSoluciones</div>
                <div class="company-tagline">Sistema de Gestión de Compras</div>
            </div>
            <div class="oc-section">
                <div class="oc-title">ORDEN DE COMPRA</div>
                <div class="oc-number">{{ $orden->numero_oc }}</div>
                <div class="oc-date">
                    Emitida: {{ $orden->fecha_emision->format('d/m/Y H:i') }}
                    @if($orden->estado)
                        <br>
                        <span class="estado-badge estado-{{ strtolower(str_replace(' ', '-', $orden->estado->nombre)) }}">
                            {{ $orden->estado->nombre }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN DE PROVEEDOR Y EMPRESA -->
    <div class="info-section">
        <div class="info-box">
            <div class="info-box-title">📦 Proveedor</div>
            <p><strong>{{ $orden->proveedor->razon_social }}</strong></p>
            @if($orden->proveedor->cuit)
                <p>CUIT: {{ $orden->proveedor->cuit }}</p>
            @endif
            @if($orden->proveedor->direccion)
                <p>{{ $orden->proveedor->direccion->direccion_completa ?? $orden->proveedor->direccion->calle ?? '' }}</p>
            @endif
            @if($orden->proveedor->telefono)
                <p>Tel: {{ $orden->proveedor->telefono }}</p>
            @endif
            @if($orden->proveedor->email)
                <p>Email: {{ $orden->proveedor->email }}</p>
            @endif
        </div>
        <div class="info-box" style="margin-left: 4%;">
            <div class="info-box-title">🏢 Solicitante</div>
            <p><strong>TecnoSoluciones S.R.L.</strong></p>
            <p>Sistema de Gestión</p>
            @if($orden->usuario)
                <p>Generado por: {{ $orden->usuario->name }}</p>
            @endif
            @if($orden->cotizacionProveedor && $orden->cotizacionProveedor->solicitud)
                <p>Ref. Solicitud: {{ $orden->cotizacionProveedor->solicitud->codigo_solicitud }}</p>
            @endif
        </div>
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 42%;">Producto</th>
                    <th style="width: 15%;">Cantidad</th>
                    <th style="width: 17%;">Precio Unit.</th>
                    <th style="width: 18%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php $subtotalGeneral = 0; @endphp
                @foreach($orden->detalles as $index => $detalle)
                    @php 
                        $subtotal = $detalle->cantidad_pedida * $detalle->precio_unitario;
                        $subtotalGeneral += $subtotal;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            @if($detalle->producto)
                                <strong>{{ $detalle->producto->nombre }}</strong>
                                @if($detalle->producto->codigo)
                                    <br><span style="color: #6b7280; font-size: 10px;">Cód: {{ $detalle->producto->codigo }}</span>
                                @endif
                            @else
                                Producto #{{ $detalle->producto_id }}
                            @endif
                        </td>
                        <td>{{ number_format($detalle->cantidad_pedida, 0) }}</td>
                        <td>${{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
                        <td>${{ number_format($subtotal, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- TOTALES -->
    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Subtotal:</span>
            <span class="totals-value">${{ number_format($subtotalGeneral, 2, ',', '.') }}</span>
        </div>
        <div class="totals-row grand-total">
            <span class="totals-label">TOTAL:</span>
            <span class="totals-value">${{ number_format($orden->total_final, 2, ',', '.') }}</span>
        </div>
    </div>

    <!-- OBSERVACIONES -->
    @if($orden->observaciones)
        <div class="observations">
            <div class="observations-title">Instrucciones / Observaciones</div>
            <div class="observations-content">{{ $orden->observaciones }}</div>
        </div>
    @endif

    <!-- FIRMAS -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">Firma y Sello del Proveedor</div>
        </div>
        <div class="signature-box" style="margin-left: 10%;">
            <div class="signature-line">Autorizado por TecnoSoluciones</div>
        </div>
    </div>

    <!-- PIE DE PÁGINA -->
    <div class="footer">
        <div class="footer-row">
            <div class="footer-left">
                <strong>DOCUMENTO INTERNO - NO VÁLIDO COMO FACTURA</strong><br>
                Orden de compra interna - No posee validez fiscal
            </div>
            <div class="footer-right">
                Impreso: {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>
</body>
</html>
