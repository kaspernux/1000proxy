<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PWAController;

/*
|--------------------------------------------------------------------------
| PWA Routes
|--------------------------------------------------------------------------
|
| Routes for Progressive Web App functionality including manifest,
| service worker, offline page, and PWA management endpoints.
|
*/

// Public PWA Files
Route::get('/manifest.json', [PWAController::class, 'manifest'])
    ->name('pwa.manifest');

Route::get('/sw.js', [PWAController::class, 'serviceWorker'])
    ->name('pwa.service-worker');

Route::get('/offline', [PWAController::class, 'offline'])
    ->name('pwa.offline');

// PWA API Routes
Route::prefix('api/pwa')->group(function () {
    // Public endpoints
    Route::get('/status', [PWAController::class, 'status'])
        ->name('pwa.api.status');

    Route::get('/capabilities', [PWAController::class, 'getCapabilities'])
        ->name('pwa.api.capabilities');

    Route::get('/meta-tags', [PWAController::class, 'getMetaTags'])
        ->name('pwa.api.meta-tags');

    Route::post('/track-installation', [PWAController::class, 'trackInstallation'])
        ->name('pwa.api.track-installation');

    Route::get('/notifications', [PWAController::class, 'getNotifications'])
        ->name('pwa.api.notifications');

    Route::get('/handle-protocol', [PWAController::class, 'handleProtocol'])
        ->name('pwa.api.handle-protocol');

    // Protected endpoints (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/install', [PWAController::class, 'install'])
            ->name('pwa.api.install');

        Route::post('/update-cache', [PWAController::class, 'updateCache'])
            ->name('pwa.api.update-cache');

        Route::post('/send-notification', [PWAController::class, 'sendNotification'])
            ->name('pwa.api.send-notification');

        Route::delete('/notifications', [PWAController::class, 'clearNotifications'])
            ->name('pwa.api.clear-notifications');
    });

    // Admin-only endpoints
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/broadcast-notification', [PWAController::class, 'broadcastNotification'])
            ->name('pwa.api.broadcast-notification');

        Route::get('/analytics', [PWAController::class, 'getAnalytics'])
            ->name('pwa.api.analytics');

        Route::post('/maintenance-mode', [PWAController::class, 'setMaintenanceMode'])
            ->name('pwa.api.maintenance-mode');
    });
});

// Fallback routes for PWA deep linking
Route::fallback(function () {
    // Check if request is from PWA
    if (request()->header('X-Requested-With') === 'PWA' ||
        str_contains(request()->header('User-Agent', ''), 'PWA')) {

        // Return offline page for PWA requests
        return view('pwa.offline');
    }

    // Regular 404 for non-PWA requests
    abort(404);
});
