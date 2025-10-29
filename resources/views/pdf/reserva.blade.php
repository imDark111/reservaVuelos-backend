<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reserva - {{ $reserva->numero_reserva }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .title {
            font-size: 18px;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 200px;
            padding: 5px 0;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .vuelo-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .pasajero-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
        }
        .estado-confirmada {
            color: #28a745;
            font-weight: bold;
        }
        .estado-pendiente {
            color: #ffc107;
            font-weight: bold;
        }
        .estado-cancelada {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ReservaVuelos</div>
        <div class="title">Detalles de Reserva</div>
    </div>

    <div class="section">
        <div class="section-title">Información General</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Número de Reserva:</div>
                <div class="info-value">{{ $reserva->numero_reserva }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">
                    <span class="estado-{{ strtolower($reserva->estado) }}">
                        {{ ucfirst($reserva->estado) }}
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo de Viaje:</div>
                <div class="info-value">{{ $reserva->tipo_viaje === 'ida' ? 'Solo Ida' : 'Ida y Vuelta' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Precio Total:</div>
                <div class="info-value">${{ number_format($reserva->precio_total, 2) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Creación:</div>
                <div class="info-value">{{ $reserva->fecha_creacion ? $reserva->fecha_creacion->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Vencimiento:</div>
                <div class="info-value">{{ $reserva->fecha_vencimiento ? $reserva->fecha_vencimiento->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
        </div>
    </div>

    @if($reserva->usuario)
    <div class="section">
        <div class="section-title">Información del Usuario</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">{{ $reserva->usuario->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $reserva->usuario->email }}</div>
            </div>
        </div>
    </div>
    @endif

    @if($reserva->pasajeros && count($reserva->pasajeros) > 0)
    <div class="section">
        <div class="section-title">Información del Pasajero Principal</div>
        @php $pasajeroPrincipal = $reserva->pasajeros[0]; @endphp
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">{{ $pasajeroPrincipal['nombres'] }} {{ $pasajeroPrincipal['apellidos'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $pasajeroPrincipal['email'] ?? 'No especificado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Documento:</div>
                <div class="info-value">{{ $pasajeroPrincipal['tipo_documento'] }} {{ $pasajeroPrincipal['numero_documento'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Nacimiento:</div>
                <div class="info-value">{{ isset($pasajeroPrincipal['fecha_nacimiento']) ? \Carbon\Carbon::parse($pasajeroPrincipal['fecha_nacimiento'])->format('d/m/Y') : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Nacionalidad:</div>
                <div class="info-value">{{ $pasajeroPrincipal['nacionalidad'] }}</div>
            </div>
            @if(isset($pasajeroPrincipal['telefono']) && $pasajeroPrincipal['telefono'])
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $pasajeroPrincipal['telefono'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($reserva->vuelos_data && $reserva->vuelos_data->count() > 0)
    <div class="section">
        <div class="section-title">Vuelos</div>
        @foreach($reserva->vuelos_data as $vuelo)
        <div class="vuelo-card">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Número de Vuelo:</div>
                    <div class="info-value">{{ $vuelo->numero_vuelo }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Origen:</div>
                    <div class="info-value">
                        @if($vuelo->origen)
                            {{ $vuelo->origen->nombre }}
                            @if($vuelo->origen->ciudad && $vuelo->origen->pais)
                                , {{ $vuelo->origen->ciudad }}, {{ $vuelo->origen->pais }}
                            @endif
                            @if($vuelo->origen->codigo_iata)
                                ({{ $vuelo->origen->codigo_iata }})
                            @endif
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Destino:</div>
                    <div class="info-value">
                        @if($vuelo->destino)
                            {{ $vuelo->destino->nombre }}
                            @if($vuelo->destino->ciudad && $vuelo->destino->pais)
                                , {{ $vuelo->destino->ciudad }}, {{ $vuelo->destino->pais }}
                            @endif
                            @if($vuelo->destino->codigo_iata)
                                ({{ $vuelo->destino->codigo_iata }})
                            @endif
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha y Hora de Salida:</div>
                    <div class="info-value">{{ $vuelo->fecha_salida ? $vuelo->fecha_salida->format('d/m/Y H:i') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha y Hora de Llegada:</div>
                    <div class="info-value">{{ $vuelo->fecha_llegada ? $vuelo->fecha_llegada->format('d/m/Y H:i') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Aerolínea:</div>
                    <div class="info-value">{{ $vuelo->avion && $vuelo->avion->aerolinea ? $vuelo->avion->aerolinea->nombre : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Avión:</div>
                    <div class="info-value">{{ $vuelo->avion ? $vuelo->avion->modelo : 'N/A' }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($reserva->pasajeros && count($reserva->pasajeros) > 0)
    <div class="section">
        <div class="section-title">Pasajeros</div>
        @foreach($reserva->pasajeros as $index => $pasajero)
        <div class="pasajero-card">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Pasajero {{ $index + 1 }}:</div>
                    <div class="info-value">{{ $pasajero['nombres'] }} {{ $pasajero['apellidos'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Documento:</div>
                    <div class="info-value">{{ $pasajero['tipo_documento'] }} {{ $pasajero['numero_documento'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha de Nacimiento:</div>
                    <div class="info-value">{{ isset($pasajero['fecha_nacimiento']) ? \Carbon\Carbon::parse($pasajero['fecha_nacimiento'])->format('d/m/Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nacionalidad:</div>
                    <div class="info-value">{{ $pasajero['nacionalidad'] }}</div>
                </div>
                @if(isset($pasajero['email']) && $pasajero['email'])
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{{ $pasajero['email'] }}</div>
                </div>
                @endif
                @if(isset($pasajero['telefono']) && $pasajero['telefono'])
                <div class="info-row">
                    <div class="info-label">Teléfono:</div>
                    <div class="info-value">{{ $pasajero['telefono'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($reserva->observaciones)
    <div class="section">
        <div class="section-title">Observaciones</div>
        <p>{{ $reserva->observaciones }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Este documento es generado automáticamente por el sistema ReservaVuelos</p>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>