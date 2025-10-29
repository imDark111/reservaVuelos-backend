<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billete de Vuelo - {{ $billete->codigo_billete }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 1px solid #ddd;
        }
        .header {
            background: #1e3c72;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 20px;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .section h3 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .item {
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
        }
        .value {
            margin-left: 10px;
        }
        .status-emitido {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .status-usado {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .status-cancelado {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .flight-route {
            background: #667eea;
            color: white;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .airport {
            display: inline-block;
            margin: 0 20px;
        }
        .airport-code {
            font-size: 20px;
            font-weight: bold;
        }
        .airport-name {
            font-size: 12px;
        }
        .flight-line {
            display: inline-block;
            width: 40px;
            height: 2px;
            background: white;
            margin: 0 10px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
        }
        .qr-placeholder {
            width: 100px;
            height: 100px;
            background: #343a40;
            color: white;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        .footer {
            background: #343a40;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Billete de Vuelo</h1>
            <p>Código: {{ $billete->codigo_billete }}</p>
        </div>

        <div class="content">
            <div class="section">
                <h3>Información del Billete</h3>
                <div class="item">
                    <span class="label">Código:</span>
                    <span class="value">{{ $billete->codigo_billete }}</span>
                </div>
                <div class="item">
                    <span class="label">Estado:</span>
                    <span class="value">
                        <span class="status-{{ strtolower($billete->estado_billete) }}">
                            {{ ucfirst($billete->estado_billete) }}
                        </span>
                    </span>
                </div>
                <div class="item">
                    <span class="label">Clase:</span>
                    <span class="value">{{ ucfirst($billete->clase_servicio) }}</span>
                </div>
                <div class="item">
                    <span class="label">Precio:</span>
                    <span class="value">${{ number_format($billete->precio_pagado, 2) }}</span>
                </div>
            </div>

            <div class="section">
                <h3>Información del Usuario</h3>
                <div class="item">
                    <span class="label">Nombre:</span>
                    <span class="value">{{ $billete->usuario ? $billete->usuario->nombres . ' ' . $billete->usuario->apellidos : 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="label">Email:</span>
                    <span class="value">{{ $billete->usuario ? $billete->usuario->email : 'N/A' }}</span>
                </div>
            </div>

            <div class="section">
                <h3>Información del Pasajero</h3>
                <div class="item">
                    <span class="label">Nombre:</span>
                    <span class="value">{{ $billete->pasajero ? $billete->pasajero->nombres . ' ' . $billete->pasajero->apellidos : 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="label">Documento:</span>
                    <span class="value">{{ $billete->pasajero ? ucfirst($billete->pasajero->tipo_documento) . ': ' . $billete->pasajero->numero_documento : 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="label">Asiento:</span>
                    <span class="value">{{ $billete->asiento ?: 'Por asignar' }}</span>
                </div>
            </div>

            @if($billete->vuelo)
            <div class="flight-route">
                <h3>Detalles del Vuelo</h3>
                <div class="airport">
                    <div class="airport-code">{{ $billete->vuelo->origen->codigo_iata ?? 'N/A' }}</div>
                    <div class="airport-name">{{ $billete->vuelo->origen->ciudad ?? 'N/A' }}</div>
                </div>
                <div class="flight-line"></div>
                <div class="airport">
                    <div class="airport-code">{{ $billete->vuelo->destino->codigo_iata ?? 'N/A' }}</div>
                    <div class="airport-name">{{ $billete->vuelo->destino->ciudad ?? 'N/A' }}</div>
                </div>
                <div style="margin-top: 15px;">
                    <strong>{{ $billete->vuelo->codigo_vuelo }}</strong><br>
                    {{ $billete->vuelo->fecha_salida ? $billete->vuelo->fecha_salida->format('d/m/Y H:i') : 'N/A' }}<br>
                    {{ $billete->vuelo->avion->aerolinea->nombre ?? 'N/A' }}
                </div>
            </div>
            @endif

            <div class="qr-code">
                <h3>Código de Barras</h3>
                <div class="qr-placeholder">
                    QR CODE<br/>{{ $billete->codigo_billete }}
                </div>
                <p>Presente este código en el aeropuerto para check-in</p>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; margin: 20px 0;">
                <strong>Información Importante:</strong><br/>
                • Presente este billete junto con su documento de identidad<br/>
                • El check-in debe realizarse 24 horas antes del vuelo<br/>
                • Este billete es intransferible y no reembolsable
            </div>
        </div>

        <div class="footer">
            <p><strong>Sistema de Reserva de Vuelos</strong> | Generado el {{ now()->format('d/m/Y H:i') }}</p>
            <p>Para soporte técnico contacte a soporte@reservavuelos.com</p>
        </div>
    </div>
</body>
</html>
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .info-section h3 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 18px;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
        }
        .value {
            color: #212529;
            font-size: 14px;
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-emitido {
            background: #28a745;
            color: white;
        }
        .status-usado {
            background: #17a2b8;
            color: white;
        }
        .status-cancelado {
            background: #dc3545;
            color: white;
        }
        .vuelo-route {
            background: #667eea;
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .vuelo-route h3 {
            margin: 0 0 25px 0;
            font-size: 22px;
            font-weight: bold;
        }
        .route-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .airport {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 8px;
            min-width: 120px;
            text-align: center;
        }
        .airport-code {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        .airport-name {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .flight-line {
            width: 60px;
            height: 2px;
            background: white;
            margin: 0 15px;
            position: relative;
        }
        .flight-line::after {
            content: '>';
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 16px;
            font-weight: bold;
        }
        .flight-details {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .qr-code {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
            border: 2px dashed #dee2e6;
        }
        .qr-code h3 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 18px;
        }
        .qr-placeholder {
            width: 120px;
            height: 120px;
            background: #343a40;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            border: 2px solid #495057;
        }
        .important-notice {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            font-size: 14px;
        }
        .important-notice strong {
            color: #533f00;
        }
        .footer {
            background: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }
        .footer p {
            margin: 5px 0;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Billete de Vuelo</h1>
            <p>Código: {{ $billete->codigo_billete }}</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Información del billete -->
            <div class="billete-info">
                <div class="info-section">
                    <h3>Información del Billete</h3>
                    <div class="info-item">
                        <span class="label">Código:</span>
                        <span class="value">{{ $billete->codigo_billete }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Estado:</span>
                        <span class="value">
                            <span class="status-badge status-{{ strtolower($billete->estado_billete) }}">
                                {{ ucfirst($billete->estado_billete) }}
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="label">Clase:</span>
                        <span class="value">{{ ucfirst($billete->clase_servicio) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Precio:</span>
                        <span class="value">${{ number_format($billete->precio_pagado, 2) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Emitido:</span>
                        <span class="value">{{ $billete->fecha_emision ? $billete->fecha_emision->format('d/m/Y H:i') : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Vence:</span>
                        <span class="value">{{ $billete->fecha_vencimiento ? $billete->fecha_vencimiento->format('d/m/Y H:i') : 'N/A' }}</span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Información del Usuario</h3>
                    <div class="info-item">
                        <span class="label">Nombre:</span>
                        <span class="value">{{ $billete->usuario ? $billete->usuario->nombres . ' ' . $billete->usuario->apellidos : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Email:</span>
                        <span class="value">{{ $billete->usuario ? $billete->usuario->email : 'N/A' }}</span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Información del Pasajero</h3>
                    <div class="info-item">
                        <span class="label">Nombre:</span>
                        <span class="value">{{ $billete->pasajero ? $billete->pasajero->nombres . ' ' . $billete->pasajero->apellidos : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Documento:</span>
                        <span class="value">{{ $billete->pasajero ? ucfirst($billete->pasajero->tipo_documento) . ': ' . $billete->pasajero->numero_documento : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Asiento:</span>
                        <span class="value">{{ $billete->asiento ?: 'Por asignar' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Check-in:</span>
                        <span class="value">{{ $billete->check_in_realizado ? 'Realizado' : 'Pendiente' }}</span>
                    </div>
                    @if($billete->check_in_realizado && $billete->fecha_check_in)
                    <div class="info-item">
                        <span class="label">Fecha Check-in:</span>
                        <span class="value">{{ $billete->fecha_check_in->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Ruta del vuelo -->
            @if($billete->vuelo)
            <div class="vuelo-route">
                <h3>Detalles del Vuelo</h3>
                <div class="route-container">
                    <div class="airport">
                        <div class="airport-code">{{ $billete->vuelo->origen->codigo_iata ?? 'N/A' }}</div>
                        <div class="airport-name">{{ $billete->vuelo->origen->ciudad ?? 'N/A' }}</div>
                    </div>
                    <div class="flight-line"></div>
                    <div class="airport">
                        <div class="airport-code">{{ $billete->vuelo->destino->codigo_iata ?? 'N/A' }}</div>
                        <div class="airport-name">{{ $billete->vuelo->destino->ciudad ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="flight-details">
                    <strong>{{ $billete->vuelo->codigo_vuelo }}</strong><br>
                    {{ $billete->vuelo->fecha_salida ? $billete->vuelo->fecha_salida->format('d/m/Y H:i') : 'N/A' }}<br>
                    {{ $billete->vuelo->avion->aerolinea->nombre ?? 'N/A' }}
                </div>
            </div>
            @endif

            <!-- Código QR -->
            <div class="qr-code">
                <h3>Código de Barras</h3>
                <div class="qr-placeholder">
                    QR CODE<br/>{{ $billete->codigo_billete }}
                </div>
                <p>Presente este código en el aeropuerto para check-in</p>
            </div>

            <!-- Avisos importantes -->
            <div class="important-notice">
                <strong>Información Importante:</strong><br/>
                • Presente este billete junto con su documento de identidad<br/>
                • Llegue al aeropuerto al menos 2 horas antes de vuelos internacionales<br/>
                • El check-in debe realizarse 24 horas antes del vuelo<br/>
                • Este billete es intransferible y no reembolsable
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Sistema de Reserva de Vuelos</strong> | Generado el {{ now()->format('d/m/Y H:i') }}</p>
            <p>Para soporte técnico contacte a soporte@reservavuelos.com</p>
        </div>
    </div>
</body>
</html>