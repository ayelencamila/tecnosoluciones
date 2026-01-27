<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Anulación - Venta #{{ $comprobante['numero'] }}</title>
    <style>
        /**
         * COMPROBANTE DE ANULACIÓN / NOTA DE CRÉDITO INTERNA
         * 
         * Diseño basado en Lineamientos de Kendall para CU-06 Paso 10:
         * 1. SERVIR AL PROPÓSITO: Constancia oficial de la operación de anulación
         * 2. AJUSTAR AL USUARIO: Cliente/vendedor necesita ver qué se anuló y por qué
         * 3. CANTIDAD ADECUADA: Datos de venta original + motivo de anulación
         * 4. COMPRENDER AL USUARIO: Lenguaje claro sobre la reversión
         * 5. PRESENTACIÓN APROPIADA: Formato profesional destacando la anulación
         * 6. PROVEER A TIEMPO: Se genera inmediatamente después de la anulación
         * 
         * Lineamientos específicos aplicados:
         * - ALINEACIÓN: Columnas numéricas alineadas a la derecha
         * - CONTENIDO: Detalle completo de lo anulado + motivo + impacto (stock/CC)
         * - EVITAR CÓDIGOS: Nombres descriptivos, "Crédito" en lugar de "NC"
         * - ESTÉTICA: Color rojo destacado para identificar anulación
         * - CONSTANTE VS VARIABLE: Datos de empresa separados de datos de anulación
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
            border-bottom: 3px solid #c00;
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

        /* Banner de ANULACIÓN destacado */
        .banner-anulacion {
            background: #c00;
            color: white;
            text-align: center;
            padding: 15px;
            margin-bottom: 15px;
            border: 3px solid #900;
            font-size: 14pt;
            font-weight: bold;
        }

        /* Información del comprobante - VARIABLE */
        .comprobante-info {
            margin-bottom: 15px;
            border: 2px solid #c00;
            padding: 10px;
            background: #fff5f5;
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
            width: 35%;
        }

        /* Sección de cliente - VARIABLE */
        .cliente-section {
            margin-bottom: 15px;
            border: 1px solid #c00;
            padding: 8px;
            background: #fffafa;
        }

        .cliente-section .title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 5px;
            text-decoration: underline;
            color: #c00;
        }

        /* Motivo de anulación destacado */
        .motivo-section {
            margin-bottom: 15px;
            border: 3px solid #c00;
            padding: 12px;
            background: #fff0f0;
        }

        .motivo-section .title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
            color: #c00;
            text-transform: uppercase;
        }

        .motivo-section .contenido {
            font-size: 11pt;
            line-height: 1.6;
            padding: 8px;
            background: white;
            border: 1px solid #ddd;
        }

        /* Tabla de productos - Lineamiento: ALINEACIÓN correcta */
        .detalles-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .detalles-table thead {
            background: #ffe0e0;
            border-top: 2px solid #c00;
            border-bottom: 2px solid #c00;
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

        /* Totales */
        .totales-section {
            float: right;
            width: 50%;
            border: 2px solid #c00;
            padding: 10px;
            background: #fff5f5;
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

        .totales-section .credito-aplicado {
            border-top: 2px solid #c00;
            font-size: 12pt;
            font-weight: bold;
            color: #c00;
        }

        /* Información de reversión */
        .reversion-info {
            clear: both;
            margin-bottom: 15px;
            padding: 10px;
            border: 2px solid #090;
            background: #f0fff0;
        }

        .reversion-info .title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #060;
            font-size: 11pt;
        }

        .reversion-info ul {
            list-style: none;
            padding-left: 15px;
        }

        .reversion-info li {
            margin-bottom: 5px;
        }

        .reversion-info li:before {
            content: "✓ ";
            color: #060;
            font-weight: bold;
            margin-right: 5px;
        }

        /* Footer - información legal */
        .footer {
            clear: both;
            border-top: 2px solid #c00;
            padding-top: 10px;
            font-size: 9pt;
            text-align: center;
            color: #555;
        }

        .footer .legal-notice {
            margin-top: 10px;
            font-style: italic;
        }

        /* Botón de impresión - NO imprimible */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #c00;
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
            background: #900;
        }
    </style>
</head>
<body>
    <!-- Botón de impresión (no se imprime) -->
    <button onclick="window.print()" class="print-button no-print">🖨️ Imprimir</button>

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

    <!-- BANNER DE ANULACIÓN -->
    <div class="banner-anulacion">
        ⚠️ COMPROBANTE DE ANULACIÓN / NOTA DE CRÉDITO INTERNA ⚠️
    </div>

    <!-- INFORMACIÓN DEL COMPROBANTE - Información VARIABLE -->
    <div class="comprobante-info">
        <table>
            <tr>
                <td class="label">VENTA ANULADA N°:</td>
                <td><strong style="color: #c00;">{{ $comprobante['numero'] }}</strong></td>
                <td class="label">FECHA VENTA ORIGINAL:</td>
                <td><strong>{{ $comprobante['fecha_venta_original'] }}</strong></td>
            </tr>
            <tr>
                <td class="label">FECHA DE ANULACIÓN:</td>
                <td><strong style="color: #c00;">{{ $comprobante['fecha_anulacion'] }}</strong></td>
                <td class="label">VENDEDOR:</td>
                <td><strong>{{ $vendedor }}</strong></td>
            </tr>
            <tr>
                <td class="label">MEDIO DE PAGO ORIGINAL:</td>
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

    <!-- MOTIVO DE ANULACIÓN - Lineamiento: Contenido del informe -->
    <div class="motivo-section">
        <div class="title">📋 Motivo de la Anulación</div>
        <div class="contenido">
            {{ $motivo_anulacion }}
        </div>
    </div>

    <!-- DETALLE DE PRODUCTOS ANULADOS -->
    <h3 style="margin-bottom: 10px; color: #c00;">DETALLE DE PRODUCTOS ANULADOS:</h3>
    <table class="detalles-table">
        <thead>
            <tr>
                <th style="width: 10%;">Cant.</th>
                <th class="descripcion" style="width: 50%;">Descripción</th>
                <th class="num" style="width: 20%;">P. Unitario</th>
                <th class="num" style="width: 20%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $detalle)
            <tr>
                <td class="num">{{ $detalle['cantidad'] }}</td>
                <td class="descripcion">{{ $detalle['descripcion'] }}</td>
                <td class="num">${{ number_format($detalle['precio_unitario'], 2, ',', '.') }}</td>
                <td class="num">${{ number_format($detalle['subtotal_neto'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTALES -->
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
            <tr class="credito-aplicado">
                <td class="label">CRÉDITO APLICADO:</td>
                <td class="monto">${{ number_format($totales['total_final'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- INFORMACIÓN DE REVERSIÓN -->
    <div class="reversion-info">
        <div class="title">✓ OPERACIONES DE REVERSIÓN APLICADAS:</div>
        <ul>
            <li>Stock de productos reincorporado al inventario</li>
            <li>Cuenta corriente del cliente ajustada (crédito aplicado)</li>
            <li>Operación registrada en historial de auditoría</li>
        </ul>
    </div>

    <!-- FOOTER - Información legal -->
    <div class="footer">
        <div>Comprobante de anulación emitido el {{ $fecha_emision }}</div>
        <div class="legal-notice">
            <strong>DOCUMENTO INTERNO - NO VÁLIDO COMO FACTURA</strong><br>
            Comprobante de anulación interno - No posee validez fiscal<br>
            El crédito generado puede ser utilizado en futuras operaciones
        </div>
    </div>
</body>
</html>
