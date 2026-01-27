<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Cotización</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .badge {
            display: inline-block;
            background-color: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 10px;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #667eea;
        }
        .alert-recordatorio {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .productos-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .productos-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .productos-table tr:hover {
            background-color: #f8f9fa;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box strong {
            color: #1976d2;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .vencimiento {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 15px 0;
        }
        .vencimiento strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            @if($esRecordatorio)
                <h1>🔔 Recordatorio de Cotización</h1>
            @else
                <h1>📋 Solicitud de Cotización</h1>
            @endif
            <div class="badge">#{{ $solicitud->codigo_solicitud }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            @if($esRecordatorio)
                <div class="alert-recordatorio">
                    <strong>⏰ Recordatorio:</strong> Aún no hemos recibido su cotización. 
                    Le recordamos que tiene hasta el <strong>{{ \Carbon\Carbon::parse($solicitud->fecha_vencimiento)->format('d/m/Y') }}</strong> 
                    para responder.
                </div>
            @endif

            <p class="greeting">
                Estimado/a <strong>{{ $proveedor->razon_social }}</strong>,
            </p>

            <p>
                Solicitamos su cotización para los siguientes productos:
            </p>

            <!-- Tabla de Productos -->
            <table class="productos-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($solicitud->detalles as $detalle)
                        <tr>
                            <td><strong>{{ $detalle->producto->nombre }}</strong></td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>{{ $detalle->producto->unidad_medida ?? 'Unidad' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Info Box -->
            <div class="info-box">
                <strong>ℹ️ Información importante:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Haga clic en el botón de abajo para responder la cotización</li>
                    <li>No necesita crear una cuenta ni iniciar sesión</li>
                    <li>Puede indicar precio, disponibilidad y plazo de entrega</li>
                    <li>Sus datos se guardan automáticamente</li>
                </ul>
            </div>

            <!-- CTA Button -->
            <center>
                <a href="{{ $url }}" class="cta-button">
                    📝 RESPONDER COTIZACIÓN
                </a>
            </center>

            <!-- Vencimiento -->
            <div class="vencimiento">
                <strong>⏱️ Fecha límite:</strong> 
                {{ \Carbon\Carbon::parse($solicitud->fecha_vencimiento)->format('d/m/Y H:i') }}
                @if($diasParaVencer > 0)
                    ({{ $diasParaVencer }} día{{ $diasParaVencer > 1 ? 's' : '' }} restante{{ $diasParaVencer > 1 ? 's' : '' }})
                @elseif($diasParaVencer == 0)
                    (<strong style="color: #d32f2f;">¡Vence hoy!</strong>)
                @endif
            </div>

            <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                Si el botón no funciona, copie y pegue este enlace en su navegador:<br>
                <a href="{{ $url }}" style="color: #667eea; word-break: break-all;">{{ $url }}</a>
            </p>

            <p style="margin-top: 20px;">
                Saludos cordiales,<br>
                <strong>{{ config('app.name') }}</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este es un correo automático. Por favor no responda a este mensaje.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
