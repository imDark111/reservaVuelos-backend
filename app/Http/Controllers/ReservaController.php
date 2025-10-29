<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Vuelo;
use App\Models\User;
use App\Models\Pasajero;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReservaController extends Controller
{
    /**
     * Listar reservas del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->_id;
        \Log::info('Listando reservas para usuario:', ['user_id' => $userId]);
        
        $query = Reserva::with(['usuario', 'billetes'])
                       ->porUsuario($userId);

        // Filtros opcionales
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde')) {
            $query->where('fecha_creacion', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->where('fecha_creacion', '<=', $request->fecha_hasta);
        }

        $reservas = $query->orderBy('fecha_creacion', 'desc')
                         ->paginate($request->get('per_page', 10));

        \Log::info('Reservas encontradas:', ['count' => $reservas->total(), 'items' => $reservas->count()]);

        // Cargar vuelos manualmente con sus relaciones
        $reservas->getCollection()->transform(function ($reserva) {
            $reserva->load('usuario');
            $vueloIds = $reserva->vuelo_ids ?? [];
            if (!empty($vueloIds)) {
                $reserva->vuelos = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                                       ->whereIn('_id', $vueloIds)
                                       ->get();
            } else {
                $reserva->vuelos = collect();
            }
            return $reserva;
        });

        return response()->json([
            'success' => true,
            'data' => $reservas->items(), // Usar items() para obtener el array de datos
            'pagination' => [
                'current_page' => $reservas->currentPage(),
                'last_page' => $reservas->lastPage(),
                'per_page' => $reservas->perPage(),
                'total' => $reservas->total(),
            ],
            'message' => 'Reservas obtenidas exitosamente'
        ]);
    }

    /**
     * Crear una nueva reserva
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'vuelo_ids' => 'required|array|min:1',
            'vuelo_ids.*' => 'required|string',
            'tipo_viaje' => 'required|in:ida,ida_vuelta',
            'pasajeros' => 'required|array|min:1|max:9',
            'pasajeros.*.nombres' => 'required|string|max:100',
            'pasajeros.*.apellidos' => 'required|string|max:100',
            'pasajeros.*.tipo_documento' => 'required|in:cedula,pasaporte',
            'pasajeros.*.numero_documento' => 'required|string',
            'pasajeros.*.fecha_nacimiento' => 'required|date|before:today',
            'pasajeros.*.nacionalidad' => 'required|string',
            'pasajeros.*.email' => 'nullable|email',
            'pasajeros.*.telefono' => 'nullable|string',
            'preferencias' => 'nullable|array',
            'observaciones' => 'nullable|string'
        ]);

        try {
            // Verificar disponibilidad de vuelos
            $vuelos = Vuelo::whereIn('_id', $request->vuelo_ids)->get();
            
            if ($vuelos->count() !== count($request->vuelo_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uno o más vuelos no están disponibles'
                ], 404);
            }

            // Verificar que los vuelos estén activos
            foreach ($vuelos as $vuelo) {
                if (!$vuelo->activo || $vuelo->estado_vuelo === 'cancelado') {
                    return response()->json([
                        'success' => false,
                        'message' => "El vuelo {$vuelo->codigo_vuelo} no está disponible"
                    ], 400);
                }
            }

            // Calcular precio total
            $precioTotal = 0;
            $numPasajeros = count($request->pasajeros);
            
            foreach ($vuelos as $vuelo) {
                $precioTotal += $vuelo->precio_base * $numPasajeros;
            }

            // Crear reserva
            $reserva = Reserva::create([
                'numero_reserva' => Reserva::generarNumeroReserva(),
                'usuario_id' => $request->user()->_id,
                'vuelo_ids' => $request->vuelo_ids,
                'tipo_viaje' => $request->tipo_viaje,
                'pasajeros' => $request->pasajeros,
                'estado' => 'pendiente',
                'fecha_creacion' => now(),
                'fecha_vencimiento' => now()->addHours(24), // 24 horas para confirmar
                'precio_total' => $precioTotal,
                'preferencias' => $request->get('preferencias', []),
                'observaciones' => $request->get('observaciones', '')
            ]);

                // Disminuir stock de asientos disponibles en cada vuelo (solo clase económica por ahora)
                foreach ($vuelos as $vuelo) {
                    $asientosDisponibles = $vuelo->asientos_disponibles ?? [];
                    if (isset($asientosDisponibles['economica']) && $asientosDisponibles['economica'] > 0) {
                        $asientosDisponibles['economica'] -= count($request->pasajeros);
                        if ($asientosDisponibles['economica'] < 0) {
                            $asientosDisponibles['economica'] = 0;
                        }
                        $vuelo->asientos_disponibles = $asientosDisponibles;
                        $vuelo->save();
                    }
                }

            // Crear registros de pasajeros
            foreach ($request->pasajeros as $pasajeroData) {
                Pasajero::create(array_merge($pasajeroData, [
                    'reserva_id' => $reserva->_id,
                    'tipo_pasajero' => $this->determinarTipoPasajero($pasajeroData['fecha_nacimiento'])
                ]));
            }

            // Cargar vuelos manualmente con sus relaciones para la respuesta
            $reserva->load('usuario');
            $vueloIds = $reserva->vuelo_ids ?? [];
            if (!empty($vueloIds)) {
                $reserva->vuelos = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                                       ->whereIn('_id', $vueloIds)
                                       ->get();
            } else {
                $reserva->vuelos = collect();
            }

            return response()->json([
                'success' => true,
                'data' => $reserva,
                'message' => 'Reserva creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una reserva específica
     */
    public function show($id, Request $request): JsonResponse
    {
        $reserva = Reserva::with(['usuario', 'billetes'])
                         ->where('_id', $id)
                         ->porUsuario($request->user()->_id)
                         ->first();

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        \Log::info('Reserva encontrada en show:', [
            'reserva_id' => $reserva->_id,
            'estado' => $reserva->estado,
            'billetes_count' => $reserva->billetes ? $reserva->billetes->count() : 0,
            'billetes_loaded' => $reserva->relationLoaded('billetes')
        ]);

        // Verificar si ha vencido
        $reserva->marcarComoVencida();

        // Cargar vuelos manualmente con sus relaciones
        $reserva->load('usuario');
        $vueloIds = $reserva->vuelo_ids ?? [];
        if (!empty($vueloIds)) {
            $reserva->vuelos = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                                   ->whereIn('_id', $vueloIds)
                                   ->get();
        } else {
            $reserva->vuelos = collect();
        }

        return response()->json([
            'success' => true,
            'data' => $reserva,
            'message' => 'Reserva encontrada'
        ]);
    }

    /**
     * Actualizar una reserva
     */
    public function update(Request $request, $id): JsonResponse
    {
        $reserva = Reserva::where('_id', $id)
                         ->porUsuario($request->user()->_id)
                         ->first();

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        // Solo se pueden modificar reservas pendientes
        if ($reserva->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden modificar reservas pendientes'
            ], 400);
        }

        $request->validate([
            'preferencias' => 'nullable|array',
            'observaciones' => 'nullable|string'
        ]);

        try {
            $reserva->update($request->only(['preferencias', 'observaciones']));

            return response()->json([
                'success' => true,
                'data' => $reserva->fresh()->load(['vuelos', 'usuario']),
                'message' => 'Reserva actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una reserva
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $reserva = Reserva::where('_id', $id)
                         ->porUsuario($request->user()->_id)
                         ->first();

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        // Solo se pueden cancelar reservas pendientes o confirmadas
        if (!in_array($reserva->estado, ['pendiente', 'confirmada'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar esta reserva'
            ], 400);
        }

        try {
            $reserva->update(['estado' => 'cancelada']);

            // Liberar asientos (aquí se implementaría la lógica)
            $this->liberarAsientos($reserva);

            return response()->json([
                'success' => true,
                'message' => 'Reserva cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar una reserva
     */
    public function confirmar($id, Request $request): JsonResponse
    {
        $reserva = Reserva::where('_id', $id)
                         ->porUsuario($request->user()->_id)
                         ->first();

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        if ($reserva->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden confirmar reservas pendientes'
            ], 400);
        }

        if ($reserva->haVencido()) {
            $reserva->marcarComoVencida();
            return response()->json([
                'success' => false,
                'message' => 'La reserva ha vencido'
            ], 400);
        }

        try {
            $reserva->update([
                'estado' => 'confirmada',
                'fecha_vencimiento' => now()->addDays(7) // 7 días para pagar
            ]);

            return response()->json([
                'success' => true,
                'data' => $reserva->fresh(),
                'message' => 'Reserva confirmada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar reserva como pagada
     */
    public function pagar($id, Request $request): JsonResponse
    {
        $reserva = Reserva::where('_id', $id)
                         ->porUsuario($request->user()->_id)
                         ->first();

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        if ($reserva->estado !== 'confirmada') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden pagar reservas confirmadas'
            ], 400);
        }

        try {
            $reserva->update(['estado' => 'pagada']);

            return response()->json([
                'success' => true,
                'data' => $reserva->fresh(),
                'message' => 'Reserva marcada como pagada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar reserva como pagada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una reserva
     */
    public function cancelar($id, Request $request): JsonResponse
    {
        \Log::info('Intentando cancelar reserva', ['reserva_id' => $id, 'user_id' => $request->user()?->_id]);

        $reserva = Reserva::where('_id', $id)
                         ->porUsuario($request->user()->_id)
                         ->first();

        \Log::info('Reserva encontrada para cancelar', [
            'reserva_id' => $id,
            'reserva_encontrada' => $reserva ? 'SI' : 'NO',
            'reserva_estado' => $reserva ? $reserva->estado : 'N/A',
            'user_id' => $request->user()?->_id
        ]);

        if (!$reserva) {
            \Log::warning('Reserva no encontrada para cancelar', ['reserva_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        if (!in_array($reserva->estado, ['pendiente', 'confirmada', 'pagada'])) {
            \Log::warning('Estado de reserva no permite cancelación', [
                'reserva_id' => $id,
                'estado_actual' => $reserva->estado
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar una reserva en este estado'
            ], 400);
        }

        try {
            $reserva->update(['estado' => 'cancelada']);
            \Log::info('Reserva cancelada exitosamente', ['reserva_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $reserva->fresh(),
                'message' => 'Reserva cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al cancelar reserva', [
                'reserva_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar reserva (TEST - sin autenticación)
     */
    public function cancelarTest($id, Request $request): JsonResponse
    {
        \Log::info('Intentando cancelar reserva (TEST)', ['reserva_id' => $id]);

        $reserva = Reserva::find($id);

        \Log::info('Reserva encontrada para cancelar (TEST)', [
            'reserva_id' => $id,
            'reserva_encontrada' => $reserva ? 'SI' : 'NO',
            'reserva_estado' => $reserva ? $reserva->estado : 'N/A'
        ]);

        if (!$reserva) {
            \Log::warning('Reserva no encontrada para cancelar (TEST)', ['reserva_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        if (!in_array($reserva->estado, ['pendiente', 'confirmada', 'pagada'])) {
            \Log::warning('Estado de reserva no permite cancelación (TEST)', [
                'reserva_id' => $id,
                'estado_actual' => $reserva->estado
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar una reserva en este estado'
            ], 400);
        }

        try {
            $reserva->update(['estado' => 'cancelada']);
            \Log::info('Reserva cancelada exitosamente (TEST)', ['reserva_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $reserva->fresh(),
                'message' => 'Reserva cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al cancelar reserva (TEST)', [
                'reserva_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las reservas (admin)
     */
    public function todas(Request $request): JsonResponse
    {
        $query = Reserva::query();

        // Filtros para admin
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('usuario_email')) {
            $query->whereHas('usuario', function($q) use ($request) {
                $q->where('email', 'like', '%' . $request->usuario_email . '%');
            });
        }

        if ($request->has('numero_reserva')) {
            $query->where('numero_reserva', 'like', '%' . $request->numero_reserva . '%');
        }

        $reservas = $query->orderBy('fecha_creacion', 'desc')
                         ->paginate($request->get('per_page', 20));

        // Cargar vuelos manualmente con sus relaciones (igual que en index)
        $reservas->getCollection()->transform(function ($reserva) {
            $reserva->load('usuario');
            $vueloIds = $reserva->vuelo_ids ?? [];
            if (!empty($vueloIds)) {
                // Si vuelo_ids es un string, convertirlo a array
                if (is_string($vueloIds)) {
                    $vueloIds = [$vueloIds];
                }
                $reserva->vuelos = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                                       ->whereIn('_id', $vueloIds)
                                       ->get();
            } else {
                $reserva->vuelos = collect();
            }
            return $reserva;
        });

        return response()->json([
            'success' => true,
            'data' => $reservas,
            'message' => 'Todas las reservas obtenidas exitosamente'
        ]);
    }

    /**
     * Determinar tipo de pasajero por edad
     */
    private function determinarTipoPasajero($fechaNacimiento)
    {
        $edad = Carbon::parse($fechaNacimiento)->diffInYears(now());
        
        if ($edad < 2) {
            return 'infante';
        } elseif ($edad < 12) {
            return 'menor';
        } else {
            return 'adulto';
        }
    }

    /**
     * Crear reserva de prueba para el usuario actual
     */
    public function crearReservaTest(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Buscar un vuelo existente
        $vuelo = Vuelo::first();
        if (!$vuelo) {
            return response()->json([
                'success' => false,
                'message' => 'No hay vuelos disponibles'
            ], 400);
        }

        $reserva = Reserva::create([
            'numero_reserva' => 'TEST-' . time(),
            'usuario_id' => $user->_id,
            'vuelo_ids' => [$vuelo->_id],
            'tipo_viaje' => 'ida',
            'pasajeros' => [
                [
                    'nombres' => 'Usuario',
                    'apellidos' => 'Test',
                    'tipo_documento' => 'cedula',
                    'numero_documento' => '123456789',
                    'fecha_nacimiento' => '1990-01-01',
                    'nacionalidad' => 'Ecuador',
                    'tipo_pasajero' => 'adulto'
                ]
            ],
            'estado' => 'pendiente',
            'precio_total' => $vuelo->precio_base ?? 100,
            'fecha_creacion' => now(),
            'fecha_vencimiento' => now()->addDays(7)
        ]);

        return response()->json([
            'success' => true,
            'data' => $reserva,
            'message' => 'Reserva de prueba creada'
        ]);
    }

    /**
     * Liberar asientos de una reserva cancelada
     */
    private function liberarAsientos($reserva)
    {
        // Aquí se implementaría la lógica para liberar asientos
        // Por ahora solo un placeholder
        foreach ($reserva->vuelo_ids as $vueloId) {
            $vuelo = Vuelo::find($vueloId);
            if ($vuelo) {
                // Incrementar asientos disponibles según la clase
                // Esta lógica dependería de cómo se manejen los asientos
            }
        }
    }
}