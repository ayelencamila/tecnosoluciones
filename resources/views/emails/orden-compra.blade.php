<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra {{ $orden->numero_oc }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .oc-number {
            font-size: 18px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .content {
            background: #f9fafb;
            padding: 25px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .info-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2563eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            background: #2563eb;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 12px;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        tr:nth-child(even) {
            background: #f3f4f6;
        }
        .total-row {
            background: #dbeafe !important;
            font-weight: bold;
        }
        .total-row td {
            border-bottom: none;
            font-size: 14px;
        }
        .highlight {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 0 6px 6px 0;
        }
        .footer {
            background: #1f2937;
            color: #9ca3af;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 12px;
        }
        .footer strong {
            color: white;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 15px 0;
        }
        .observaciones {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        .observaciones-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 ORDEN DE COMPRA</h1>
        <div class="oc-number">{{ $orden->numero_oc }}</div>
    </div>

    <div class="content">
        <p class="greeting">
            Estimados <strong>{{ $proveedor->razon_social }}</strong>,
        </p>

        <p>
            Adjuntamos la Orden de Compra <strong>{{ $orden->numero_oc }}</strong> 
            emitida el {{ $orden->fecha_emision->format('d/m/Y') }} por TecnoSoluciones.
        </p>

        <div class="info-box">
            <div class="info-box-title">📦 Detalle de Productos</div>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align: center;">Cant.</th>
                        <th style="text-align: right;">P. Unit.</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalles as $detalle)
                    <tr>
                        <td>
                            {{ $detalle->producto->nombre ?? 'Producto #' . $detalle->producto_id }}
                            @if($detalle->producto && $detalle->producto->codigo)
                                <br><small style="color: #6b7280;">Cód: {{ $detalle->producto->codigo }}</small>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $detalle->cantidad_pedida }}</td>
                        <td style="text-align: right;">${{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
                        <td style="text-align: right;">${{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">${{ number_format($orden->total_final, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($orden->observaciones)
        <div class="observaciones">
            <div class="observaciones-title">📝 Observaciones:</div>
            <p style="margin: 0; color: #78350f;">{{ $orden->observaciones }}</p>
        </div>
        @endif

        <div class="highlight">
            <strong>📎 PDF Adjunto:</strong> Encontrará el documento completo de la Orden de Compra 
            adjunto a este correo en formato PDF.
        </div>

        <p style="margin-top: 20px;">
            Por favor, confirme la recepción de esta orden y comuníquese con nosotros 
            para coordinar los detalles de entrega.
        </p>

        <p>
            <strong>Datos de contacto:</strong><br>
            📧 compras@tecnosoluciones.com<br>
            📞 +54 11 1234-5678
        </p>
    </div>

    <div class="footer">
        <p>
            <strong>TecnoSoluciones S.R.L.</strong><br>
            Sistema de Gestión de Compras
        </p>
        <p style="margin-top: 10px; font-size: 11px;">
            Este es un correo automático. Por favor no responda directamente a este mensaje.<br>
            Documento interno - No válido como factura.
        </p>
    </div>
</body>
</html>
