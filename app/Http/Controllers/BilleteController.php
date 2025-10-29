<?php

namespace App\Http\Controllers;

use App\Models\Billete;
use App\Models\Reserva;
use App\Models\Pasajero;
use App\Models\Vuelo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class BilleteController extends Controller
{
    /**
     * Listar billetes del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        \Log::info('Buscando billetes', [
            'user_id' => $request->user()->_id,
            'reserva_id' => $request->get('reserva_id'),
            'estado_billete' => $request->get('estado_billete')
        ]);

        $query = Billete::with(['reserva', 'pasajero', 'vuelo', 'usuario'])
                       ->where('usuario_id', $request->user()->_id);

        // Filtros
        if ($request->has('estado_billete')) {
            $query->where('estado_billete', $request->estado_billete);
        }

        if ($request->has('reserva_id')) {
            $query->where('reserva_id', $request->reserva_id);
        }

        if ($request->has('vuelo_codigo')) {
            $query->whereHas('vuelo', function($q) use ($request) {
                $q->where('codigo_vuelo', 'like', '%' . $request->vuelo_codigo . '%');
            });
        }

        $billetes = $query->orderBy('fecha_emision', 'desc');

        // Si se filtra por reserva_id, no paginar (devolver todos)
        if ($request->has('reserva_id')) {
            $billetes = $billetes->get();
        } else {
            $billetes = $billetes->paginate($request->get('per_page', 15));
        }

        \Log::info('Billetes encontrados', [
            'count' => $billetes->count(),
            'is_paginated' => $request->has('reserva_id') ? false : true
        ]);

        return response()->json([
            'success' => true,
            'data' => $billetes,
            'message' => 'Billetes obtenidos exitosamente'
        ]);
    }

    /**
     * Crear billetes a partir de una reserva (comprar)
     */
    public function comprar(Request $request, $reservaId): JsonResponse
    {
        \Log::info('Intentando comprar billete', [
            'reserva_id' => $reservaId,
            'user_id' => $request->user()->_id,
            'user_email' => $request->user()->email
        ]);

        $reserva = Reserva::with(['usuario', 'vuelos'])
                         ->where('_id', $reservaId)
                         ->first();

        \Log::info('Reserva encontrada (sin scope)', [
            'reserva' => $reserva ? 'SI' : 'NO',
            'reserva_id' => $reserva?->_id,
            'reserva_usuario_id' => $reserva?->usuario_id,
            'request_user_id' => $request->user()->_id
        ]);

        // TEMPORAL: Permitir cualquier reserva para testing
        if (!$reserva) {
            \Log::warning('Reserva no encontrada para compra', [
                'reserva_id' => $reservaId,
                'user_id' => $request->user()->_id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        if (!$reserva) {
            \Log::warning('Reserva no encontrada para compra', [
                'reserva_id' => $reservaId,
                'user_id' => $request->user()->_id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        if (!in_array($reserva->estado, ['pendiente', 'confirmada'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden comprar billetes de reservas pendientes o confirmadas'
            ], 400);
        }

        if ($reserva->haVencido()) {
            $reserva->marcarComoVencida();
            return response()->json([
                'success' => false,
                'message' => 'La reserva ha vencido'
            ], 400);
        }

        $request->validate([
            'metodo_pago' => 'required|in:tarjeta_credito,transferencia,efectivo',
            'datos_pago' => 'required|array',
            'metodo_entrega' => 'required|in:email,aeropuerto,domicilio',
            'direccion_entrega' => 'required_if:metodo_entrega,domicilio|nullable|string',
            'asientos' => 'nullable|array',
            'clase_servicio' => 'required|in:economica,premium,ejecutiva,primera'
        ]);

        try {
            // Verificar que el usuario tenga tarjeta de crédito registrada
            $usuario = $request->user();
            if ($request->metodo_pago === 'tarjeta_credito' && !$usuario->tarjeta_credito) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene una tarjeta de crédito registrada'
                ], 400);
            }

            // Procesar pago (simulado)
            $pagoExitoso = $this->procesarPago($request->metodo_pago, $request->datos_pago, $reserva->precio_total);

            if (!$pagoExitoso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en el procesamiento del pago'
                ], 400);
            }

            // Obtener pasajeros de la reserva
            $pasajeros = Pasajero::where('reserva_id', $reserva->_id)->get();
            $billetes = [];

            foreach ($reserva->vuelo_ids as $index => $vueloId) {
                $vuelo = Vuelo::find($vueloId);
                
                foreach ($pasajeros as $pasajeroIndex => $pasajero) {
                    // Asignar asiento si no se especificó
                    $asiento = $request->asientos[$index][$pasajeroIndex] ?? $this->asignarAsientoAutomatico($vuelo, $request->clase_servicio);
                    
                    $billete = Billete::create([
                        'codigo_billete' => Billete::generarCodigoBillete(),
                        'reserva_id' => $reserva->_id,
                        'pasajero_id' => $pasajero->_id,
                        'vuelo_id' => $vueloId,
                        'usuario_id' => $usuario->_id,
                        'asiento' => $asiento,
                        'clase_servicio' => $request->clase_servicio,
                        'precio_pagado' => $vuelo->tarifas[$request->clase_servicio] ?? $vuelo->precio_base,
                        'estado_billete' => 'emitido',
                        'fecha_emision' => now(),
                        'fecha_vencimiento' => $vuelo->fecha_salida->addDays(1),
                        'metodo_entrega' => $request->metodo_entrega,
                        'direccion_entrega' => $request->get('direccion_entrega'),
                        'check_in_realizado' => false,
                        'equipaje_facturado' => []
                    ]);

                    $billetes[] = $billete;
                }
            }

            // Actualizar estado de la reserva
            $reserva->update(['estado' => 'confirmada']);

            // Actualizar asientos disponibles en vuelos
            $this->actualizarAsientosDisponibles($reserva->vuelo_ids, $request->clase_servicio, count($pasajeros));

            return response()->json([
                'success' => true,
                'data' => [
                    'billetes' => $billetes,
                    'reserva' => $reserva->fresh()
                ],
                'message' => 'Billetes comprados exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al comprar billetes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un billete específico
     */
    public function show($id, Request $request): JsonResponse
    {
        $billete = Billete::with(['reserva', 'pasajero', 'vuelo', 'usuario'])
                         ->where('_id', $id)
                         ->where('usuario_id', $request->user()->_id)
                         ->first();

        if (!$billete) {
            return response()->json([
                'success' => false,
                'message' => 'Billete no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $billete,
            'message' => 'Billete encontrado'
        ]);
    }

    /**
     * Realizar check-in
     */
    public function checkIn($id, Request $request): JsonResponse
    {
        $billete = Billete::with(['vuelo'])
                         ->where('_id', $id)
                         ->where('usuario_id', $request->user()->_id)
                         ->first();

        if (!$billete) {
            return response()->json([
                'success' => false,
                'message' => 'Billete no encontrado'
            ], 404);
        }

        if ($billete->estado_billete !== 'emitido') {
            return response()->json([
                'success' => false,
                'message' => 'El billete no está en estado válido para check-in'
            ], 400);
        }

        if ($billete->check_in_realizado) {
            return response()->json([
                'success' => false,
                'message' => 'El check-in ya fue realizado'
            ], 400);
        }

        // Verificar que el check-in esté en el período permitido (24h antes hasta 1h antes del vuelo)
        $ahora = now();
        $salidaVuelo = $billete->vuelo->fecha_salida;
        $checkInInicio = $salidaVuelo->copy()->subHours(24);
        $checkInCierre = $salidaVuelo->copy()->subHours(1);

        if ($ahora < $checkInInicio) {
            return response()->json([
                'success' => false,
                'message' => 'El check-in aún no está disponible'
            ], 400);
        }

        if ($ahora > $checkInCierre) {
            return response()->json([
                'success' => false,
                'message' => 'El período de check-in ha cerrado'
            ], 400);
        }

        $request->validate([
            'equipaje_facturado' => 'nullable|array',
            'equipaje_facturado.*.peso' => 'nullable|numeric|min:0|max:50',
            'equipaje_facturado.*.descripcion' => 'nullable|string|max:255',
            'necesidades_especiales' => 'nullable|array'
        ]);

        try {
            $billete->update([
                'check_in_realizado' => true,
                'fecha_check_in' => now(),
                'equipaje_facturado' => $request->get('equipaje_facturado', [])
            ]);

            // Actualizar necesidades especiales del pasajero si se proporcionan
            if ($request->has('necesidades_especiales')) {
                $billete->pasajero->update([
                    'necesidades_especiales' => $request->necesidades_especiales
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $billete->fresh(['vuelo', 'pasajero']),
                'message' => 'Check-in realizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar un billete
     */
    public function cancelar($id, Request $request): JsonResponse
    {
        $billete = Billete::with(['vuelo'])
                         ->where('_id', $id)
                         ->where('usuario_id', $request->user()->_id)
                         ->first();

        if (!$billete) {
            return response()->json([
                'success' => false,
                'message' => 'Billete no encontrado'
            ], 404);
        }

        if ($billete->estado_billete !== 'emitido') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar billetes emitidos'
            ], 400);
        }

        // Verificar que el vuelo no haya despegado
        if ($billete->vuelo->fecha_salida <= now()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar un billete de un vuelo que ya despegó'
            ], 400);
        }

        try {
            $billete->update(['estado_billete' => 'cancelado']);

            // Procesar reembolso (simulado)
            $this->procesarReembolso($billete);

            return response()->json([
                'success' => true,
                'message' => 'Billete cancelado exitosamente. El reembolso será procesado.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar billete: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los billetes (admin)
     */
    public function todos(Request $request): JsonResponse
    {
        $query = Billete::with(['reserva', 'pasajero', 'vuelo', 'usuario']);

        // Filtros para admin
        if ($request->has('estado_billete')) {
            $query->where('estado_billete', $request->estado_billete);
        }

        if ($request->has('codigo_billete')) {
            $query->where('codigo_billete', 'like', '%' . $request->codigo_billete . '%');
        }

        if ($request->has('vuelo_id')) {
            $query->where('vuelo_id', $request->vuelo_id);
        }

        $billetes = $query->orderBy('fecha_emision', 'desc')
                         ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $billetes,
            'message' => 'Todos los billetes obtenidos exitosamente'
        ]);
    }

    /**
     * Procesar pago (simulado)
     */
    private function procesarPago($metodoPago, $datosPago, $monto)
    {
        // Simulación de procesamiento de pago
        // En una implementación real, aquí se integraría con una pasarela de pago
        
        if ($metodoPago === 'tarjeta_credito') {
            // Validar datos de tarjeta
            if (empty($datosPago['numero']) || empty($datosPago['cvv'])) {
                return false;
            }
        }

        // Simular éxito del 95% de las veces
        return rand(1, 100) <= 95;
    }

    /**
     * Asignar asiento automáticamente
     */
    private function asignarAsientoAutomatico($vuelo, $claseServicio)
    {
        // Lógica simplificada para asignar asiento
        $configuracion = $vuelo->avion->configuracion_asientos[$claseServicio] ?? null;
        
        if (!$configuracion) {
            return '1A'; // Asiento por defecto
        }

        // En una implementación real, se buscaría el primer asiento disponible
        $filas = explode('-', $configuracion['filas']);
        $filaInicial = (int)$filas[0];
        $letras = ['A', 'B', 'C', 'D', 'E', 'F'];
        
        return $filaInicial . $letras[array_rand($letras)];
    }

    /**
     * Actualizar asientos disponibles en vuelos
     */
    private function actualizarAsientosDisponibles($vueloIds, $claseServicio, $cantidadPasajeros)
    {
        foreach ($vueloIds as $vueloId) {
            $vuelo = Vuelo::find($vueloId);
            if ($vuelo) {
                $asientosDisponibles = $vuelo->asientos_disponibles;
                $asientosDisponibles[$claseServicio] = max(0, $asientosDisponibles[$claseServicio] - $cantidadPasajeros);
                
                $vuelo->update(['asientos_disponibles' => $asientosDisponibles]);
            }
        }
    }

    /**
     * Procesar reembolso (simulado)
     */
    private function procesarReembolso($billete)
    {
        // Simulación de procesamiento de reembolso
        // En una implementación real, aquí se integraría con el sistema de pagos para revertir la transacción
        
        // Calcular monto de reembolso (con penalización si aplica)
        $montoReembolso = $billete->precio_pagado * 0.8; // 20% de penalización
        
        // Registrar en logs o sistema de contabilidad
        \Log::info("Reembolso procesado para billete {$billete->codigo_billete}: ${$montoReembolso}");
    }

    /**
     * Comprar billetes (versión de prueba sin autenticación)
     */
    public function comprarTest(Request $request, $reservaId): JsonResponse
    {
        \Log::info('Comprar billete TEST - Reserva ID:', ['reserva_id' => $reservaId]);

        $reserva = Reserva::where('_id', $reservaId)->first();

        if (!$reserva) {
            \Log::warning('Reserva no encontrada en TEST', ['reserva_id' => $reservaId]);
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        if (!in_array($reserva->estado, ['pendiente', 'confirmada'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden comprar billetes de reservas pendientes o confirmadas'
            ], 400);
        }

        \Log::info('Reserva encontrada en TEST', [
            'reserva_id' => $reserva->_id,
            'estado_anterior' => $reserva->estado,
            'vuelo_ids' => $reserva->vuelo_ids
        ]);

        try {
            // Usar pasajeros de la reserva (array embebido)
            $pasajeros = $reserva->pasajeros ?? [];
            \Log::info('Pasajeros encontrados en reserva:', ['cantidad' => count($pasajeros)]);

            $billetes = [];

            // Para cada vuelo en la reserva
            foreach ($reserva->vuelo_ids as $index => $vueloId) {
                $vuelo = Vuelo::find($vueloId);
                \Log::info('Procesando vuelo:', ['vuelo_id' => $vueloId, 'vuelo_encontrado' => $vuelo ? 'SI' : 'NO']);

                if (!$vuelo) {
                    \Log::warning('Vuelo no encontrado, saltando:', ['vuelo_id' => $vueloId]);
                    continue;
                }

                // Para cada pasajero en la reserva
                foreach ($pasajeros as $pasajeroIndex => $pasajeroData) {
                    // Crear pasajero en la base de datos si no existe
                    $pasajero = Pasajero::firstOrCreate(
                        ['reserva_id' => $reserva->_id, 'nombres' => $pasajeroData['nombres'], 'apellidos' => $pasajeroData['apellidos']],
                        array_merge($pasajeroData, ['reserva_id' => $reserva->_id])
                    );

                    // Asignar asiento automático
                    $asiento = $this->asignarAsientoAutomatico($vuelo, 'economica');

                    $billete = Billete::create([
                        'codigo_billete' => Billete::generarCodigoBillete(),
                        'reserva_id' => $reserva->_id,
                        'pasajero_id' => $pasajero->_id,
                        'vuelo_id' => $vueloId,
                        'usuario_id' => $reserva->usuario_id,
                        'asiento' => $asiento,
                        'clase_servicio' => 'economica',
                        'precio_pagado' => $vuelo->tarifas['economica'] ?? $vuelo->precio_base,
                        'estado_billete' => 'emitido',
                        'fecha_emision' => now(),
                        'fecha_vencimiento' => $vuelo->fecha_salida->addDays(1),
                        'metodo_entrega' => 'email',
                        'check_in_realizado' => false,
                        'equipaje_facturado' => []
                    ]);

                    $billetes[] = $billete;
                    \Log::info('Billete creado:', [
                        'codigo' => $billete->codigo_billete,
                        'pasajero' => $pasajero->nombres . ' ' . $pasajero->apellidos,
                        'vuelo' => $vuelo->codigo_vuelo,
                        'asiento' => $asiento
                    ]);
                }
            }

            // Cambiar estado a confirmado
            $reserva->update(['estado' => 'confirmada']);

            \Log::info('Compra completada:', [
                'reserva_id' => $reserva->_id,
                'estado_nuevo' => $reserva->estado,
                'billetes_creados' => count($billetes)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'billetes' => $billetes,
                    'reserva' => $reserva->fresh()
                ],
                'message' => 'Billetes comprados exitosamente (TEST)'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en compra de billetes TEST:', [
                'reserva_id' => $reservaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar billete en formato PDF
     */
    public function descargarPDF($id, Request $request)
    {
        $billete = Billete::with(['reserva', 'pasajero', 'vuelo', 'usuario'])
                         ->where('_id', $id)
                         ->first();

        if (!$billete) {
            return response()->json(['message' => 'Billete no encontrado'], 404);
        }

        // Verificar que el billete pertenece al usuario autenticado
        if ($billete->usuario_id !== $request->user()->_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $this->generarPDFBillete($billete);
    }

    /**
     * Descargar billete en formato PDF (versión de prueba sin autenticación)
     */
    public function descargarPDFTest($id, Request $request)
    {
        $billete = Billete::with(['reserva', 'pasajero', 'vuelo', 'usuario'])
                         ->where('_id', $id)
                         ->first();

        if (!$billete) {
            return response()->json(['message' => 'Billete no encontrado'], 404);
        }

        return $this->generarPDFBillete($billete);
    }

    /**
     * Generar PDF del billete
     */
    private function generarPDFBillete($billete)
    {
        $pdf = \PDF::loadView('billete-pdf', compact('billete'));
        
        $filename = 'billete_' . $billete->codigo_billete . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Descargar PDF del billete por ID de reserva (sin autenticación - TEST)
     */
    public function descargarPDFPorReservaTest($reservaId, Request $request)
    {
        \Log::info('Descargando PDF por reserva ID (TEST)', ['reserva_id' => $reservaId]);

        // Verificar que la reserva existe y cargar relaciones
        $reserva = Reserva::with(['usuario'])->find($reservaId);
        \Log::info('Reserva encontrada:', [
            'reserva_id' => $reservaId,
            'reserva_exists' => $reserva ? 'SI' : 'NO',
            'reserva_estado' => $reserva ? $reserva->estado : 'N/A'
        ]);

        if (!$reserva) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        // Cargar vuelos manualmente
        $vueloIds = $reserva->vuelo_ids ?? [];
        if (!empty($vueloIds)) {
            $reserva->vuelos_data = Vuelo::with(['origen', 'destino', 'avion.aerolinea'])
                                   ->whereIn('_id', $vueloIds)
                                   ->get();
        } else {
            $reserva->vuelos_data = collect();
        }

        // Buscar el primer billete de la reserva
        $billete = Billete::with(['reserva', 'pasajero', 'vuelo', 'usuario'])
                         ->where('reserva_id', $reservaId)
                         ->orWhere('reserva_id', new \MongoDB\BSON\ObjectId($reservaId))
                         ->first();

        \Log::info('Búsqueda de billete completada:', [
            'reserva_id' => $reservaId,
            'billete_encontrado' => $billete ? 'SI' : 'NO',
            'billete_id' => $billete ? $billete->_id : 'N/A',
            'billete_codigo' => $billete ? $billete->codigo_billete : 'N/A'
        ]);

        if (!$billete) {
            \Log::warning('No se encontró billete para la reserva, creando billete temporal para PDF', ['reserva_id' => $reservaId]);

            // Crear un objeto billete temporal con datos de la reserva
            $billete = $this->crearBilleteTemporalParaPDF($reserva);
        }

        \Log::info('Generando PDF de billete', [
            'billete_codigo' => $billete->codigo_billete,
            'reserva_id' => $reservaId
        ]);

        return $this->generarPDFBillete($billete);
    }

    /**
     * Crear un objeto billete temporal para generar PDF cuando no existe billete
     */
    private function crearBilleteTemporalParaPDF($reserva)
    {
        // Obtener el primer pasajero de la reserva
        $primerPasajero = null;
        if (!empty($reserva->pasajeros) && is_array($reserva->pasajeros)) {
            $primerPasajero = $reserva->pasajeros[0];
        }

        // Obtener el primer vuelo
        $primerVuelo = null;
        if (isset($reserva->vuelos_data) && $reserva->vuelos_data->count() > 0) {
            $primerVuelo = $reserva->vuelos_data->first();
            \Log::info('Vuelo obtenido de vuelos_data', [
                'vuelo_id' => $primerVuelo->_id ?? 'N/A',
                'codigo_vuelo' => $primerVuelo->codigo_vuelo ?? 'N/A',
                'origen_codigo' => $primerVuelo->origen->codigo ?? 'N/A',
                'destino_codigo' => $primerVuelo->destino->codigo ?? 'N/A'
            ]);
        }

        // Si no hay vuelo cargado, intentar cargar el primer vuelo de vuelo_ids
        if (!$primerVuelo && !empty($reserva->vuelo_ids)) {
            $primerVuelo = Vuelo::with(['origen', 'destino', 'avion.aerolinea'])
                               ->find($reserva->vuelo_ids[0]);
            \Log::info('Vuelo cargado directamente', [
                'vuelo_id' => $primerVuelo->_id ?? 'N/A',
                'codigo_vuelo' => $primerVuelo->codigo_vuelo ?? 'N/A',
                'origen_codigo' => $primerVuelo->origen->codigo ?? 'N/A',
                'destino_codigo' => $primerVuelo->destino->codigo ?? 'N/A'
            ]);
        }

        // Crear objeto pasajero temporal si existe
        $pasajeroTemporal = null;
        if ($primerPasajero) {
            $pasajeroTemporal = (object) [
                'nombres' => $primerPasajero['nombres'] ?? '',
                'apellidos' => $primerPasajero['apellidos'] ?? '',
                'tipo_documento' => $primerPasajero['tipo_documento'] ?? 'dni',
                'numero_documento' => $primerPasajero['numero_documento'] ?? '',
                'fecha_nacimiento' => isset($primerPasajero['fecha_nacimiento']) ? Carbon::parse($primerPasajero['fecha_nacimiento']) : null,
                'nacionalidad' => $primerPasajero['nacionalidad'] ?? '',
                'telefono' => $primerPasajero['telefono'] ?? '',
                'email' => $primerPasajero['email'] ?? ''
            ];
        }

        // Obtener información de asientos y clase de las preferencias
        $asiento = null;
        $claseServicio = 'economica'; // Valor por defecto

        if (!empty($reserva->preferencias)) {
            // Buscar asiento específico para este pasajero
            if (isset($reserva->preferencias['asientos_preferidos']) && is_array($reserva->preferencias['asientos_preferidos'])) {
                // Asignar el primer asiento disponible o por orden de pasajero
                $asientosPreferidos = $reserva->preferencias['asientos_preferidos'];
                $indicePasajero = 0; // Para el primer pasajero
                if (isset($asientosPreferidos[$indicePasajero])) {
                    $asiento = $asientosPreferidos[$indicePasajero];
                }
            }

            // Verificar si hay información de clase
            if (isset($reserva->preferencias['clase_servicio'])) {
                $claseServicio = $reserva->preferencias['clase_servicio'];
            }
        }

        // Crear billete temporal
        $billeteTemporal = (object) [
            'codigo_billete' => 'TEMP-' . strtoupper(substr($reserva->_id, -8)),
            'reserva' => $reserva,
            'pasajero' => $pasajeroTemporal,
            'vuelo' => $primerVuelo,
            'usuario' => $reserva->usuario,
            'asiento' => $asiento,
            'clase_servicio' => $claseServicio,
            'precio_pagado' => $reserva->precio_total / count($reserva->pasajeros ?? [1]),
            'estado_billete' => 'emitido',
            'fecha_emision' => $reserva->fecha_creacion,
            'fecha_vencimiento' => $primerVuelo ? $primerVuelo->fecha_salida : null,
            'check_in_realizado' => false,
            'fecha_check_in' => null
        ];

        \Log::info('Billete temporal creado para PDF', [
            'codigo_temporal' => $billeteTemporal->codigo_billete,
            'usuario' => $reserva->usuario ? $reserva->usuario->nombres . ' ' . $reserva->usuario->apellidos : 'N/A',
            'pasajero' => $pasajeroTemporal ? $pasajeroTemporal->nombres . ' ' . $pasajeroTemporal->apellidos : 'N/A'
        ]);

        return $billeteTemporal;
    }

    /**
     * Generar PDF con información de reserva (cuando no hay billetes)
     */
    private function generarPDFReserva($reserva)
    {
        // Las relaciones ya están cargadas en el método principal
        $pdf = \PDF::loadView('pdf.reserva', compact('reserva'));

        $filename = 'reserva_' . $reserva->numero_reserva . '.pdf';

        return $pdf->download($filename);
    }
}