<?php

namespace App\Http\Controllers;

use App\Models\Avion;
use App\Models\Aerolinea;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AvionController extends Controller
{
    /**
     * Listar todos los aviones
     */
    public function index(Request $request): JsonResponse
    {
        $query = Avion::with('aerolinea');

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('aerolinea_id')) {
            $query->where('aerolinea_id', $request->aerolinea_id);
        }

        if ($request->has('modelo')) {
            $query->where('modelo', 'like', '%' . $request->modelo . '%');
        }

        if ($request->has('codigo_avion')) {
            $query->where('codigo_avion', 'like', '%' . $request->codigo_avion . '%');
        }

        $aviones = $query->orderBy('codigo_avion')
                        ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $aviones,
            'message' => 'Aviones obtenidos exitosamente'
        ]);
    }

    /**
     * Crear un nuevo avión
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_avion' => 'required|string|unique:aviones|max:20',
            'aerolinea_id' => 'required|exists:aerolineas,_id',
            'modelo' => 'required|string|max:100',
            'num_asientos_total' => 'required|integer|min:1|max:1000',
            'configuracion_asientos' => 'required|array',
            'configuracion_asientos.economica' => 'required|array',
            'configuracion_asientos.economica.total' => 'required|integer|min:0',
            'configuracion_asientos.premium' => 'nullable|array',
            'configuracion_asientos.premium.total' => 'nullable|integer|min:0',
            'configuracion_asientos.ejecutiva' => 'nullable|array',
            'configuracion_asientos.ejecutiva.total' => 'nullable|integer|min:0'
        ]);

        try {
            // Verificar que la aerolínea esté activa
            $aerolinea = Aerolinea::find($request->aerolinea_id);
            if (!$aerolinea || !$aerolinea->activa) {
                return response()->json([
                    'success' => false,
                    'message' => 'La aerolínea especificada no está activa'
                ], 400);
            }

            // Validar que el total de asientos coincida
            $totalConfigurado = 0;
            foreach ($request->configuracion_asientos as $clase) {
                if (isset($clase['total'])) {
                    $totalConfigurado += $clase['total'];
                }
            }

            if ($totalConfigurado !== $request->num_asientos_total) {
                return response()->json([
                    'success' => false,
                    'message' => 'El total de asientos configurados no coincide con el número total'
                ], 400);
            }

            $avion = Avion::create([
                'codigo_avion' => strtoupper($request->codigo_avion),
                'aerolinea_id' => $request->aerolinea_id,
                'modelo' => $request->modelo,
                'num_asientos_total' => $request->num_asientos_total,
                'configuracion_asientos' => $request->configuracion_asientos,
                'activo' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $avion->load('aerolinea'),
                'message' => 'Avión creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear avión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un avión específico
     */
    public function show($id): JsonResponse
    {
        $avion = Avion::with(['aerolinea', 'vuelos'])
                     ->find($id);

        if (!$avion) {
            return response()->json([
                'success' => false,
                'message' => 'Avión no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $avion,
            'message' => 'Avión encontrado'
        ]);
    }

    /**
     * Actualizar un avión
     */
    public function update(Request $request, $id): JsonResponse
    {
        $avion = Avion::find($id);

        if (!$avion) {
            return response()->json([
                'success' => false,
                'message' => 'Avión no encontrado'
            ], 404);
        }

        $request->validate([
            'codigo_avion' => 'sometimes|required|string|unique:aviones,codigo_avion,' . $id . '|max:20',
            'aerolinea_id' => 'sometimes|required|exists:aerolineas,_id',
            'modelo' => 'sometimes|required|string|max:100',
            'num_asientos_total' => 'sometimes|required|integer|min:1|max:1000',
            'configuracion_asientos' => 'sometimes|required|array',
            'activo' => 'sometimes|boolean'
        ]);

        try {
            $datosActualizar = $request->only([
                'codigo_avion', 'aerolinea_id', 'modelo', 'num_asientos_total', 
                'configuracion_asientos', 'activo'
            ]);

            // Convertir código a mayúsculas
            if (isset($datosActualizar['codigo_avion'])) {
                $datosActualizar['codigo_avion'] = strtoupper($datosActualizar['codigo_avion']);
            }

            // Validar aerolínea si se está cambiando
            if (isset($datosActualizar['aerolinea_id'])) {
                $aerolinea = Aerolinea::find($datosActualizar['aerolinea_id']);
                if (!$aerolinea || !$aerolinea->activa) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La aerolínea especificada no está activa'
                    ], 400);
                }
            }

            // Validar configuración de asientos si se está actualizando
            if (isset($datosActualizar['configuracion_asientos']) && isset($datosActualizar['num_asientos_total'])) {
                $totalConfigurado = 0;
                foreach ($datosActualizar['configuracion_asientos'] as $clase) {
                    if (isset($clase['total'])) {
                        $totalConfigurado += $clase['total'];
                    }
                }

                if ($totalConfigurado !== $datosActualizar['num_asientos_total']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El total de asientos configurados no coincide con el número total'
                    ], 400);
                }
            }

            $avion->update($datosActualizar);

            return response()->json([
                'success' => true,
                'data' => $avion->fresh()->load('aerolinea'),
                'message' => 'Avión actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar avión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (desactivar) un avión
     */
    public function destroy($id): JsonResponse
    {
        $avion = Avion::find($id);

        if (!$avion) {
            return response()->json([
                'success' => false,
                'message' => 'Avión no encontrado'
            ], 404);
        }

        try {
            // Verificar si tiene vuelos activos programados
            $tieneVuelosActivos = $avion->vuelos()
                ->where('activo', true)
                ->where('fecha_salida', '>', now())
                ->exists();

            if ($tieneVuelosActivos) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar un avión con vuelos activos programados'
                ], 400);
            }

            $avion->update(['activo' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Avión desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar avión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener aviones disponibles por aerolínea
     */
    public function porAerolinea($aerolineaId): JsonResponse
    {
        $aerolinea = Aerolinea::find($aerolineaId);

        if (!$aerolinea) {
            return response()->json([
                'success' => false,
                'message' => 'Aerolínea no encontrada'
            ], 404);
        }

        $aviones = Avion::where('aerolinea_id', $aerolineaId)
                       ->where('activo', true)
                       ->orderBy('codigo_avion')
                       ->get(['_id', 'codigo_avion', 'modelo', 'num_asientos_total', 'configuracion_asientos']);

        return response()->json([
            'success' => true,
            'data' => $aviones,
            'message' => 'Aviones de la aerolínea obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener estadísticas de un avión
     */
    public function estadisticas($id): JsonResponse
    {
        $avion = Avion::with(['vuelos' => function($query) {
            $query->where('fecha_salida', '>=', now()->subDays(30));
        }])->find($id);

        if (!$avion) {
            return response()->json([
                'success' => false,
                'message' => 'Avión no encontrado'
            ], 404);
        }

        $estadisticas = [
            'total_vuelos_ultimo_mes' => $avion->vuelos->count(),
            'vuelos_completados' => $avion->vuelos->where('estado_vuelo', 'aterrizado')->count(),
            'vuelos_cancelados' => $avion->vuelos->where('estado_vuelo', 'cancelado')->count(),
            'horas_vuelo_total' => $avion->vuelos->sum('duracion_minutos') / 60,
            'utilizacion_promedio' => $this->calcularUtilizacionPromedio($avion->vuelos),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'avion' => $avion,
                'estadisticas' => $estadisticas
            ],
            'message' => 'Estadísticas del avión obtenidas exitosamente'
        ]);
    }

    /**
     * Calcular utilización promedio de asientos
     */
    private function calcularUtilizacionPromedio($vuelos)
    {
        if ($vuelos->isEmpty()) {
            return 0;
        }

        $totalUtilizacion = 0;
        $vuelosConDatos = 0;

        foreach ($vuelos as $vuelo) {
            if (isset($vuelo->asientos_disponibles)) {
                $totalAsientos = $vuelo->avion->num_asientos_total;
                $asientosOcupados = $totalAsientos;
                
                foreach ($vuelo->asientos_disponibles as $disponibles) {
                    $asientosOcupados -= $disponibles;
                }
                
                $utilizacion = ($asientosOcupados / $totalAsientos) * 100;
                $totalUtilizacion += $utilizacion;
                $vuelosConDatos++;
            }
        }

        return $vuelosConDatos > 0 ? round($totalUtilizacion / $vuelosConDatos, 2) : 0;
    }
}