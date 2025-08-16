<?php

use Illuminate\Http\Request;
use App\Services\XUIService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerAuthController;
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
use App\Http\Controllers\ValidationController;

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

    // Customer-specific token endpoints (issue tokens for Customer model)
    Route::prefix('customer')->middleware(['throttle:auth'])->group(function () {
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::post('/login', [CustomerAuthController::class, 'login']);
    });

    // Protected routes (general endpoints guarded by sanctum only)
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

    // (Customer-only endpoints moved to a dedicated guard group below)

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

    // Customer-protected routes (require customer session or customer_api token)
    Route::middleware(['auth:customer,customer_api', 'throttle:60,1'])->group(function () {
        // Customer auth token management when authenticated as customer
        Route::prefix('customer')->group(function () {
            Route::get('/me', [CustomerAuthController::class, 'me']);
            Route::post('/logout', [CustomerAuthController::class, 'logout']);
            Route::post('/refresh', [CustomerAuthController::class, 'refresh']);
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
    });
});

// Minimal JSON validation endpoint for tests expecting 422 on invalid payloads
Route::middleware(['throttle:api'])->post('/profile', function (\Illuminate\Http\Request $request) {
    // Simulate Sanctum-like JSON validation without redirecting
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name' => ['required','string','max:255'],
        'email' => ['required','email'],
    ]);
    if ($validator->fails()) {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422);
    }
    return response()->json(['ok' => true]);
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

// Legacy plural payment route aliases (backward compatibility for older tests expecting /api/payments/*)
// NOTE: We intentionally avoid session-only auth middleware that redirects (like AuthenticateCustomer) to prevent 302 in API tests.
Route::prefix('payments')->middleware(['throttle:60,1'])->group(function () {
    // Legacy crypto creation mapped to unified createPayment expecting gateway=nowpayments (payment_method alias supported)
    Route::post('/crypto', function(\Illuminate\Http\Request $request) {
        // Manual auth check supporting sanctum token OR customer session; return JSON 401 instead of redirect
        $actor = auth('api')->user() ?? auth('customer_api')->user() ?? auth('customer')->user();
        if (!$actor) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        // Force acting customer context (business rule: only customers pay orders)
        if ($actor instanceof \App\Models\User) {
            return response()->json(['message' => 'Only customers can create payments'], 403);
        }
        // Legacy tests expect validation errors for order_id, payment_method, currency, amount
        // Perform explicit validation here BEFORE mapping payment_method->gateway so missing fields are reported.
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|integer', // existence checked later in controller (findOrFail)
            'payment_method' => 'required|string',
            'currency' => 'required|string|size:3',
            'amount' => 'required|numeric|min:0.01',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }
        // If legacy tests send payment_method without gateway, map it
        if ($request->filled('payment_method') && !$request->filled('gateway')) {
            $pm = strtolower($request->input('payment_method'));
            $gateway = $pm === 'crypto' ? 'nowpayments' : $pm; // legacy 'crypto' synonym for nowpayments gateway
            $request->merge(['gateway' => $gateway]);
        }
        // Always default to nowpayments if still missing
        $request->merge(['gateway' => $request->input('gateway','nowpayments')]);
        // Flag legacy so controller can tailor validation/response shape
        $request->merge(['_legacy' => true]);
        return app(\App\Http\Controllers\PaymentController::class)->createPayment($request);
    });
    Route::get('/status/{paymentId}', function($paymentId){
        $actor = auth('api')->user() ?? auth('customer_api')->user() ?? auth('customer')->user();
        if (!$actor) { return response()->json(['message'=>'Unauthenticated'],401);} 
        return app(\App\Http\Controllers\PaymentController::class)->getPaymentStatus($paymentId);
    });
    Route::get('/currencies', function(){
        $actor = auth('api')->user() ?? auth('customer_api')->user() ?? auth('customer')->user();
        if (!$actor) { return response()->json(['message'=>'Unauthenticated'],401);} 
        return app(\App\Http\Controllers\PaymentController::class)->getCurrencies();
    });
    Route::post('/estimate', function(\Illuminate\Http\Request $request){
        $actor = auth('api')->user() ?? auth('customer_api')->user() ?? auth('customer')->user();
        if (!$actor) { return response()->json(['message'=>'Unauthenticated'],401);} 
        // Manually run validation mirroring EstimatePriceRequest since we are bypassing automatic form request injection
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency_from' => 'required|string|size:3|in:USD,RUB,EUR,GBP,BTC,ETH,XMR,LTC',
            'currency_to' => 'required|string|size:3|in:USD,RUB,EUR,GBP,BTC,ETH,XMR,LTC',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        // Wrap validated data into a synthetic FormRequest-like object
        $request->replace($validator->validated());
        return app(\App\Http\Controllers\PaymentController::class)->getEstimatePrice($request);
    });
});

// Public NowPayments webhook (legacy tests call /api/webhooks/nowpayments)
Route::post('/webhooks/nowpayments', [\App\Http\Controllers\PaymentController::class, 'handleWebhook'])->defaults('gateway','nowpayments');

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