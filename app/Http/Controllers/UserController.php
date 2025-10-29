<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios (admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->has('nombres')) {
            $query->where('nombres', 'like', '%' . $request->nombres . '%');
        }

        if ($request->has('fecha_desde')) {
            $query->where('fecha_registro', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->where('fecha_registro', '<=', $request->fecha_hasta);
        }

        $usuarios = $query->orderBy('fecha_registro', 'desc')
                         ->paginate($request->get('per_page', 20));

        // Ocultar información sensible
        $usuarios->getCollection()->transform(function ($usuario) {
            return $usuario->makeHidden(['password', 'tarjeta_credito', 'remember_token']);
        });

        return response()->json([
            'success' => true,
            'data' => $usuarios,
            'message' => 'Usuarios obtenidos exitosamente'
        ]);
    }

    /**
     * Crear un nuevo usuario (admin)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'tarjeta_credito' => 'nullable|string|size:16|regex:/^[0-9]+$/',
            'activo' => 'boolean'
        ]);

        try {
            $usuario = User::create([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tarjeta_credito' => $request->tarjeta_credito ? encrypt($request->tarjeta_credito) : null,
                'fecha_registro' => now(),
                'activo' => $request->get('activo', true)
            ]);

            return response()->json([
                'success' => true,
                'data' => $usuario->makeHidden(['password', 'tarjeta_credito', 'remember_token']),
                'message' => 'Usuario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario específico (admin)
     */
    public function show($id): JsonResponse
    {
        $usuario = User::with(['reservas', 'billetes'])
                      ->find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $usuario->makeHidden(['password', 'tarjeta_credito', 'remember_token']),
            'message' => 'Usuario encontrado'
        ]);
    }

    /**
     * Actualizar un usuario (admin)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $request->validate([
            'nombres' => 'sometimes|required|string|max:100',
            'apellidos' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
            'tarjeta_credito' => 'nullable|string|size:16|regex:/^[0-9]+$/',
            'activo' => 'sometimes|boolean'
        ]);

        try {
            $datosActualizar = $request->only(['nombres', 'apellidos', 'email', 'activo']);

            if ($request->has('password')) {
                $datosActualizar['password'] = Hash::make($request->password);
            }

            if ($request->has('tarjeta_credito')) {
                $datosActualizar['tarjeta_credito'] = $request->tarjeta_credito ? 
                    encrypt($request->tarjeta_credito) : null;
            }

            $usuario->update($datosActualizar);

            return response()->json([
                'success' => true,
                'data' => $usuario->fresh()->makeHidden(['password', 'tarjeta_credito', 'remember_token']),
                'message' => 'Usuario actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar un usuario (admin)
     */
    public function destroy($id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        try {
            // Verificar si tiene reservas activas
            $tieneReservasActivas = $usuario->reservas()
                ->whereIn('estado', ['pendiente', 'confirmada', 'pagada'])
                ->exists();

            if ($tieneReservasActivas) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar un usuario con reservas activas'
                ], 400);
            }

            $usuario->update(['activo' => false]);

            // Revocar todos los tokens del usuario
            $usuario->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar un usuario desactivado (admin)
     */
    public function activar($id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        try {
            $usuario->update(['activo' => true]);

            return response()->json([
                'success' => true,
                'data' => $usuario->makeHidden(['password', 'tarjeta_credito', 'remember_token']),
                'message' => 'Usuario activado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de usuarios (admin)
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total_usuarios' => User::count(),
                'usuarios_activos' => User::where('activo', true)->count(),
                'usuarios_inactivos' => User::where('activo', false)->count(),
                'registros_ultimo_mes' => User::where('fecha_registro', '>=', now()->subMonth())->count(),
                'registros_ultima_semana' => User::where('fecha_registro', '>=', now()->subWeek())->count(),
                'usuarios_con_reservas' => User::has('reservas')->count(),
                'usuarios_con_billetes' => User::has('billetes')->count(),
            ];

            // Estadísticas por mes (últimos 6 meses)
            $registrosPorMes = [];
            for ($i = 5; $i >= 0; $i--) {
                $fecha = now()->subMonths($i);
                $registrosPorMes[] = [
                    'mes' => $fecha->format('Y-m'),
                    'registros' => User::whereYear('fecha_registro', $fecha->year)
                                     ->whereMonth('fecha_registro', $fecha->month)
                                     ->count()
                ];
            }

            $estadisticas['registros_por_mes'] = $registrosPorMes;

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de usuarios obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar usuarios (admin)
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate([
            'termino' => 'required|string|min:2'
        ]);

        $termino = $request->termino;

        $usuarios = User::where(function($query) use ($termino) {
            $query->where('nombres', 'like', '%' . $termino . '%')
                  ->orWhere('apellidos', 'like', '%' . $termino . '%')
                  ->orWhere('email', 'like', '%' . $termino . '%');
        })
        ->where('activo', true)
        ->orderBy('nombres')
        ->limit(10)
        ->get(['_id', 'nombres', 'apellidos', 'email', 'fecha_registro']);

        return response()->json([
            'success' => true,
            'data' => $usuarios,
            'message' => 'Búsqueda realizada exitosamente'
        ]);
    }

    /**
     * Resetear contraseña de usuario (admin)
     */
    public function resetearPassword(Request $request, $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $request->validate([
            'nueva_password' => 'required|string|min:8'
        ]);

        try {
            $usuario->update([
                'password' => Hash::make($request->nueva_password)
            ]);

            // Revocar todos los tokens del usuario para forzar re-login
            $usuario->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña reseteada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al resetear contraseña: ' . $e->getMessage()
            ], 500);
        }
    }
}