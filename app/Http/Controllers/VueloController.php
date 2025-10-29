<?php

namespace App\Http\Controllers;

use App\Models\Vuelo;
use App\Models\Aeropuerto;
use App\Models\Avion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class VueloController extends Controller
{
    /**
     * Listar todos los vuelos con filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                     ->activos();

        // Filtros
        if ($request->has('origen')) {
            $query->where('origen_id', $request->origen);
        }

        if ($request->has('destino')) {
            $query->where('destino_id', $request->destino);
        }

        if ($request->has('fecha_salida')) {
            $query->whereDate('fecha_salida', $request->fecha_salida);
        }

        if ($request->has('precio_min')) {
            $query->where('precio_base', '>=', $request->precio_min);
        }

        if ($request->has('precio_max')) {
            $query->where('precio_base', '<=', $request->precio_max);
        }

        if ($request->has('aerolinea')) {
            $query->whereHas('avion.aerolinea', function($q) use ($request) {
                $q->where('codigo_iata', $request->aerolinea);
            });
        }

        if ($request->has('solo_directos') && $request->solo_directos == 'true') {
            $query->where('es_directo', true);
        }

        // Ordenamiento
        $ordenPor = $request->get('orden_por', 'fecha_salida');
        $direccion = $request->get('direccion', 'asc');
        
        if ($ordenPor === 'precio') {
            $query->orderBy('precio_base', $direccion);
        } else {
            $query->orderBy('fecha_salida', $direccion);
        }

        // Si no se especifica per_page o es 0, devolver todos los vuelos
        $perPage = $request->get('per_page', 0);
        if ($perPage > 0) {
            $vuelos = $query->paginate($perPage);
        } else {
            // Devolver todos los vuelos sin paginación
            $vuelosCollection = $query->get();
            $vuelos = (object) [
                'data' => $vuelosCollection,
                'total' => $vuelosCollection->count(),
                'per_page' => $vuelosCollection->count(),
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => $vuelosCollection->count()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $vuelos,
            'message' => 'Vuelos obtenidos exitosamente'
        ]);
    }

    /**
     * Crear un nuevo vuelo
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_vuelo' => 'required|string|unique:vuelos',
            'avion_id' => 'required|exists:aviones,_id',
            'origen_id' => 'required|exists:aeropuertos,_id',
            'destino_id' => 'required|exists:aeropuertos,_id|different:origen_id',
            'fecha_salida' => 'required|date|after:now',
            'fecha_llegada' => 'required|date|after:fecha_salida',
            'precio_base' => 'required|numeric|min:0',
            'tarifas' => 'required|array',
            'tarifas.economica' => 'required|numeric|min:0',
            'tarifas.premium' => 'nullable|numeric|min:0',
            'tarifas.ejecutiva' => 'nullable|numeric|min:0',
            'es_directo' => 'boolean'
        ]);

        try {
            // Calcular duración
            $salida = Carbon::parse($request->fecha_salida);
            $llegada = Carbon::parse($request->fecha_llegada);
            $duracionMinutos = $llegada->diffInMinutes($salida);

            // Obtener avión para asientos disponibles
            $avion = Avion::find($request->avion_id);
            $asientosDisponibles = $avion->configuracion_asientos;

            $vuelo = Vuelo::create([
                'codigo_vuelo' => $request->codigo_vuelo,
                'avion_id' => $request->avion_id,
                'origen_id' => $request->origen_id,
                'destino_id' => $request->destino_id,
                'fecha_salida' => $request->fecha_salida,
                'fecha_llegada' => $request->fecha_llegada,
                'duracion_minutos' => $duracionMinutos,
                'estado_vuelo' => 'programado',
                'precio_base' => $request->precio_base,
                'tarifas' => $request->tarifas,
                'asientos_disponibles' => [
                    'economica' => $asientosDisponibles['economica']['total'] ?? 0,
                    'premium' => $asientosDisponibles['premium']['total'] ?? 0,
                    'ejecutiva' => $asientosDisponibles['ejecutiva']['total'] ?? 0
                ],
                'es_directo' => $request->get('es_directo', true),
                'activo' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $vuelo->load(['avion.aerolinea', 'origen', 'destino']),
                'message' => 'Vuelo creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear vuelo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un vuelo específico
     */
    public function show($id): JsonResponse
    {
        $vuelo = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                     ->find($id);

        if (!$vuelo) {
            return response()->json([
                'success' => false,
                'message' => 'Vuelo no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $vuelo,
            'message' => 'Vuelo encontrado'
        ]);
    }

    /**
     * Actualizar un vuelo
     */
    public function update(Request $request, $id): JsonResponse
    {
        $vuelo = Vuelo::find($id);

        if (!$vuelo) {
            return response()->json([
                'success' => false,
                'message' => 'Vuelo no encontrado'
            ], 404);
        }

        $request->validate([
            'codigo_vuelo' => 'sometimes|required|string|unique:vuelos,codigo_vuelo,' . $id,
            'avion_id' => 'sometimes|required|exists:aviones,_id',
            'origen_id' => 'sometimes|required|exists:aeropuertos,_id',
            'destino_id' => 'sometimes|required|exists:aeropuertos,_id|different:origen_id',
            'fecha_salida' => 'sometimes|required|date',
            'fecha_llegada' => 'sometimes|required|date|after:fecha_salida',
            'precio_base' => 'sometimes|required|numeric|min:0',
            'tarifas' => 'sometimes|required|array',
            'estado_vuelo' => 'sometimes|in:programado,en_hora,retrasado,cancelado,abordando,en_vuelo,aterrizado',
            'es_directo' => 'sometimes|boolean',
            'activo' => 'sometimes|boolean'
        ]);

        try {
            $datosActualizar = $request->only([
                'codigo_vuelo', 'avion_id', 'origen_id', 'destino_id', 
                'fecha_salida', 'fecha_llegada', 'precio_base', 'tarifas',
                'estado_vuelo', 'es_directo', 'activo'
            ]);

            // Recalcular duración si cambian las fechas
            if ($request->has('fecha_salida') || $request->has('fecha_llegada')) {
                $salida = Carbon::parse($request->get('fecha_salida', $vuelo->fecha_salida));
                $llegada = Carbon::parse($request->get('fecha_llegada', $vuelo->fecha_llegada));
                $datosActualizar['duracion_minutos'] = $llegada->diffInMinutes($salida);
            }

            $vuelo->update($datosActualizar);

            return response()->json([
                'success' => true,
                'data' => $vuelo->fresh()->load(['avion.aerolinea', 'origen', 'destino']),
                'message' => 'Vuelo actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar vuelo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (desactivar) un vuelo
     */
    public function destroy($id): JsonResponse
    {
        $vuelo = Vuelo::find($id);

        if (!$vuelo) {
            return response()->json([
                'success' => false,
                'message' => 'Vuelo no encontrado'
            ], 404);
        }

        try {
            // Verificar si tiene reservas activas
            $tieneReservas = $vuelo->reservas()->whereIn('estado', ['pendiente', 'confirmada', 'pagada'])->exists();

            if ($tieneReservas) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un vuelo con reservas activas'
                ], 400);
            }

            $vuelo->update(['activo' => false, 'estado_vuelo' => 'cancelado']);

            return response()->json([
                'success' => true,
                'message' => 'Vuelo desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar vuelo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar vuelos por ruta y fecha
     */
    public function buscar(Request $request): JsonResponse
    {
        // Validación flexible - solo los campos básicos
        $request->validate([
            'origen_codigo' => 'nullable|string|size:3',
            'destino_codigo' => 'nullable|string|size:3',
            'origen' => 'nullable|string',
            'destino' => 'nullable|string',
            'fecha_salida' => 'nullable|date',
            'fecha_regreso' => 'nullable|date',
        ]);

        \Log::info('🔍 BÚSQUEDA FLEXIBLE DE VUELOS', [
            'parametros_recibidos' => $request->only([
                'origen_codigo', 'destino_codigo', 'origen', 'destino', 
                'fecha_salida', 'fecha_regreso'
            ])
        ]);

        // Buscar aeropuertos por código IATA o ID
        $origen = null;
        $destino = null;
        
        if ($request->origen_codigo) {
            $origen = Aeropuerto::where('codigo_iata', $request->origen_codigo)->first();
            \Log::info('✈️ Origen por código IATA: ' . $request->origen_codigo, [
                'encontrado' => $origen ? 'SÍ' : 'NO',
                'aeropuerto' => $origen ? $origen->nombre : null
            ]);
        } elseif ($request->origen) {
            $origen = Aeropuerto::find($request->origen);
            \Log::info('✈️ Origen por ID: ' . $request->origen, [
                'encontrado' => $origen ? 'SÍ' : 'NO',
                'aeropuerto' => $origen ? $origen->nombre : null
            ]);
        }
        
        if ($request->destino_codigo) {
            $destino = Aeropuerto::where('codigo_iata', $request->destino_codigo)->first();
            \Log::info('🛬 Destino por código IATA: ' . $request->destino_codigo, [
                'encontrado' => $destino ? 'SÍ' : 'NO',
                'aeropuerto' => $destino ? $destino->nombre : null
            ]);
        } elseif ($request->destino) {
            $destino = Aeropuerto::find($request->destino);
            \Log::info('🛬 Destino por ID: ' . $request->destino, [
                'encontrado' => $destino ? 'SÍ' : 'NO',
                'aeropuerto' => $destino ? $destino->nombre : null
            ]);
        }

        // Construir query base
        $vuelosQuery = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
            ->activos();

        // Aplicar filtros según lo que esté disponible
        if ($origen && $destino) {
            // Caso: Origen Y Destino especificados
            \Log::info('🎯 Filtro: ORIGEN + DESTINO', [
                'origen' => $origen->nombre . ' (' . $origen->codigo_iata . ')',
                'destino' => $destino->nombre . ' (' . $destino->codigo_iata . ')'
            ]);
            $vuelosQuery->where('origen_id', $origen->_id)
                       ->where('destino_id', $destino->_id);
            
            // Si no hay fecha específica, mostrar vuelos desde hoy en adelante
            if (!$request->fecha_salida) {
                $vuelosQuery->where('fecha_salida', '>=', now()->startOfDay());
                \Log::info('📅 Sin fecha específica - mostrando desde hoy en adelante');
            }
        } elseif ($origen) {
            // Caso: Solo Origen especificado
            \Log::info('🎯 Filtro: Solo ORIGEN', [
                'origen' => $origen->nombre . ' (' . $origen->codigo_iata . ')'
            ]);
            $vuelosQuery->where('origen_id', $origen->_id);
        } elseif ($destino) {
            // Caso: Solo Destino especificado
            \Log::info('🎯 Filtro: Solo DESTINO', [
                'destino' => $destino->nombre . ' (' . $destino->codigo_iata . ')'
            ]);
            $vuelosQuery->where('destino_id', $destino->_id);
        }
        
        // Aplicar filtro de fecha si está especificada
        if ($request->fecha_salida) {
            $fechaSalida = Carbon::parse($request->fecha_salida)->format('Y-m-d');
            \Log::info('📅 Filtro por fecha de salida: ' . $fechaSalida);
            $vuelosQuery->whereDate('fecha_salida', $fechaSalida);
        }
        
            // Si solo se especifica fecha_regreso, filtrar por ese campo
            if (!$origen && !$destino && !$request->fecha_salida && $request->fecha_regreso) {
                $fechaRegreso = Carbon::parse($request->fecha_regreso)->format('Y-m-d');
                \Log::info('📅 Filtro SOLO por fecha de regreso: ' . $fechaRegreso);
                $vuelosQuery->whereDate('fecha_salida', $fechaRegreso);
            }

        // Ejecutar consulta
        $vuelos = $vuelosQuery->get();
        
        \Log::info('✅ Resultados de búsqueda', [
            'vuelos_encontrados' => $vuelos->count(),
            'query_aplicada' => [
                'tiene_origen' => !is_null($origen),
                'tiene_destino' => !is_null($destino),
                'tiene_fecha' => !is_null($request->fecha_salida)
            ]
        ]);

        // Preparar respuesta
        $resultado = [
            'vuelos_ida' => $vuelos,
            'vuelos_regreso' => []
        ];

        // Si hay fecha de regreso y ambos aeropuertos, buscar vuelos de regreso
        if ($request->fecha_regreso && $origen && $destino) {
            $fechaRegreso = Carbon::parse($request->fecha_regreso)->format('Y-m-d');
            $vuelosRegreso = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                ->where('origen_id', $destino->_id)
                ->where('destino_id', $origen->_id)
                ->whereDate('fecha_salida', $fechaRegreso)
                ->activos()
                ->get();
            
            $resultado['vuelos_regreso'] = $vuelosRegreso;
            
            \Log::info('🔄 Vuelos de regreso encontrados: ' . $vuelosRegreso->count());
        }

        return response()->json([
            'success' => true,
            'data' => $resultado,
            'message' => 'Búsqueda realizada exitosamente'
        ]);
    }

    /**
     * Obtener horarios de vuelos entre dos ciudades
     */
    public function horarios(Request $request): JsonResponse
    {
        $request->validate([
            'origen_codigo' => 'required|string|size:3',
            'destino_codigo' => 'required|string|size:3',
        ]);

        $origen = Aeropuerto::where('codigo_iata', $request->origen_codigo)->first();
        $destino = Aeropuerto::where('codigo_iata', $request->destino_codigo)->first();

        if (!$origen || !$destino) {
            return response()->json([
                'success' => false,
                'message' => 'Aeropuerto no encontrado'
            ], 404);
        }

        $horarios = Vuelo::with(['avion.aerolinea'])
            ->porRuta($origen->_id, $destino->_id)
            ->where('fecha_salida', '>=', Carbon::today())
            ->activos()
            ->orderBy('fecha_salida')
            ->get()
            ->groupBy(function($vuelo) {
                return $vuelo->fecha_salida->format('Y-m-d');
            });

        return response()->json([
            'success' => true,
            'data' => $horarios,
            'message' => 'Horarios obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener tarifas de vuelos ordenadas por precio
     */
    public function tarifas(Request $request): JsonResponse
    {
        $request->validate([
            'origen_codigo' => 'required|string|size:3',
            'destino_codigo' => 'required|string|size:3',
            'fecha_salida' => 'nullable|date|after_or_equal:today'
        ]);

        $origen = Aeropuerto::where('codigo_iata', $request->origen_codigo)->first();
        $destino = Aeropuerto::where('codigo_iata', $request->destino_codigo)->first();

        if (!$origen || !$destino) {
            return response()->json([
                'success' => false,
                'message' => 'Aeropuerto no encontrado'
            ], 404);
        }

        $query = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
            ->porRuta($origen->_id, $destino->_id)
            ->activos()
            ->orderBy('precio_base');

        if ($request->fecha_salida) {
            $query->porFecha($request->fecha_salida);
        } else {
            $query->where('fecha_salida', '>=', Carbon::today());
        }

        $tarifas = $query->get();

        return response()->json([
            'success' => true,
            'data' => $tarifas,
            'message' => 'Tarifas obtenidas exitosamente'
        ]);
    }

    /**
     * Obtener aeropuertos disponibles
     */
    public function aeropuertos(): JsonResponse
    {
        $aeropuertos = Aeropuerto::where('activo', true)
            ->orderBy('ciudad')
            ->get(['codigo_iata', 'codigo_icao', 'nombre', 'ciudad', 'pais']);

        return response()->json([
            'success' => true,
            'data' => $aeropuertos,
            'message' => 'Aeropuertos obtenidos exitosamente'
        ]);
    }
}