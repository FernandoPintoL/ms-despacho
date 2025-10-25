<?php

use App\Http\Controllers\Api\AmbulanciaController;
use App\Http\Controllers\Api\DespachoController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas API para el microservicio de despacho de ambulancias
|
*/

// Rutas públicas (sin autenticación por ahora)
Route::prefix('v1')->group(function () {
    
    // Ambulancias
    Route::get('/ambulancias', [AmbulanciaController::class, 'index']);
    Route::get('/ambulancias/{id}', [AmbulanciaController::class, 'show']);
    Route::post('/ambulancias/{id}/ubicacion', [AmbulanciaController::class, 'actualizarUbicacion']);
    Route::patch('/ambulancias/{id}/estado', [AmbulanciaController::class, 'actualizarEstado']);

    // Despachos
    Route::get('/despachos', [DespachoController::class, 'index']);
    Route::post('/despachos', [DespachoController::class, 'store']);
    Route::get('/despachos/{id}', [DespachoController::class, 'show']);
    Route::patch('/despachos/{id}', [DespachoController::class, 'update']);
    Route::post('/despachos/{id}/rastreo', [DespachoController::class, 'rastreo']);

    // Health checks
    Route::get('/health', [HealthController::class, 'index']);
    Route::get('/health/microservices', [HealthController::class, 'microservices']);
});

// Rutas protegidas (con autenticación Sanctum - para implementar después)
// Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
//     // Rutas protegidas aquí
// });
