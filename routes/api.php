<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VueloController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\BilleteController;
use App\Http\Controllers\AerolineaController;
use App\Http\Controllers\AeropuertoController;
use App\Http\Controllers\AvionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TestController;


// Rutas públicas de prueba
Route::get('/test', [TestController::class, 'test']);
Route::get('/info', [TestController::class, 'info']);
Route::get('/db-status', [TestController::class, 'dbStatus']);

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/registro', [AuthController::class, 'registro']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas públicas de vuelos (consultas)
Route::prefix('vuelos')->group(function () {
    Route::get('/', [VueloController::class, 'index']);
    Route::get('/buscar', [VueloController::class, 'buscar']);
    Route::get('/horarios', [VueloController::class, 'horarios']);
    Route::get('/tarifas', [VueloController::class, 'tarifas']);
    Route::get('/aeropuertos', [VueloController::class, 'aeropuertos']);
    Route::get('/{id}', [VueloController::class, 'show']);
});

// Rutas públicas de aeropuertos
Route::prefix('aeropuertos')->group(function () {
    Route::get('/activos', [AeropuertoController::class, 'activos']);
    Route::get('/buscar', [AeropuertoController::class, 'buscar']);
});

// Rutas públicas de aerolíneas
Route::prefix('aerolineas')->group(function () {
    Route::get('/', [AerolineaController::class, 'index']);
    Route::get('/{id}', [AerolineaController::class, 'show']);
});

// Rutas protegidas (requieren autenticación)
Route::middleware('custom.auth')->group(function () {
    
    // Rutas de autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/perfil', [AuthController::class, 'perfil']);
        Route::put('/perfil', [AuthController::class, 'actualizarPerfil']);
        Route::post('/validar-token', [AuthController::class, 'validarToken']);
        Route::post('/cambiar-password', [AuthController::class, 'cambiarPassword']);
    });

    // Rutas de reservas
    Route::prefix('reservas')->group(function () {
        Route::get('/', [ReservaController::class, 'index']);
        Route::post('/', [ReservaController::class, 'store']);
        Route::get('/{id}', [ReservaController::class, 'show']);
        Route::put('/{id}', [ReservaController::class, 'update']);
        Route::delete('/{id}', [ReservaController::class, 'destroy']);
        Route::post('/{id}/confirmar', [ReservaController::class, 'confirmar']);
        Route::post('/{id}/pagar', [ReservaController::class, 'pagar']);
        Route::post('/{id}/cancelar', [ReservaController::class, 'cancelar']);
        
        // Rutas admin (requieren permisos adicionales)
        Route::get('/todas/admin', [ReservaController::class, 'todas']);
        Route::post('/test/crear', [ReservaController::class, 'crearReservaTest']);
        
        // Rutas de prueba sin autenticación
        Route::get('/test/todas', [ReservaController::class, 'todas']);
        Route::post('/test/{id}/cancelar', [ReservaController::class, 'cancelarTest']);
    });

    // Rutas de billetes
    Route::prefix('billetes')->group(function () {
        Route::get('/', [BilleteController::class, 'index']);
        Route::post('/{reserva_id}/comprar', [BilleteController::class, 'comprar']);
        Route::get('/{id}', [BilleteController::class, 'show']);
        Route::get('/{id}/pdf', [BilleteController::class, 'descargarPDF']);
        Route::post('/{id}/check-in', [BilleteController::class, 'checkIn']);
        Route::post('/{id}/cancelar', [BilleteController::class, 'cancelar']);
        
        // Ruta de prueba sin autenticación
        Route::post('/test/{reserva_id}/comprar', [BilleteController::class, 'comprarTest']);
        Route::get('/test/{id}/pdf', [BilleteController::class, 'descargarPDFTest']);
        Route::get('/test/reserva/{reserva_id}/pdf', [BilleteController::class, 'descargarPDFPorReservaTest']);
        
        // Rutas admin
        Route::get('/todos/admin', [BilleteController::class, 'todos']);
        
        // Ruta de debug temporal
        Route::get('/debug/reserva/{reservaId}', [BilleteController::class, 'debugReservaBilletes']);
    });

    // Rutas administrativas de vuelos
    Route::prefix('admin/vuelos')->group(function () {
        Route::post('/', [VueloController::class, 'store']);
        Route::put('/{id}', [VueloController::class, 'update']);
        Route::delete('/{id}', [VueloController::class, 'destroy']);
    });

    // Rutas administrativas de aerolíneas
    Route::prefix('admin/aerolineas')->group(function () {
        Route::post('/', [AerolineaController::class, 'store']);
        Route::put('/{id}', [AerolineaController::class, 'update']);
        Route::delete('/{id}', [AerolineaController::class, 'destroy']);
    });

    // Rutas administrativas de aeropuertos
    Route::prefix('admin/aeropuertos')->group(function () {
        Route::get('/', [AeropuertoController::class, 'index']);
        Route::post('/', [AeropuertoController::class, 'store']);
        Route::get('/{id}', [AeropuertoController::class, 'show']);
        Route::put('/{id}', [AeropuertoController::class, 'update']);
        Route::delete('/{id}', [AeropuertoController::class, 'destroy']);
    });

    // Rutas administrativas de aviones
    Route::prefix('admin/aviones')->group(function () {
        Route::get('/', [AvionController::class, 'index']);
        Route::post('/', [AvionController::class, 'store']);
        Route::get('/{id}', [AvionController::class, 'show']);
        Route::put('/{id}', [AvionController::class, 'update']);
        Route::delete('/{id}', [AvionController::class, 'destroy']);
        Route::get('/aerolinea/{aerolinea_id}', [AvionController::class, 'porAerolinea']);
        Route::get('/{id}/estadisticas', [AvionController::class, 'estadisticas']);
    });

    // Rutas administrativas de usuarios
    Route::prefix('admin/usuarios')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\UserController::class, 'store']);
        Route::get('/estadisticas', [\App\Http\Controllers\UserController::class, 'estadisticas']);
        Route::get('/buscar', [\App\Http\Controllers\UserController::class, 'buscar']);
        Route::get('/{id}', [\App\Http\Controllers\UserController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\UserController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);
        Route::post('/{id}/activar', [\App\Http\Controllers\UserController::class, 'activar']);
        Route::post('/{id}/resetear-password', [\App\Http\Controllers\UserController::class, 'resetearPassword']);
    });
});

// Rutas de prueba y diagnóstico (sin duplicar)
// Las rutas de prueba ya están definidas al inicio del archivo

// Ruta de prueba para verificar funcionamiento de auth
Route::get('/test-auth', function () {
    $usuarios = \App\Models\User::limit(3)->get(['nombres', 'apellidos', 'email', 'activo']);
    return response()->json([
        'success' => true,
        'message' => 'Sistema de autenticación listo',
        'endpoints_auth' => [
            'registro' => 'POST /api/auth/registro',
            'login' => 'POST /api/auth/login',
            'perfil' => 'GET /api/auth/perfil (requiere token)'
        ],
        'usuarios_prueba' => [
            'admin' => [
                'email' => 'admin@reservavuelos.com',
                'password' => 'password123'
            ],
            'usuario' => [
                'email' => 'juan.rodriguez@email.com', 
                'password' => 'password123'
            ]
        ],
        'usuarios_existentes' => $usuarios
    ]);
});

// Ruta específica para probar CORS
Route::get('/test-cors', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => '🌐 CORS configurado correctamente',
        'cors_info' => [
            'origin' => $request->header('Origin'),
            'user_agent' => $request->header('User-Agent'),
            'method' => $request->method(),
            'headers' => [
                'Access-Control-Allow-Origin' => 'Configurado dinámicamente',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Access-Control-Allow-Credentials' => 'true'
            ]
        ],
        'frontend_ready' => true
    ]);
});

// Endpoint OPTIONS para manejar preflight requests manualmente
Route::options('/{any}', function () {
    return response('', 200);
})->where('any', '.*');