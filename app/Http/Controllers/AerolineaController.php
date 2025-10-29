<?php

namespace App\Http\Controllers;

use App\Models\Aerolinea;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AerolineaController extends Controller
{
    /**
     * Listar todas las aerolíneas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Aerolinea::query();

        // Filtros
        if ($request->has('activa')) {
            $query->where('activa', $request->boolean('activa'));
        }

        if ($request->has('pais')) {
            $query->where('pais', 'like', '%' . $request->pais . '%');
        }

        if ($request->has('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        $aerolineas = $query->orderBy('nombre')
                           ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $aerolineas,
            'message' => 'Aerolíneas obtenidas exitosamente'
        ]);
    }

    /**
     * Crear una nueva aerolínea
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_iata' => 'required|string|size:2|unique:aerolineas',
            'codigo_icao' => 'required|string|size:3|unique:aerolineas',
            'nombre' => 'required|string|max:255',
            'pais' => 'required|string|max:100',
            'contacto' => 'required|string|max:255',
            'logo_url' => 'nullable|url'
        ]);

        try {
            $aerolinea = Aerolinea::create([
                'codigo_iata' => strtoupper($request->codigo_iata),
                'codigo_icao' => strtoupper($request->codigo_icao),
                'nombre' => $request->nombre,
                'pais' => $request->pais,
                'contacto' => $request->contacto,
                'logo_url' => $request->logo_url,
                'activa' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $aerolinea,
                'message' => 'Aerolínea creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear aerolínea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una aerolínea específica
     */
    public function show($id): JsonResponse
    {
        $aerolinea = Aerolinea::with(['aviones', 'vuelos'])
                             ->find($id);

        if (!$aerolinea) {
            return response()->json([
                'success' => false,
                'message' => 'Aerolínea no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $aerolinea,
            'message' => 'Aerolínea encontrada'
        ]);
    }

    /**
     * Actualizar una aerolínea
     */
    public function update(Request $request, $id): JsonResponse
    {
        $aerolinea = Aerolinea::find($id);

        if (!$aerolinea) {
            return response()->json([
                'success' => false,
                'message' => 'Aerolínea no encontrada'
            ], 404);
        }

        $request->validate([
            'codigo_iata' => 'sometimes|required|string|size:2|unique:aerolineas,codigo_iata,' . $id,
            'codigo_icao' => 'sometimes|required|string|size:3|unique:aerolineas,codigo_icao,' . $id,
            'nombre' => 'sometimes|required|string|max:255',
            'pais' => 'sometimes|required|string|max:100',
            'contacto' => 'sometimes|required|string|max:255',
            'logo_url' => 'nullable|url',
            'activa' => 'sometimes|boolean'
        ]);

        try {
            $datosActualizar = $request->only([
                'codigo_iata', 'codigo_icao', 'nombre', 'pais', 'contacto', 'logo_url', 'activa'
            ]);

            // Convertir códigos a mayúsculas
            if (isset($datosActualizar['codigo_iata'])) {
                $datosActualizar['codigo_iata'] = strtoupper($datosActualizar['codigo_iata']);
            }
            if (isset($datosActualizar['codigo_icao'])) {
                $datosActualizar['codigo_icao'] = strtoupper($datosActualizar['codigo_icao']);
            }

            $aerolinea->update($datosActualizar);

            return response()->json([
                'success' => true,
                'data' => $aerolinea->fresh(),
                'message' => 'Aerolínea actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar aerolínea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (desactivar) una aerolínea
     */
    public function destroy($id): JsonResponse
    {
        $aerolinea = Aerolinea::find($id);

        if (!$aerolinea) {
            return response()->json([
                'success' => false,
                'message' => 'Aerolínea no encontrada'
            ], 404);
        }

        try {
            // Verificar si tiene vuelos activos
            $tieneVuelosActivos = $aerolinea->vuelos()
                ->where('activo', true)
                ->where('fecha_salida', '>', now())
                ->exists();

            if ($tieneVuelosActivos) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar una aerolínea con vuelos activos programados'
                ], 400);
            }

            $aerolinea->update(['activa' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Aerolínea desactivada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar aerolínea: ' . $e->getMessage()
            ], 500);
        }
    }
}