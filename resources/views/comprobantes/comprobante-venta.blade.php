<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta #{{ $comprobante['numero'] }}</title>
    <style>
        /**
         * COMPROBANTE DE VENTA - TecnoSoluciones
         * 
         * Diseño basado en Lineamientos de Kendall:
         * 1. SERVIR AL PROPÓSITO: Constancia clara de la venta realizada
         * 2. AJUSTAR AL USUARIO: Fácil lectura del detalle de compra
         * 3. CANTIDAD ADECUADA: Productos, precios, descuentos y totales
         * 4. COMPRENDER AL USUARIO: Lenguaje comercial claro
         * 5. PRESENTACIÓN APROPIADA: Formato profesional listo para imprimir
         * 6. PROVEER A TIEMPO: Se genera inmediatamente después de la venta
         * 
         * Lineamientos específicos aplicados:
         * - ALINEACIÓN: Columnas numéricas alineadas a la derecha
         * - CONTENIDO: Detalle completo de productos y descuentos
         * - EVITAR CÓDIGOS: Nombres descriptivos en lugar de códigos
         * - ESTÉTICA: Separación visual clara entre secciones
         * - CONSTANTE VS VARIABLE: Datos de empresa separados de datos de venta
         */

        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Encabezado con información CONSTANTE (empresa) */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header .empresa-info {
            font-size: 10pt;
            line-height: 1.3;
        }

        /* Información del comprobante - VARIABLE */
        .comprobante-info {
            margin-bottom: 15px;
            border: 2px solid #000;
            padding: 10px;
            background: #f9f9f9;
        }

        .comprobante-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .comprobante-info td {
            padding: 3px 5px;
        }

        .comprobante-info .label {
            font-weight: bold;
            width: 30%;
        }

        /* Sección de cliente - VARIABLE */
        .cliente-section {
            margin-bottom: 15px;
            border: 1px solid #000;
            padding: 8px;
        }

        .cliente-section .title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 5px;
            text-decoration: underline;
        }

        /* Tabla de productos - Lineamiento: ALINEACIÓN correcta */
        .detalles-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .detalles-table thead {
            background: #e0e0e0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .detalles-table th {
            padding: 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }

        .detalles-table th.num {
            text-align: right;
        }

        .detalles-table td {
            padding: 4px 5px;
            border-bottom: 1px solid #ccc;
            font-size: 10pt;
        }

        .detalles-table td.descripcion {
            text-align: left;
        }

        .detalles-table td.num {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .detalles-table tfoot {
            border-top: 2px solid #000;
        }

        .detalles-table tfoot td {
            border-bottom: none;
        }

        /* Descuentos globales */
        .descuentos-section {
            margin-bottom: 10px;
            padding: 8px;
            border: 1px dashed #666;
            background: #fffef0;
        }

        .descuentos-section .title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .descuentos-section ul {
            list-style: none;
            padding-left: 15px;
        }

        .descuentos-section li {
            margin-bottom: 3px;
        }

        /* Totales - Lineamiento: SEPARACIÓN VISUAL */
        .totales-section {
            float: right;
            width: 50%;
            border: 2px solid #000;
            padding: 10px;
            background: #f5f5f5;
            margin-bottom: 15px;
        }

        .totales-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .totales-section td {
            padding: 4px 8px;
        }

        .totales-section .label {
            text-align: left;
            font-weight: bold;
        }

        .totales-section .monto {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .totales-section .total-final {
            border-top: 2px solid #000;
            font-size: 12pt;
            font-weight: bold;
        }

        /* Observaciones */
        .observaciones-section {
            clear: both;
            margin-bottom: 15px;
            padding: 8px;
            border: 1px solid #999;
            background: #fafafa;
            min-height: 50px;
        }

        .observaciones-section .title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Footer - información legal */
        .footer {
            clear: both;
            border-top: 2px solid #000;
            padding-top: 10px;
            font-size: 9pt;
            text-align: center;
            color: #555;
        }

        .footer .legal-notice {
            margin-top: 10px;
            font-style: italic;
        }

        /* Marca de agua para ANULADA */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100pt;
            font-weight: bold;
            color: rgba(255, 0, 0, 0.15);
            z-index: -1;
            pointer-events: none;
        }

        /* Botón de impresión - NO imprimible */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .print-button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <!-- Botón de impresión (no se imprime) -->
    <button onclick="window.print()" class="print-button no-print">🖨️ Imprimir</button>

    <!-- Marca de agua si está anulada -->
    @if($es_anulada)
        <div class="watermark">ANULADA</div>
    @endif

    <!-- ENCABEZADO - Información CONSTANTE de la empresa -->
    <div class="header">
        <h1>{{ $empresa['nombre'] }}</h1>
        <div class="empresa-info">
            @if($empresa['direccion'])
                <div>{{ $empresa['direccion'] }}</div>
            @endif
            @if($empresa['telefono'])
                <div>Tel: {{ $empresa['telefono'] }}</div>
            @endif
            @if($empresa['email'])
                <div>Email: {{ $empresa['email'] }}</div>
            @endif
            @if($empresa['cuit'])
                <div>CUIT: {{ $empresa['cuit'] }}</div>
            @endif
        </div>
    </div>

    <!-- INFORMACIÓN DEL COMPROBANTE - Información VARIABLE -->
    <div class="comprobante-info">
        <table>
            <tr>
                <td class="label">COMPROBANTE DE VENTA:</td>
                <td><strong>#{{ $comprobante['numero'] }}</strong></td>
                <td class="label">FECHA:</td>
                <td><strong>{{ $comprobante['fecha'] }}</strong></td>
            </tr>
            <tr>
                <td class="label">ESTADO:</td>
                <td><strong>{{ $comprobante['estado'] }}</strong></td>
                <td class="label">VENDEDOR:</td>
                <td><strong>{{ $vendedor }}</strong></td>
            </tr>
            <tr>
                <td class="label">MEDIO DE PAGO:</td>
                <td colspan="3"><strong>{{ $medio_pago }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- INFORMACIÓN DEL CLIENTE - Información VARIABLE -->
    <div class="cliente-section">
        <div class="title">CLIENTE</div>
        <div><strong>Nombre:</strong> {{ $cliente['nombre_completo'] }}</div>
        @if($cliente['dni'])
            <div><strong>DNI:</strong> {{ $cliente['dni'] }}</div>
        @endif
        @if($cliente['tipo'])
            <div><strong>Tipo:</strong> {{ $cliente['tipo'] }}</div>
        @endif
    </div>

    <!-- DETALLE DE PRODUCTOS - Lineamiento: Contenido con detalles necesarios -->
    <table class="detalles-table">
        <thead>
            <tr>
                <th style="width: 5%;">Cant.</th>
                <th class="descripcion" style="width: 40%;">Descripción</th>
                <th class="num" style="width: 15%;">P. Unitario</th>
                <th class="num" style="width: 15%;">Subtotal</th>
                <th class="num" style="width: 10%;">Descuento</th>
                <th class="num" style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $detalle)
            <tr>
                <td class="num">{{ $detalle['cantidad'] }}</td>
                <td class="descripcion">{{ $detalle['descripcion'] }}</td>
                <td class="num">${{ number_format($detalle['precio_unitario'], 2, ',', '.') }}</td>
                <td class="num">${{ number_format($detalle['subtotal_bruto'], 2, ',', '.') }}</td>
                <td class="num">
                    @if($detalle['descuento'] > 0)
                        -${{ number_format($detalle['descuento'], 2, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
                <td class="num">${{ number_format($detalle['subtotal_neto'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- DESCUENTOS GLOBALES (si existen) -->
    @if(count($descuentos_globales) > 0)
    <div class="descuentos-section">
        <div class="title">DESCUENTOS APLICADOS:</div>
        <ul>
            @foreach($descuentos_globales as $descuento)
            <li>{{ $descuento['nombre'] }}: -${{ number_format($descuento['monto'], 2, ',', '.') }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- TOTALES - Lineamiento: Alineación y separación visual -->
    <div class="totales-section">
        <table>
            <tr>
                <td class="label">SUBTOTAL:</td>
                <td class="monto">${{ number_format($totales['subtotal'], 2, ',', '.') }}</td>
            </tr>
            @if($totales['total_descuentos'] > 0)
            <tr>
                <td class="label">DESCUENTOS:</td>
                <td class="monto">-${{ number_format($totales['total_descuentos'], 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-final">
                <td class="label">TOTAL:</td>
                <td class="monto">${{ number_format($totales['total_final'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- OBSERVACIONES -->
    <div class="observaciones-section">
        <div class="title">OBSERVACIONES:</div>
        <div>
            @if($observaciones)
                {{ $observaciones }}
            @else
                (Sin observaciones)
            @endif
        </div>
        @if($es_anulada && $motivo_anulacion)
        <div style="margin-top: 10px; color: red; font-weight: bold;">
            <strong>MOTIVO DE ANULACIÓN:</strong> {{ $motivo_anulacion }}
        </div>
        @endif
    </div>

    <!-- FOOTER - Información legal -->
    <div class="footer">
        <div>Comprobante generado el {{ $fecha_emision }}</div>
        <div class="legal-notice">
            <strong>DOCUMENTO NO VÁLIDO COMO FACTURA</strong><br>
            Comprobante interno - No posee validez fiscal
        </div>
    </div>
</body>
</html>
