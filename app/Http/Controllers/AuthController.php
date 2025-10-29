<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registro de nuevo usuario
     */
    public function registro(Request $request): JsonResponse
    {
        $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'tarjeta_credito' => 'nullable|string|size:16|regex:/^[0-9]+$/'
        ]);

        try {
            $user = User::create([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tarjeta_credito' => $request->tarjeta_credito ? encrypt($request->tarjeta_credito) : null,
                'fecha_registro' => now(),
                'activo' => true
            ]);

            // Generar token simple para autenticación
            $token = base64_encode($user->email . ':' . now()->timestamp . ':' . $user->_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->makeHidden(['tarjeta_credito', 'password']),
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'Usuario registrado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar sesión
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        if (!$user->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta desactivada. Contacte al administrador'
            ], 403);
        }

        // Generar token simple para autenticación
        $token = base64_encode($user->email . ':' . now()->timestamp . ':' . $user->_id);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->makeHidden(['tarjeta_credito', 'password']),
                'token' => $token,
                'token_type' => 'Bearer'
            ],
            'message' => 'Inicio de sesión exitoso'
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente. Token invalidado del lado del cliente.'
        ]);
    }

    /**
     * Obtener información del usuario autenticado
     */
    public function perfil(Request $request): JsonResponse
    {
        $user = $request->user()->makeHidden(['tarjeta_credito']);
        
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Perfil obtenido exitosamente'
        ]);
    }

    /**
     * Actualizar perfil del usuario
     */
    public function actualizarPerfil(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'nombres' => 'sometimes|required|string|max:100',
            'apellidos' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->_id,
            'password' => 'sometimes|required|string|min:8|confirmed',
            'tarjeta_credito' => 'nullable|string|size:16|regex:/^[0-9]+$/'
        ]);

        try {
            $datosActualizar = $request->only(['nombres', 'apellidos', 'email']);

            if ($request->has('password')) {
                $datosActualizar['password'] = Hash::make($request->password);
            }

            if ($request->has('tarjeta_credito')) {
                $datosActualizar['tarjeta_credito'] = $request->tarjeta_credito ? 
                    encrypt($request->tarjeta_credito) : null;
            }

            $user->update($datosActualizar);

            return response()->json([
                'success' => true,
                'data' => $user->fresh()->makeHidden(['tarjeta_credito']),
                'message' => 'Perfil actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar token
     */
    public function validarToken(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'user' => $request->user()->makeHidden(['tarjeta_credito'])
            ],
            'message' => 'Token válido'
        ]);
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password_actual' => 'required|string',
            'password_nuevo' => 'required|string|min:8|confirmed|different:password_actual'
        ]);

        $user = $request->user();

        if (!Hash::check($request->password_actual, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password_nuevo)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña cambiada exitosamente'
        ]);
    }
}