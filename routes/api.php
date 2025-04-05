<?php

use Illuminate\Http\Request;
use App\Services\XUIService;
use Illuminate\Support\Facades\Route;

// Secure user route
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