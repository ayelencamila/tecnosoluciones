<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Ingreso - Reparación {{ $comprobante['codigo'] }}</title>
    <style>
        /**
         * COMPROBANTE DE INGRESO DE REPARACIÓN - TecnoSoluciones
         * 
         * Diseño basado en Lineamientos de Kendall para CU-11 Paso 9:
         * 
         * OBJETIVOS DE SALIDA:
         * 1. SERVIR AL PROPÓSITO: Constancia oficial de recepción del dispositivo
         * 2. AJUSTAR AL USUARIO: Cliente necesita saber qué dejó, estado y cuándo retira
         * 3. CANTIDAD ADECUADA: Datos del equipo + falla declarada + fecha promesa
         * 4. DONDE SE NECESITE: Imprimible para entregar al cliente en el local
         * 5. PROVEER A TIEMPO: Se genera inmediatamente después del registro
         * 6. MÉTODO CORRECTO: Digital (pantalla) e impreso (papel)
         * 
         * LINEAMIENTOS ESPECÍFICOS:
         * - ALINEACIÓN: Encabezados alineados sobre los datos
         * - CONTENIDO: Detalle completo del dispositivo y falla
         * - EVITAR CÓDIGOS: "Marca y Modelo" en lugar de "modelo_id"
         * - ESTÉTICA: Separación visual clara entre secciones
         * - CONSTANTE VS VARIABLE: Datos de empresa separados de datos de reparación
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

        /* Título del documento */
        .titulo-documento {
            background: #4a5568;
            color: white;
            text-align: center;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 14pt;
            font-weight: bold;
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

        /* Sección del equipo - Lineamiento: Contenido del informe */
        .equipo-section {
            margin-bottom: 15px;
            border: 2px solid #4a5568;
            padding: 10px;
            background: #f0f4f8;
        }

        .equipo-section .title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
            color: #2d3748;
            text-transform: uppercase;
        }

        .equipo-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .equipo-section td {
            padding: 4px 8px;
        }

        .equipo-section .label {
            font-weight: bold;
            width: 35%;
            color: #4a5568;
        }

        /* Sección de falla - Lineamiento: Información clara */
        .falla-section {
            margin-bottom: 15px;
            border: 2px solid #c53030;
            padding: 10px;
            background: #fff5f5;
        }

        .falla-section .title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 5px;
            color: #c53030;
            text-transform: uppercase;
        }

        .falla-section .contenido {
            padding: 8px;
            background: white;
            border: 1px solid #ddd;
            min-height: 60px;
        }

        /* Observaciones */
        .observaciones-section {
            margin-bottom: 15px;
            padding: 8px;
            border: 1px solid #999;
            background: #fafafa;
        }

        .observaciones-section .title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Información importante destacada */
        .info-importante {
            margin-bottom: 15px;
            padding: 12px;
            border: 3px solid #2b6cb0;
            background: #ebf8ff;
        }

        .info-importante .title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
            color: #2c5282;
            text-align: center;
        }

        .info-importante .contenido {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: #2c5282;
        }

        /* Condiciones y advertencias */
        .condiciones-section {
            margin-bottom: 15px;
            padding: 10px;
            border: 2px dashed #718096;
            background: #f7fafc;
            font-size: 9pt;
        }

        .condiciones-section .title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 10pt;
        }

        .condiciones-section ul {
            list-style: disc;
            padding-left: 20px;
            margin: 0;
        }

        .condiciones-section li {
            margin-bottom: 4px;
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

        .footer .firma-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-around;
        }

        .footer .firma {
            text-align: center;
            width: 40%;
        }

        .footer .firma-linea {
            border-top: 1px solid #000;
            margin-top: 40px;
            margin-bottom: 5px;
        }

        /* Botón de impresión - NO imprimible */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #4a5568;
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
            background: #2d3748;
        }

        /* Marca de agua diagonal */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.06);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            text-transform: uppercase;
            letter-spacing: 5px;
        }

        @media print {
            .watermark {
                position: fixed;
                color: rgba(0, 0, 0, 0.08);
            }
        }
    </style>
</head>
<body>
    <!-- Marca de agua -->
    <div class="watermark">Comprobante no fiscal</div>

    <!-- Botón de impresión (no se imprime) -->
    <button onclick="window.print()" class="print-button no-print" style="display: inline-flex; align-items: center; gap: 6px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
        Imprimir
    </button>

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

    <!-- TÍTULO DEL DOCUMENTO -->
    <div class="titulo-documento">
        COMPROBANTE DE INGRESO - SERVICIO TÉCNICO
    </div>

    <!-- INFORMACIÓN DEL COMPROBANTE - Información VARIABLE -->
    <div class="comprobante-info">
        <table>
            <tr>
                <td class="label">CÓDIGO REPARACIÓN:</td>
                <td><strong style="font-size: 12pt;">{{ $comprobante['codigo'] }}</strong></td>
                <td class="label">FECHA INGRESO:</td>
                <td><strong>{{ $comprobante['fecha_ingreso'] }}</strong></td>
            </tr>
            <tr>
                <td class="label">TÉCNICO ASIGNADO:</td>
                <td><strong>{{ $tecnico }}</strong></td>
                <td class="label">ESTADO:</td>
                <td><strong>{{ $estado }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- INFORMACIÓN DEL CLIENTE - Información VARIABLE -->
    <div class="cliente-section">
        <div class="title">DATOS DEL CLIENTE</div>
        <div><strong>Nombre:</strong> {{ $cliente['nombre_completo'] }}</div>
        @if($cliente['dni'])
            <div><strong>DNI:</strong> {{ $cliente['dni'] }}</div>
        @endif
        <div><strong>Teléfono:</strong> {{ $cliente['telefono'] }}</div>
    </div>

    <!-- FECHA PROMESA DESTACADA -->
    <div class="info-importante">
        <div class="title">FECHA PROMESA DE ENTREGA</div>
        <div class="contenido">{{ $comprobante['fecha_promesa'] }}</div>
    </div>

    <!-- INFORMACIÓN DEL EQUIPO - Lineamiento: Evitar códigos confusos -->
    <div class="equipo-section">
        <div class="title">Datos del Equipo Recibido</div>
        <table>
            <tr>
                <td class="label">MARCA:</td>
                <td><strong>{{ $equipo['marca'] }}</strong></td>
                <td class="label">MODELO:</td>
                <td><strong>{{ $equipo['modelo'] }}</strong></td>
            </tr>
            <tr>
                <td class="label">IMEI / N° SERIE:</td>
                <td colspan="3"><strong>{{ $equipo['imei_serie'] }}</strong></td>
            </tr>
            <tr>
                <td class="label">CLAVE DE BLOQUEO:</td>
                <td colspan="3"><strong>{{ $equipo['clave_bloqueo'] }}</strong></td>
            </tr>
            <tr>
                <td class="label">ACCESORIOS DEJADOS:</td>
                <td colspan="3">{{ $equipo['accesorios'] }}</td>
            </tr>
        </table>
    </div>

    <!-- FALLA DECLARADA - Lineamiento: Contenido del informe -->
    <div class="falla-section">
        <div class="title">FALLA DECLARADA POR EL CLIENTE</div>
        <div class="contenido">
            {{ $falla_declarada }}
        </div>
    </div>

    <!-- PRESUPUESTO INICIAL (Flujo simplificado - se da al ingresar) -->
    @if($presupuesto['tiene_presupuesto'])
    <div class="presupuesto-section" style="margin-bottom: 15px; border: 2px solid #38a169; padding: 10px; background: #f0fff4;">
        <div style="font-weight: bold; font-size: 12pt; margin-bottom: 8px; color: #276749; text-transform: uppercase;">
            PRESUPUESTO INICIAL ACORDADO
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
            @if(count($presupuesto['repuestos']) > 0)
                <tr style="background: #c6f6d5;">
                    <td colspan="4" style="padding: 4px 8px; font-weight: bold; border-bottom: 1px solid #9ae6b4;">REPUESTOS</td>
                </tr>
                @foreach($presupuesto['repuestos'] as $repuesto)
                <tr>
                    <td style="padding: 3px 8px;">{{ $repuesto['nombre'] }}</td>
                    <td style="padding: 3px 8px; text-align: center;">x{{ $repuesto['cantidad'] }}</td>
                    <td style="padding: 3px 8px; text-align: right;">${{ number_format($repuesto['precio_unitario'], 2, ',', '.') }}</td>
                    <td style="padding: 3px 8px; text-align: right; font-weight: bold;">${{ number_format($repuesto['subtotal'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" style="padding: 4px 8px; text-align: right; border-top: 1px solid #9ae6b4;">Subtotal Repuestos:</td>
                    <td style="padding: 4px 8px; text-align: right; font-weight: bold; border-top: 1px solid #9ae6b4;">${{ number_format($presupuesto['subtotal_repuestos'], 2, ',', '.') }}</td>
                </tr>
            @endif
            @if($presupuesto['costo_mano_obra'] > 0)
            <tr>
                <td colspan="3" style="padding: 4px 8px; text-align: right;">Mano de Obra:</td>
                <td style="padding: 4px 8px; text-align: right; font-weight: bold;">${{ number_format($presupuesto['costo_mano_obra'], 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr style="background: #276749; color: white;">
                <td colspan="3" style="padding: 6px 8px; text-align: right; font-size: 11pt; font-weight: bold;">TOTAL A COBRAR:</td>
                <td style="padding: 6px 8px; text-align: right; font-size: 12pt; font-weight: bold;">${{ number_format($presupuesto['total'], 2, ',', '.') }}</td>
            </tr>
        </table>
        <div style="margin-top: 8px; font-size: 9pt; color: #276749; text-align: center; font-style: italic;">
            * El cliente acepta el presupuesto detallado arriba al firmar este comprobante.
        </div>
    </div>
    @endif

    <!-- OBSERVACIONES (si existen) -->
    @if($observaciones)
    <div class="observaciones-section">
        <div class="title">OBSERVACIONES ADICIONALES:</div>
        <div>{{ $observaciones }}</div>
    </div>
    @endif

    <!-- CONDICIONES DEL SERVICIO -->
    <div class="condiciones-section">
        <div class="title">
            CONDICIONES IMPORTANTES:
        </div>
        <ul>
            <li>El cliente declara que el equipo ingresó en las condiciones descritas anteriormente.</li>
            @if($presupuesto['tiene_presupuesto'])
            <li><strong>El cliente acepta el presupuesto inicial detallado en este comprobante.</strong></li>
            <li>Si durante la reparación se detectan fallas adicionales, se comunicará un presupuesto complementario.</li>
            @else
            <li>El presupuesto será informado una vez realizado el diagnóstico técnico.</li>
            @endif
            <li>El plazo de retiro es de 30 días desde la fecha de notificación de reparación finalizada.</li>
            <li>Pasados 60 días sin retiro, el equipo será considerado abandonado según normativa vigente.</li>
            <li>La empresa no se hace responsable por información contenida en el dispositivo.</li>
            <li>Se recomienda realizar respaldo de datos antes del ingreso.</li>
        </ul>
    </div>

    <!-- FOOTER - Información legal y firmas -->
    <div class="footer">
        <div>Comprobante emitido el {{ $fecha_emision }}</div>
        
        <div class="firma-section">
            <div class="firma">
                <div class="firma-linea"></div>
                <strong>FIRMA DEL CLIENTE</strong><br>
                DNI: {{ $cliente['dni'] }}
            </div>
            <div class="firma">
                <div class="firma-linea"></div>
                <strong>FIRMA Y SELLO</strong><br>
                {{ $empresa['nombre'] }}
            </div>
        </div>

        <div style="margin-top: 15px; font-style: italic;">
            <strong>DOCUMENTO INTERNO - NO VÁLIDO COMO FACTURA</strong><br>
            Comprobante de ingreso - No posee validez fiscal<br>
            Conserve este comprobante para el retiro del equipo
        </div>
    </div>
</body>
</html>
