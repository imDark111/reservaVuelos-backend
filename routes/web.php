<?php

use Illuminate\Support\Facades\Route;

// Redirect root to API documentation
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Backend API - Sistema de Reserva de Vuelos',
        'version' => '1.0.0',
        'api_base_url' => url('api'),
        'endpoints' => [
            'test' => url('api/test'),
            'info' => url('api/info'), 
            'cors_test' => url('api/test-cors'),
            'auth' => [
                'registro' => url('api/auth/registro'),
                'login' => url('api/auth/login')
            ]
        ],
        'documentation' => 'Ver archivos README en el proyecto'
    ]);
});
