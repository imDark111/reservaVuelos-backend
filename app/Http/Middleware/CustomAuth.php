<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CustomAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $path = $request->path();
        
        \Log::info('CustomAuth middleware ejecutándose', [
            'path' => $path,
            'method' => $request->method(),
            'auth_header_present' => !!$authHeader
        ]);
        
        // Permitir rutas de prueba sin autenticación
        if (str_contains($path, 'billetes/test/') || str_contains($path, 'reservas/test/')) {
            \Log::info('Ruta de prueba detectada, omitiendo autenticación', ['path' => $path]);
            return $next($request);
        }
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            \Log::warning('Token no proporcionado o inválido', [
                'path' => $path,
                'auth_header' => $authHeader ? 'presente' : 'ausente'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado'
            ], 401);
        }

        $token = substr($authHeader, 7); // Remove "Bearer "
        
        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            
            if (count($parts) < 3) {
                throw new \Exception('Token inválido');
            }
            
            $email = $parts[0];
            $userId = $parts[2];
            
            $user = User::where('email', $email)->where('_id', $userId)->first();
            
            \Log::info('Middleware CustomAuth - Usuario encontrado', [
                'email' => $email,
                'user_id' => $userId,
                'user_found' => $user ? 'SI' : 'NO',
                'user_active' => $user?->activo,
                'user_db_id' => $user?->_id
            ]);
            
            if (!$user || !$user->activo) {
                \Log::warning('Middleware CustomAuth - Usuario inválido', [
                    'email' => $email,
                    'user_id' => $userId,
                    'user_exists' => !!$user,
                    'user_active' => $user?->activo
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o usuario inactivo'
                ], 401);
            }
            
            // Agregar usuario a la request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }

        return $next($request);
    }
}