<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServerPlanController;
use App\Http\Controllers\ServerInboundController;
use App\Http\Controllers\ServerClientController;
use App\Http\Controllers\ServerConfigController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\XUIController;

// Secure user route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Group routes and apply middleware
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('server-plans', ServerPlanController::class);
    Route::apiResource('server-inbounds', ServerInboundController::class);
    Route::apiResource('server-clients', ServerClientController::class);
    Route::apiResource('server-configs', ServerConfigController::class);
    Route::apiResource('servers', ServerController::class); // Added semicolon here
    Route::post('servers/batch-create', [ServerController::class, 'batchCreate']);

    // XUI Routes
    Route::prefix('xui')->group(function () {
        Route::post('login', [XUIController::class, 'login']);
        Route::get('inbounds', [XUIController::class, 'getInbounds']);
        Route::post('inbounds', [XUIController::class, 'addInbound']);
        Route::put('inbounds/{id}', [XUIController::class, 'updateInbound']);
        Route::delete('inbounds/{id}', [XUIController::class, 'deleteInbound']);
        Route::post('clients', [XUIController::class, 'addClient']);
        Route::put('clients/{clientId}', [XUIController::class, 'updateClient']);
        Route::delete('clients/{clientId}', [XUIController::class, 'deleteClient']);
    });
});