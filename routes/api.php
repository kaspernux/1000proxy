<?php

use Illuminate\Http\Request;
use App\Services\XUIService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;

// Mobile App API Routes
Route::prefix('v1')->middleware(['throttle:api'])->group(function () {
    // Authentication routes - stricter rate limiting
    Route::middleware(['throttle:auth'])->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });
    
    // Protected routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // User routes
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        
        // Server routes
        Route::get('/servers', [ServerController::class, 'index']);
        Route::get('/servers/{id}', [ServerController::class, 'show']);
        Route::get('/servers/{id}/plans', [ServerController::class, 'plans']);
        Route::get('/servers/{id}/stats', [ServerController::class, 'stats']);
        Route::get('/servers/search', [ServerController::class, 'search']);
        Route::get('/servers/featured', [ServerController::class, 'featured']);
        Route::get('/categories', [ServerController::class, 'categories']);
        Route::get('/locations', [ServerController::class, 'locations']);
        
        // Order routes - tighter rate limiting for orders
        Route::middleware(['throttle:orders'])->group(function () {
            Route::get('/orders', [OrderController::class, 'index']);
            Route::post('/orders', [OrderController::class, 'store']);
            Route::get('/orders/{id}', [OrderController::class, 'show']);
            Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);
            Route::get('/orders/{id}/configuration', [OrderController::class, 'configuration']);
            Route::get('/orders/stats', [OrderController::class, 'stats']);
        });
        
        // Wallet routes
        Route::get('/wallet', [WalletController::class, 'index']);
        Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
        Route::get('/wallet/transactions/{id}', [WalletController::class, 'transaction']);
        Route::get('/wallet/stats', [WalletController::class, 'stats']);
        Route::post('/wallet/deposit', [WalletController::class, 'createDeposit']);
        Route::get('/wallet/deposit/{id}/status', [WalletController::class, 'depositStatus']);
        Route::get('/wallet/currencies', [WalletController::class, 'currencies']);
    });
});

// Legacy XUI routes (for backward compatibility)
Route::middleware('auth:sanctum')->get('/admin', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('xui')->group(function () {
        Route::post('/login', [XUIService::class, 'login'])->name('xui.login');
        Route::get('/list', [XUIService::class, 'getInbounds'])->name('xui.inbounds.list');
        Route::get('/get/{id}', [XUIService::class, 'getInbound'])->name('xui.inbounds.get');
        Route::get('/getClientTraffics/{email}', [XUIService::class, 'getClientTraffics'])->name('xui.inbounds.getClientTraffics');
        Route::post('/createbackup', [XUIService::class, 'createBackup'])->name('xui.inbounds.createBackup');
        Route::post('/add', [XUIService::class, 'addInbound'])->name('xui.inbounds.add');
        Route::post('/del/{id}', [XUIService::class, 'deleteInbound'])->name('xui.inbounds.delete');
        Route::post('/update/{id}', [XUIService::class, 'updateInbound'])->name('xui.inbounds.update');
        Route::post('/clientIps/{email}', [XUIService::class, 'getClientIps'])->name('xui.inbounds.clientIps');
        Route::post('/clearClientIps/{email}', [XUIService::class, 'clearClientIps'])->name('xui.inbounds.clearClientIps');
        Route::post('/addClient', [XUIService::class, 'addClient'])->name('xui.inbounds.addClient');
        Route::post('/{id}/delClient/{clientId}', [XUIService::class, 'deleteClient'])->name('xui.inbounds.deleteClient');
        Route::post('/updateClient/{clientId}', [XUIService::class, 'updateClient'])->name('xui.inbounds.updateClient');
        Route::post('/{id}/resetClientTraffic/{email}', [XUIService::class, 'resetClientTraffic'])->name('xui.inbounds.resetClientTraffic');
        Route::post('/resetAllTraffics', [XUIService::class, 'resetAllTraffics'])->name('xui.inbounds.resetAllTraffics');
        Route::post('/resetAllClientTraffics/{id}', [XUIService::class, 'resetAllClientTraffics'])->name('xui.inbounds.resetAllClientTraffics');
        Route::post('/delDepletedClients/{id}', [XUIService::class, 'deleteDepletedClients'])->name('xui.inbounds.deleteDepletedClients');
        Route::get('/onlines', [XUIService::class, 'getOnlineUsers'])->name('xui.inbounds.getOnlineUsers');
    });
});