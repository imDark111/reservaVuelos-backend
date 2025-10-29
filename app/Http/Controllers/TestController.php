<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aeropuerto;
use App\Models\Aerolinea;
use App\Models\Avion;
use App\Models\Vuelo;
use App\Models\User;

class TestController extends Controller
{
    /**
     * Endpoint de prueba del API
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => '🎉 Backend de Reserva de Vuelos funcionando correctamente!',
            'version' => '1.0.0',
            'timestamp' => now(),
            'database_stats' => [
                'aeropuertos' => Aeropuerto::count(),
                'aerolineas' => Aerolinea::count(), 
                'aviones' => Avion::count(),
                'vuelos' => Vuelo::count(),
                'usuarios' => User::count()
            ]
        ]);
    }

    /**
     * Estado de la base de datos
     */
    public function dbStatus()
    {
        try {
            // Verificar conexión con algunos datos de muestra
            $ultimoVuelo = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                                ->latest('created_at')
                                ->first();

            $proximosVuelos = Vuelo::with(['avion.aerolinea', 'origen', 'destino'])
                                   ->where('fecha_salida', '>=', now())
                                   ->orderBy('fecha_salida', 'asc')
                                   ->limit(5)
                                   ->get();

            return response()->json([
                'success' => true,
                'message' => 'Base de datos conectada y funcionando',
                'data' => [
                    'ultimo_vuelo_creado' => $ultimoVuelo,
                    'proximos_vuelos' => $proximosVuelos,
                    'estadisticas' => [
                        'total_aeropuertos' => Aeropuerto::count(),
                        'aeropuertos_activos' => Aeropuerto::where('activo', true)->count(),
                        'total_aerolineas' => Aerolinea::count(),
                        'aerolineas_activas' => Aerolinea::where('activa', true)->count(),
                        'total_aviones' => Avion::count(),
                        'aviones_activos' => Avion::where('activo', true)->count(),
                        'total_vuelos' => Vuelo::count(),
                        'vuelos_programados' => Vuelo::where('estado_vuelo', 'programado')->count(),
                        'vuelos_activos' => Vuelo::where('activo', true)->count(),
                        'total_usuarios' => User::count(),
                        'usuarios_activos' => User::where('activo', true)->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Información del sistema
     */
    public function info()
    {
        return response()->json([
            'success' => true,
            'sistema' => 'Backend Reserva de Vuelos',
            'version' => '1.0.0',
            'framework' => 'Laravel ' . app()->version(),
            'php_version' => PHP_VERSION,
            'timezone' => config('app.timezone'),
            'database' => 'MongoDB',
            'endpoints' => [
                'autenticacion' => [
                    'POST /api/auth/registro' => 'Registrar nuevo usuario',
                    'POST /api/auth/login' => 'Iniciar sesión',
                    'POST /api/auth/logout' => 'Cerrar sesión',
                    'GET /api/auth/perfil' => 'Obtener perfil usuario',
                    'PUT /api/auth/perfil' => 'Actualizar perfil',
                    'POST /api/auth/cambiar-password' => 'Cambiar contraseña'
                ],
                'vuelos' => [
                    'GET /api/vuelos' => 'Listar vuelos',
                    'GET /api/vuelos/buscar' => 'Buscar vuelos',
                    'GET /api/vuelos/{id}' => 'Detalle del vuelo'
                ],
                'aeropuertos' => [
                    'GET /api/aeropuertos' => 'Listar aeropuertos',
                    'GET /api/aeropuertos/{id}' => 'Detalle del aeropuerto'
                ],
                'aerolineas' => [
                    'GET /api/aerolineas' => 'Listar aerolíneas',
                    'GET /api/aerolineas/{id}' => 'Detalle de aerolínea'
                ]
            ]
        ]);
    }
}