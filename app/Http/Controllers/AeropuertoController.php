<?php

namespace App\Http\Controllers;

use App\Models\Aeropuerto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AeropuertoController extends Controller
{
    /**
     * Listar todos los aeropuertos
     */
    public function index(Request $request): JsonResponse
    {
        $query = Aeropuerto::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('pais')) {
            $query->where('pais', 'like', '%' . $request->pais . '%');
        }

        if ($request->has('ciudad')) {
            $query->where('ciudad', 'like', '%' . $request->ciudad . '%');
        }

        if ($request->has('codigo')) {
            $query->where(function($q) use ($request) {
                $q->where('codigo_iata', 'like', '%' . $request->codigo . '%')
                  ->orWhere('codigo_icao', 'like', '%' . $request->codigo . '%');
            });
        }

        $aeropuertos = $query->orderBy('ciudad')
                            ->orderBy('nombre')
                            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $aeropuertos,
            'message' => 'Aeropuertos obtenidos exitosamente'
        ]);
    }

    /**
     * Crear un nuevo aeropuerto
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_iata' => 'required|string|size:3|unique:aeropuertos',
            'codigo_icao' => 'required|string|size:4|unique:aeropuertos',
            'nombre' => 'required|string|max:255',
            'ciudad' => 'required|string|max:100',
            'pais' => 'required|string|max:100',
            'zona_horaria' => 'required|string|max:50',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180'
        ]);

        try {
            $aeropuerto = Aeropuerto::create([
                'codigo_iata' => strtoupper($request->codigo_iata),
                'codigo_icao' => strtoupper($request->codigo_icao),
                'nombre' => $request->nombre,
                'ciudad' => $request->ciudad,
                'pais' => $request->pais,
                'zona_horaria' => $request->zona_horaria,
                'latitud' => $request->latitud,
                'longitud' => $request->longitud,
                'activo' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $aeropuerto,
                'message' => 'Aeropuerto creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear aeropuerto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un aeropuerto específico
     */
    public function show($id): JsonResponse
    {
        $aeropuerto = Aeropuerto::with(['vuelosOrigen', 'vuelosDestino'])
                                ->find($id);

        if (!$aeropuerto) {
            return response()->json([
                'success' => false,
                'message' => 'Aeropuerto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $aeropuerto,
            'message' => 'Aeropuerto encontrado'
        ]);
    }

    /**
     * Actualizar un aeropuerto
     */
    public function update(Request $request, $id): JsonResponse
    {
        $aeropuerto = Aeropuerto::find($id);

        if (!$aeropuerto) {
            return response()->json([
                'success' => false,
                'message' => 'Aeropuerto no encontrado'
            ], 404);
        }

        $request->validate([
            'codigo_iata' => 'sometimes|required|string|size:3|unique:aeropuertos,codigo_iata,' . $id,
            'codigo_icao' => 'sometimes|required|string|size:4|unique:aeropuertos,codigo_icao,' . $id,
            'nombre' => 'sometimes|required|string|max:255',
            'ciudad' => 'sometimes|required|string|max:100',
            'pais' => 'sometimes|required|string|max:100',
            'zona_horaria' => 'sometimes|required|string|max:50',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'activo' => 'sometimes|boolean'
        ]);

        try {
            $datosActualizar = $request->only([
                'codigo_iata', 'codigo_icao', 'nombre', 'ciudad', 'pais', 
                'zona_horaria', 'latitud', 'longitud', 'activo'
            ]);

            // Convertir códigos a mayúsculas
            if (isset($datosActualizar['codigo_iata'])) {
                $datosActualizar['codigo_iata'] = strtoupper($datosActualizar['codigo_iata']);
            }
            if (isset($datosActualizar['codigo_icao'])) {
                $datosActualizar['codigo_icao'] = strtoupper($datosActualizar['codigo_icao']);
            }

            $aeropuerto->update($datosActualizar);

            return response()->json([
                'success' => true,
                'data' => $aeropuerto->fresh(),
                'message' => 'Aeropuerto actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar aeropuerto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (desactivar) un aeropuerto
     */
    public function destroy($id): JsonResponse
    {
        $aeropuerto = Aeropuerto::find($id);

        if (!$aeropuerto) {
            return response()->json([
                'success' => false,
                'message' => 'Aeropuerto no encontrado'
            ], 404);
        }

        try {
            // Verificar si tiene vuelos activos
            $tieneVuelosActivos = $aeropuerto->vuelosOrigen()
                ->where('activo', true)
                ->where('fecha_salida', '>', now())
                ->exists() ||
                $aeropuerto->vuelosDestino()
                ->where('activo', true)
                ->where('fecha_salida', '>', now())
                ->exists();

            if ($tieneVuelosActivos) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar un aeropuerto con vuelos activos programados'
                ], 400);
            }

            $aeropuerto->update(['activo' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Aeropuerto desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar aeropuerto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener aeropuertos activos (para uso público)
     */
    public function activos(): JsonResponse
    {
        $aeropuertos = Aeropuerto::where('activo', true)
            ->orderBy('ciudad')
            ->get(['_id', 'codigo_iata', 'codigo_icao', 'nombre', 'ciudad', 'pais']);

        return response()->json([
            'success' => true,
            'data' => $aeropuertos,
            'message' => 'Aeropuertos activos obtenidos exitosamente'
        ]);
    }

    /**
     * Buscar aeropuertos por término de búsqueda
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate([
            'termino' => 'required|string|min:2'
        ]);

        $termino = $request->termino;

        $aeropuertos = Aeropuerto::where('activo', true)
            ->where(function($query) use ($termino) {
                $query->where('nombre', 'like', '%' . $termino . '%')
                      ->orWhere('ciudad', 'like', '%' . $termino . '%')
                      ->orWhere('codigo_iata', 'like', '%' . $termino . '%')
                      ->orWhere('codigo_icao', 'like', '%' . $termino . '%');
            })
            ->orderBy('ciudad')
            ->limit(10)
            ->get(['_id', 'codigo_iata', 'codigo_icao', 'nombre', 'ciudad', 'pais']);

        return response()->json([
            'success' => true,
            'data' => $aeropuertos,
            'message' => 'Búsqueda realizada exitosamente'
        ]);
    }
}