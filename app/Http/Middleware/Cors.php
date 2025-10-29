<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Manejar solicitudes OPTIONS (preflight)
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Agregar headers CORS a la respuesta
        return $response
            ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Determinar el origen permitido basado en la solicitud
     */
    private function getAllowedOrigin(Request $request): string
    {
        $origin = $request->header('Origin');
        
        $allowedOrigins = [
            'http://localhost:3000',     // React
            'http://localhost:5173',     // Vite
            'http://localhost:4200',     // Angular  
            'http://localhost:8080',     // Vue
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173', 
            'http://127.0.0.1:4200',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:5500',     // Live Server
            'http://localhost:5500',
            'http://localhost:8081',
            'http://127.0.0.1:8081',
        ];

        // Si el origen está en la lista permitida, devolverlo
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        // Permitir localhost y 127.0.0.1 en cualquier puerto para desarrollo
        if ($origin && (
            str_starts_with($origin, 'http://localhost:') || 
            str_starts_with($origin, 'http://127.0.0.1:')
        )) {
            return $origin;
        }

        // Por defecto permitir todos los orígenes en desarrollo
        return '*';
    }
}