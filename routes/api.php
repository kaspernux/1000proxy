<?php

use Illuminate\Http\Request;
use App\Services\XUIService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ServerPlanFilterController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\Admin\AdvancedProxyController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Horizon;

// Mobile App API Routes
Route::middleware(['throttle:api'])->group(function () {
    // Lightweight health/queue status endpoint
    Route::get('/health/queue', function() {
        $connection = config('queue.default');
        $queueName = config('queue.connections.' . $connection . '.queue', 'default');
        $size = Queue::size($queueName);
        $failed = DB::table('failed_jobs')->count();
        $horizonStatus = class_exists(Horizon::class) ? (Horizon::isPaused() ? 'paused' : 'running') : 'unavailable';
        return response()->json([
            'ok' => true,
            'queue' => [
                'connection' => $connection,
                'name' => $queueName,
                'size' => $size,
                'failed' => $failed,
            ],
            'horizon' => $horizonStatus,
            'timestamp' => now()->toIso8601String(),
        ]);
    });
    // Authentication routes - stricter rate limiting
    Route::middleware(['throttle:auth'])->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes (general user endpoints guarded by sanctum only)
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

        // Advanced Server Plan Filtering - New Feature
        Route::get('/server-plans', [ServerPlanFilterController::class, 'index']);
        Route::get('/server-plans/filters', [ServerPlanFilterController::class, 'getFilters']);

    // (Payment routes moved out; see below unified multi-guard group)

        // Advanced Proxy Management API Routes (legacy grouping retained for backward compatibility)
        Route::prefix('advanced-proxy')->group(function () {
            Route::post('/initialize-setup', [AdvancedProxyController::class, 'initializeSetup']);
            Route::get('/dashboard', [AdvancedProxyController::class, 'getDashboard']);
            Route::post('/enable-auto-rotation', [AdvancedProxyController::class, 'enableAutoRotation']);
                // (Legacy payment routes removed; replaced by unified multi-guard group below)
            Route::post('/setup-load-balancer', [AdvancedProxyController::class, 'setupLoadBalancer']);
            Route::post('/setup-health-monitoring', [AdvancedProxyController::class, 'setupHealthMonitoring']);
            Route::get('/performance-analytics', [AdvancedProxyController::class, 'getPerformanceAnalytics']);
            Route::get('/health-status', [AdvancedProxyController::class, 'getHealthStatus']);
            Route::post('/execute-ip-rotation', [AdvancedProxyController::class, 'executeIPRotation']);
            Route::put('/update-load-balancer', [AdvancedProxyController::class, 'updateLoadBalancer']);
            Route::get('/load-balancer-metrics', [AdvancedProxyController::class, 'getLoadBalancerMetrics']);
            Route::post('/optimize-setup', [AdvancedProxyController::class, 'optimizeSetup']);
            Route::get('/health-report', [AdvancedProxyController::class, 'getHealthReport']);
            Route::post('/automated-maintenance', [AdvancedProxyController::class, 'executeAutomatedMaintenance']);
            Route::post('/configure-advanced-options', [AdvancedProxyController::class, 'configureAdvancedOptions']);
            Route::get('/proxy-configurations', [AdvancedProxyController::class, 'getProxyConfigurations']);
            Route::get('/users', [AdvancedProxyController::class, 'getUsers']);
            Route::get('/system-overview', [AdvancedProxyController::class, 'getSystemOverview']);
        });

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

        // QR Code routes - Branded 1000 Proxies QR generation
        Route::prefix('qr')->group(function () {
            Route::post('/generate', [QrCodeController::class, 'generate']);
            Route::post('/client', [QrCodeController::class, 'generateClient']);
            Route::post('/subscription', [QrCodeController::class, 'generateSubscription']);
            Route::post('/download', [QrCodeController::class, 'generateDownload']);
            Route::post('/client-set', [QrCodeController::class, 'generateClientSet']);
            Route::post('/mobile-app', [QrCodeController::class, 'generateMobileApp']);
            Route::post('/optimal-size', [QrCodeController::class, 'getOptimalSize']);
            Route::post('/validate', [QrCodeController::class, 'validateData']);
        });
    });
});

// Unified Payment API routes allowing either API token (sanctum) or customer session guard.
// Ordering: placed after public + auth group definitions so it isn't nested inside sanctum-only group.
// NOTE: This file is already within /api route group by RouteServiceProvider; avoid duplicating 'api'.
Route::prefix('payment')->middleware(['throttle:60,1'])->group(function () {
    // Accept either sanctum (api/customer_api) or session customer guard
    Route::middleware(['auth:sanctum,customer,customer_api'])->group(function () {
        Route::post('/create', [\App\Http\Controllers\PaymentController::class, 'createPayment']);
        Route::post('/topup', [\App\Http\Controllers\PaymentController::class, 'topUpWallet']);
        Route::post('/refund', [\App\Http\Controllers\PaymentController::class, 'refundPayment']);
        Route::get('/status/{orderId}', [\App\Http\Controllers\PaymentController::class, 'getPaymentStatusByOrder']);
        Route::get('/status-by-id/{paymentId}', [\App\Http\Controllers\PaymentController::class, 'getPaymentStatus']);
        Route::get('/gateways', [\App\Http\Controllers\PaymentController::class, 'getAvailableGateways']);
        Route::get('/currencies', [\App\Http\Controllers\PaymentController::class, 'getCurrencies']);
        Route::post('/webhook/{gateway}', [\App\Http\Controllers\PaymentController::class, 'handleWebhook']);
    });
});

// Public QR Code routes (no authentication required)
Route::prefix('public/qr')->middleware(['throttle:qr'])->group(function () {
    Route::post('/generate', [QrCodeController::class, 'generate']);
    Route::post('/validate', [QrCodeController::class, 'validateData']);
    Route::post('/optimal-size', [QrCodeController::class, 'getOptimalSize']);
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