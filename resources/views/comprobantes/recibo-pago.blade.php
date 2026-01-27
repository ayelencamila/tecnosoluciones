<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago - {{ $recibo['numero'] }}</title>
    <style>
        /* === DISEÑO SIGUIENDO LINEAMIENTOS DE KENDALL === */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 20mm;
        }
        
        /* Información CONSTANTE - Encabezado de la empresa */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11px;
            margin: 2px 0;
        }
        
        /* Separación visual (Kendall: líneas en blanco o recuadros) */
        .section {
            margin-bottom: 15px;
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        
        /* Tipo de documento prominente */
        .document-type {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            background: #000;
            color: #fff;
            padding: 8px;
            margin: 10px 0;
            letter-spacing: 2px;
        }
        
        /* ALERTA LEGAL: Documento no fiscal (Kendall: información precisa) */
        .legal-notice {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            color: #d00;
            border: 2px solid #d00;
            padding: 8px;
            margin: 15px 0;
            background: #ffe0e0;
        }
        
        /* Información en columnas (Kendall: alineación correcta) */
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .label {
            font-weight: bold;
            width: 40%;
        }
        
        .value {
            width: 58%;
            text-align: right;
        }
        
        /* Tabla de imputaciones (Kendall: alineación de columnas) */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        table th {
            background: #333;
            color: #fff;
            padding: 6px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        table th.text-right,
        table td.text-right {
            text-align: right; /* Kendall: montos alineados a la derecha */
        }
        
        table td {
            border-bottom: 1px solid #ddd;
            padding: 5px;
            font-size: 11px;
        }
        
        table tr:last-child td {
            border-bottom: 2px solid #000;
        }
        
        /* Totales destacados (Kendall: subtotales útiles) */
        .totales {
            margin-top: 15px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .total-principal {
            font-size: 16px;
            background: #f0f0f0;
            padding: 8px;
            border: 2px solid #000;
        }
        
        /* Anticipo destacado */
        .anticipo-box {
            background: #fffacd;
            border: 2px dashed #f90;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
        }
        
        .anticipo-box .amount {
            font-size: 18px;
            font-weight: bold;
            color: #f60;
        }
        
        /* Estado ANULADO */
        .anulado-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(255, 0, 0, 0.2);
            z-index: 1000;
            pointer-events: none;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px dashed #999;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        /* Optimización para impresión */
        @media print {
            body {
                padding: 10mm;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <!-- Marca de agua ANULADO si corresponde (Kendall: claridad visual) -->
    @if($es_anulado)
        <div class="anulado-watermark">ANULADO</div>
    @endif
    
    <!-- INFORMACIÓN CONSTANTE: Datos de la empresa -->
    <div class="header">
        <h1>{{ $empresa['nombre'] }}</h1>
        @if($empresa['direccion'])
            <p>{{ $empresa['direccion'] }}</p>
        @endif
        @if($empresa['telefono'])
            <p>Tel: {{ $empresa['telefono'] }}</p>
        @endif
        @if($empresa['email'])
            <p>Email: {{ $empresa['email'] }}</p>
        @endif
        @if($empresa['cuit'])
            <p>CUIT: {{ $empresa['cuit'] }}</p>
        @endif
    </div>
    
    <!-- Tipo de documento (Kendall: propósito claro) -->
    <div class="document-type">
        RECIBO DE PAGO - COMPROBANTE INTERNO
    </div>
    
    <!-- ALERTA LEGAL: No válido como factura (Riesgo detectado por Kendall) -->
    <div class="legal-notice">
        ⚠️ DOCUMENTO NO VÁLIDO COMO FACTURA<br>
        COMPROBANTE INTERNO NO FISCAL
    </div>
    
    <!-- INFORMACIÓN VARIABLE: Datos del recibo -->
    <div class="section">
        <div class="section-title">Datos del Recibo</div>
        <div class="row">
            <span class="label">N° de Recibo:</span>
            <span class="value">{{ $recibo['numero'] }}</span>
        </div>
        <div class="row">
            <span class="label">Fecha y Hora:</span>
            <span class="value">{{ $recibo['fecha'] }}</span>
        </div>
        <div class="row">
            <span class="label">Estado:</span>
            <span class="value">{{ $recibo['estado'] }}</span>
        </div>
        <div class="row">
            <span class="label">Cajero:</span>
            <span class="value">{{ $pago['cajero'] }}</span>
        </div>
    </div>
    
    <!-- Cliente (Kendall: información comprensible) -->
    <div class="section">
        <div class="section-title">Cliente</div>
        <div class="row">
            <span class="label">Nombre:</span>
            <span class="value">{{ $cliente['nombre_completo'] }}</span>
        </div>
        @if($cliente['dni'])
            <div class="row">
                <span class="label">DNI:</span>
                <span class="value">{{ $cliente['dni'] }}</span>
            </div>
        @endif
        @if($cliente['domicilio'])
            <div class="row">
                <span class="label">Domicilio:</span>
                <span class="value">{{ $cliente['domicilio'] }}</span>
            </div>
        @endif
    </div>
    
    <!-- Detalles del Pago -->
    <div class="section">
        <div class="section-title">Detalle del Pago</div>
        <div class="row">
            <span class="label">Medio de Pago:</span>
            <span class="value">{{ $pago['medio_pago'] }}</span>
        </div>
        <div class="row">
            <span class="label">Monto Total Recibido:</span>
            <span class="value">${{ number_format($pago['monto_total'], 2, ',', '.') }}</span>
        </div>
        @if($pago['observaciones'])
            <div class="row">
                <span class="label">Observaciones:</span>
                <span class="value">{{ $pago['observaciones'] }}</span>
            </div>
        @endif
    </div>
    
    <!-- Imputación a Documentos (si existe) -->
    @if(count($imputaciones) > 0)
        <div class="section">
            <div class="section-title">Imputación a Documentos Pendientes</div>
            <table>
                <thead>
                    <tr>
                        <th>N° Comprobante</th>
                        <th>Fecha Venta</th>
                        <th class="text-right">Total Venta</th>
                        <th class="text-right">Monto Imputado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($imputaciones as $imp)
                        <tr>
                            <td>{{ $imp['numero_comprobante'] }}</td>
                            <td>{{ $imp['fecha_venta'] }}</td>
                            <td class="text-right">${{ number_format($imp['total_venta'], 2, ',', '.') }}</td>
                            <td class="text-right">${{ number_format($imp['monto_imputado'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    
    <!-- Totales (Kendall: subtotales útiles con alineación correcta) -->
    <div class="totales">
        @if(count($imputaciones) > 0)
            <div class="total-row">
                <span>TOTAL IMPUTADO A DEUDAS:</span>
                <span>${{ number_format($totales['total_imputado'], 2, ',', '.') }}</span>
            </div>
        @endif
        
        <div class="total-row total-principal">
            <span>MONTO TOTAL RECIBIDO:</span>
            <span>${{ number_format($pago['monto_total'], 2, ',', '.') }}</span>
        </div>
        
        <!-- Anticipo (si existe) -->
        @if($totales['anticipo_generado'] > 0)
            <div class="anticipo-box">
                <div style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">
                    💰 ANTICIPO GENERADO
                </div>
                <div class="amount">
                    ${{ number_format($totales['anticipo_generado'], 2, ',', '.') }}
                </div>
                <div style="font-size: 10px; margin-top: 5px; color: #666;">
                    Saldo a favor del cliente
                </div>
            </div>
        @endif
    </div>
    
    <!-- Sin imputaciones: Todo es anticipo -->
    @if(count($imputaciones) == 0)
        <div class="anticipo-box">
            <div style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">
                ℹ️ PAGO REGISTRADO COMO ANTICIPO COMPLETO
            </div>
            <div style="font-size: 10px; color: #666;">
                El cliente no tenía documentos pendientes al momento del pago.
            </div>
        </div>
    @endif
    
    <!-- Footer (Kendall: información adicional útil) -->
    <div class="footer">
        <p>Documento generado electrónicamente el {{ $fecha_emision }}</p>
        <p style="margin-top: 5px; font-style: italic;">
            Gracias por su pago. Ante cualquier consulta, comuníquese con nosotros.
        </p>
    </div>
    
    <!-- Botón para imprimir (solo visible en pantalla) -->
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #4CAF50; color: white; border: none; border-radius: 5px;">
            🖨️ Imprimir Recibo
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #999; color: white; border: none; border-radius: 5px; margin-left: 10px;">
            ✕ Cerrar
        </button>
    </div>
</body>
</html>
