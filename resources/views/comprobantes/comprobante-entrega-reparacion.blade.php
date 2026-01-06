<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Entrega - Reparación {{ $comprobante['codigo'] }}</title>
    <style>
        /* Reset y Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 10mm;
        }

        /* Alineación Kendall: Campos alineados correctamente */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        /* Estética Kendall: Separación visual */
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5mm;
            margin-bottom: 5mm;
        }

        .section {
            margin-bottom: 4mm;
            padding: 3mm;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .section-title {
            font-weight: bold;
            font-size: 10pt;
            color: #1e40af;
            margin-bottom: 2mm;
            text-transform: uppercase;
            border-bottom: 1px solid #3b82f6;
            padding-bottom: 1mm;
        }

        /* Tablas con alineación Kendall */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2mm;
        }

        th {
            background: #3b82f6;
            color: #fff;
            padding: 1.5mm;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2563eb;
            font-size: 8pt;
        }

        td {
            padding: 1.5mm;
            border: 1px solid #d1d5db;
            font-size: 8pt;
        }

        /* Alineación Kendall: Montos a la derecha */
        .monto {
            text-align: right;
            font-weight: bold;
        }

        /* Totales destacados */
        .totales-box {
            margin-top: 3mm;
            padding: 2mm;
            background: #dbeafe;
            border: 2px solid #3b82f6;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 1mm 0;
            border-bottom: 1px solid #93c5fd;
            font-size: 9pt;
        }

        .total-row.final {
            font-size: 11pt;
            font-weight: bold;
            border-bottom: 2px double #1e40af;
            color: #1e40af;
        }

        /* Firma */
        .firma-box {
            margin-top: 6mm;
            padding-top: 10mm;
            border-top: 1px solid #000;
            text-align: center;
        }

        /* Información Constante vs Variable (Kendall) */
        .empresa-info {
            font-size: 8pt;
            color: #6b7280;
        }

        .destacado {
            background: #fef3c7;
            padding: 2mm;
            border-left: 2mm solid #f59e0b;
            margin: 2mm 0;
            font-size: 9pt;
        }

        /* Impresión */
        @media print {
            body { 
                padding: 0; 
                margin: 0;
            }
            .no-print { display: none; }
        }

        /* Aviso Legal */
        .legal-notice {5mm;
            padding: 2mm;
            background: #f3f4f6;
            border-left: 2mm solid #9ca3af;
            font-size: 7 3mm solid #9ca3af;
            font-size: 9pt;
            color: #4b5563;
        }
    </style>
    <script>
        // Activar diálogo de impresión automáticamente
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
    <!-- ENCABEZADO: Información CONSTANTE (Kendall) -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 style="font-size: 14pt; color: #1e40af; margin-bottom: 1mm;">{{ $empresa['nombre'] }}</h1>
                <p class="empresa-info">{{ $empresa['direccion'] }}</p>
                <p class="empresa-info">Tel: {{ $empresa['telefono'] }} | Email: {{ $empresa['email'] }}</p>
                <p class="empresa-info">CUIT: {{ $empresa['cuit'] }}</p>
            </div>
            <div class="text-right">
                <h2 style="font-size: 12pt; color: #10b981; font-weight: bold;">COMPROBANTE DE ENTREGA</h2>
                <p style="font-size: 10pt; margin-top: 1mm;"><strong>Reparación:</strong> {{ $comprobante['codigo'] }}</p>
                <p style="font-size: 8pt; color: #6b7280;">Fecha de Emisión: {{ $fecha_emision }}</p>
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN VARIABLE: Datos del Cliente y Equipo (Kendall) -->
    <div class="section">
        <div class="section-title">Datos del Cliente</div>
        <table style="border: none;">
            <tr>
                <td style="border: none; width: 25%; font-weight: bold;">Cliente:</td>
                <td style="border: none;">{{ $cliente['nombre_completo'] }}</td>
                <td style="border: none; width: 20%; font-weight: bold;">DNI:</td>
                <td style="border: none;">{{ $cliente['dni'] }}</td>
            </tr>
            <tr>
                <td style="border: none; font-weight: bold;">Teléfono:</td>
                <td style="border: none;">{{ $cliente['telefono'] }}</td>
                <td style="border: none; font-weight: bold;">Fecha Ingreso:</td>
                <td style="border: none;">{{ $comprobante['fecha_ingreso'] }}</td>
            </tr>
            <tr>
                <td style="border: none; font-weight: bold;">Técnico:</td>
                <td style="border: none;">{{ $tecnico }}</td>
                <td style="border: none; font-weight: bold;">Fecha Entrega:</td>
                <td style="border: none; color: #10b981; font-weight: bold;">{{ $comprobante['fecha_entrega'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Datos del Equipo</div>
        <table style="border: none;">
            <tr>
                <td style="border: none; width: 25%; font-weight: bold;">Marca y Modelo:</td>
                <td style="border: none;">{{ $equipo['marca'] }} {{ $equipo['modelo'] }}</td>
            </tr>
            <tr>
                <td style="border: none; font-weight: bold;">Serie / IMEI:</td>
                <td style="border: none; font-family: monospace;">{{ $equipo['imei_serie'] }}</td>
            </tr>
        </table>
    </div>

    <!-- Falla Original y Diagnóstico -->
    <div class="destacado">
        <p style="font-weight: bold; color: #dc2626; margin-bottom: 2mm;">Falla Declarada por el Cliente:</p>
        <p>{{ $falla_original }}</p>
    </div>

    <div class="section">
        <div class="section-title">Diagnóstico Técnico y Trabajo Realizado</div>
        <p style="white-space: pre-line; padding: 2mm;">{{ $diagnostico }}</p>
        @if($observaciones)
            <p style="margin-top: 3mm; padding: 2mm; background: #fef3c7; border-left: 3mm solid #f59e0b;">
                <strong>Observaciones:</strong> {{ $observaciones }}
            </p>
        @endif
    </div>

    <!-- Repuestos y Servicios Utilizados (Kendall: Alineación de campos) -->
    <div class="section">
        <div class="section-title">Repuestos y Servicios Utilizados</div>
        @if(count($repuestos) > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 45%;">Descripción</th>
                        <th style="width: 15%; text-align: center;">Cant.</th>
                        <th style="width: 20%; text-align: right;">Precio Unit.</th>
                        <th style="width: 20%; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($repuestos as $item)
                        <tr>
                            <td>{{ $item['descripcion'] }}</td>
                            <td style="text-align: center;">{{ $item['cantidad'] }}</td>
                            <td class="monto">${{ number_format($item['precio_unitario'], 2, ',', '.') }}</td>
                            <td class="monto">${{ number_format($item['subtotal'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: #6b7280; font-style: italic; padding: 3mm;">
                No se utilizaron repuestos adicionales.
            </p>
        @endif
    </div>

    <!-- Totales (Kendall: Subtotales útiles con alineación correcta) -->
    <div class="totales-box">
        <div class="total-row">
            <span>Subtotal Repuestos:</span>
            <span class="monto">${{ number_format($totales['total_repuestos'], 2, ',', '.') }}</span>
        </div>
        <div class="total-row">
            <span>Mano de Obra:</span>
            <span class="monto">${{ number_format($totales['mano_obra'], 2, ',', '.') }}</span>
        </div>
        <div class="total-row final">
            <span>TOTAL A ABONAR:</span>
            <span>${{ number_format($totales['total_final'], 2, ',', '.') }}</span>
        </div>
    </div>

    <!-- Firma del Cliente -->
    <div class="firma-box">
        <p style="margin-bottom: 1mm;">__________________________________________</p>
        <p style="font-weight: bold;">Firma y Aclaración del Cliente</p>
        <p style="font-size: 9pt; color: #6b7280; margin-top: 2mm;">
            Declaro haber recibido el equipo detallado en este comprobante<br>
            en perfecto estado de funcionamiento.
        </p>
    </div>

    <!-- Aviso Legal (Kendall: Información Constante) -->
    <div class="legal-notice">
        <strong>IMPORTANTE:</strong> Este es un comprobante interno no fiscal. 
        Conserve este documento como constancia de la entrega del equipo. 
        La garantía de los trabajos realizados es de 30 días a partir de la fecha de entrega, 
        siempre que el equipo no haya sido manipulado por terceros.
    </div>

    <!-- Botón para imprimir (no se muestra en impresión) -->
    <div class="no-print" style="text-align: center; margin-top: 10mm;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #3b82f6; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 12pt;">
            🖨️ Imprimir Comprobante
        </button>
    </div>
</body>
</html>
