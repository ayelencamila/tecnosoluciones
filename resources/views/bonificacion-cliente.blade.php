<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Respuesta de Bonificación - TecnoSoluciones</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .info-value {
            font-weight: 600;
            color: #212529;
            font-size: 14px;
        }
        
        .price-box {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .price-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .price-amount {
            font-size: 36px;
            font-weight: bold;
        }
        
        .discount-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .discount-info p {
            color: #856404;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(17, 153, 142, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(235, 51, 73, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .observaciones {
            margin-top: 20px;
        }
        
        .observaciones label {
            display: block;
            font-size: 14px;
            color: #495057;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .observaciones textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
        }
        
        .observaciones textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .message {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 Bonificación por Demora</h1>
            <p>TecnoSoluciones - Reparación #{{ $bonificacion->reparacion->codigo_reparacion }}</p>
        </div>
        
        <div class="content">
            <div id="messageArea"></div>
            <div id="loadingArea" class="loading">
                <div class="spinner"></div>
                <p>Procesando su respuesta...</p>
            </div>
            
            <div id="contentArea">
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Cliente</span>
                        <span class="info-value">{{ $bonificacion->reparacion->cliente->nombreCompleto }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Equipo</span>
                        <span class="info-value">{{ $bonificacion->reparacion->equipo_marca }} {{ $bonificacion->reparacion->equipo_modelo }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Días de demora</span>
                        <span class="info-value">{{ $bonificacion->dias_excedidos }} días</span>
                    </div>
                </div>
                
                <div class="price-box">
                    <div class="price-label">Total a pagar con bonificación</div>
                    <div class="price-amount">${{ number_format($totalPagar, 2, ',', '.') }}</div>
                    <div style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                        ${{ number_format($bonificacion->monto_original, 2, ',', '.') }} - ${{ number_format($bonificacion->monto_bonificado, 2, ',', '.') }}
                    </div>
                </div>
                
                <div class="discount-info">
                    <p>
                        <strong>🎉 ¡Tenemos una bonificación para usted!</strong><br>
                        Debido a la demora en su reparación, aplicaremos un descuento del <strong>{{ $bonificacion->porcentaje_aprobado }}%</strong> sobre el costo final.
                    </p>
                </div>
                
                <div class="observaciones">
                    <label for="observaciones">Observaciones (opcional)</label>
                    <textarea id="observaciones" placeholder="Puede agregar algún comentario aquí..."></textarea>
                </div>
                
                <div class="buttons">
                    <button class="btn btn-success" onclick="responder('aceptar')">
                        <span>✅</span> Aceptar y Continuar con la Reparación
                    </button>
                    <button class="btn btn-danger" onclick="responder('cancelar')">
                        <span>❌</span> Cancelar y Retirar mi Equipo
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const token = '{{ $token }}';
        
        async function responder(decision) {
            const observaciones = document.getElementById('observaciones').value;
            
            // Mostrar loading
            document.getElementById('contentArea').style.display = 'none';
            document.getElementById('loadingArea').classList.add('active');
            
            try {
                const response = await fetch(`/bonificacion/${token}/responder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ decision, observaciones })
                });
                
                const data = await response.json();
                
                document.getElementById('loadingArea').classList.remove('active');
                
                if (data.mensaje) {
                    mostrarExito(data.mensaje);
                } else if (data.error) {
                    mostrarError(data.error);
                    document.getElementById('contentArea').style.display = 'block';
                }
            } catch (error) {
                document.getElementById('loadingArea').classList.remove('active');
                mostrarError('Error al procesar su respuesta. Por favor, intente nuevamente.');
                document.getElementById('contentArea').style.display = 'block';
            }
        }
        
        function mostrarExito(mensaje) {
            document.getElementById('messageArea').innerHTML = `
                <div class="message success">
                    <h3 style="margin-bottom: 10px;">✅ ¡Respuesta Registrada!</h3>
                    <p>${mensaje}</p>
                </div>
            `;
        }
        
        function mostrarError(mensaje) {
            document.getElementById('messageArea').innerHTML = `
                <div class="message error">
                    <h3 style="margin-bottom: 10px;">⚠️ Error</h3>
                    <p>${mensaje}</p>
                </div>
            `;
        }
        
    </script>
</body>
</html>
