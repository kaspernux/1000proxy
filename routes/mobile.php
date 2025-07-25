<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\Mobile\MobileServerController;
use App\Http\Controllers\Api\Mobile\MobileOrderController;
use App\Http\Controllers\Api\Mobile\MobilePaymentController;
use App\Http\Controllers\Api\Mobile\MobileNotificationController;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Mobile API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group.
|
*/

// Mobile Authentication Routes (Public)
Route::prefix('mobile/auth')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);
    Route::post('/register', [MobileAuthController::class, 'register']);
    Route::post('/verify', [MobileAuthController::class, 'verify']);
    Route::post('/forgot-password', [MobileAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [MobileAuthController::class, 'resetPassword']);
});

// Mobile Protected Routes
Route::prefix('mobile')->middleware(['auth:sanctum'])->group(function () {

    // Authentication Routes (Protected)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::post('/refresh', [MobileAuthController::class, 'refresh']);
        Route::get('/profile', [MobileAuthController::class, 'getProfile']);
        Route::put('/profile', [MobileAuthController::class, 'updateProfile']);
        Route::post('/change-password', [MobileAuthController::class, 'changePassword']);
    });

    // Server Management Routes
    Route::prefix('servers')->group(function () {
        Route::get('/plans', [MobileServerController::class, 'getServerPlans']);
        Route::get('/filters', [MobileServerController::class, 'getAvailableFilters']);
        Route::get('/search', [MobileServerController::class, 'searchServerPlans']);
        Route::get('/popular', [MobileServerController::class, 'getPopularPlans']);
        Route::get('/recommended', [MobileServerController::class, 'getRecommendedPlans']);
        Route::get('/locations', [MobileServerController::class, 'getLocations']);
        Route::get('/protocols', [MobileServerController::class, 'getProtocols']);
    });

    // Order Management Routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [MobileOrderController::class, 'getUserOrders']);
        Route::post('/', [MobileOrderController::class, 'createOrder']);
        Route::get('/{orderId}', [MobileOrderController::class, 'getOrderDetails']);
        Route::post('/{orderId}/cancel', [MobileOrderController::class, 'cancelOrder']);
        Route::post('/{orderId}/renew', [MobileOrderController::class, 'renewOrder']);
        Route::get('/{orderId}/configuration', [MobileOrderController::class, 'getOrderConfiguration']);
        Route::get('/{orderId}/qr-code', [MobileOrderController::class, 'downloadQRCode']);
    });

    // Payment Management Routes
    Route::prefix('payments')->group(function () {
        Route::get('/methods', [MobilePaymentController::class, 'getPaymentMethods']);
        Route::post('/process', [MobilePaymentController::class, 'processPayment']);
        Route::get('/status/{paymentId}', [MobilePaymentController::class, 'getPaymentStatus']);
        Route::get('/history', [MobilePaymentController::class, 'getPaymentHistory']);
        Route::post('/intent', [MobilePaymentController::class, 'createPaymentIntent']);
        Route::post('/confirm', [MobilePaymentController::class, 'confirmPayment']);
    });

    // Wallet Management Routes
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [MobilePaymentController::class, 'getWalletBalance']);
        Route::post('/add-funds', [MobilePaymentController::class, 'addFundsToWallet']);
        Route::get('/transactions', [MobilePaymentController::class, 'getWalletTransactions']);
    });

    // Device Management Routes (Optional)
    Route::prefix('device')->group(function () {
        Route::post('/register', function() {
            return response()->json(['message' => 'Device registration endpoint']);
        });
        Route::post('/update', function() {
            return response()->json(['message' => 'Device update endpoint']);
        });
        Route::get('/info', function() {
            return response()->json(['message' => 'Device info endpoint']);
        });
    });

    // Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [MobileNotificationController::class, 'getUserNotifications']);
        Route::post('/mark-read', [MobileNotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [MobileNotificationController::class, 'markAllAsRead']);
        Route::delete('/{notificationId}', [MobileNotificationController::class, 'deleteNotification']);
        Route::get('/settings', [MobileNotificationController::class, 'getNotificationSettings']);
        Route::post('/settings', [MobileNotificationController::class, 'updateNotificationSettings']);
        Route::post('/register-device', [MobileNotificationController::class, 'registerDevice']);
        Route::post('/unregister-device', [MobileNotificationController::class, 'unregisterDevice']);
        Route::post('/test', [MobileNotificationController::class, 'sendTestNotification']);
    });

    // Support Routes
    Route::prefix('support')->group(function () {
        Route::get('/tickets', function() {
            return response()->json(['message' => 'Get support tickets endpoint']);
        });
        Route::post('/tickets', function() {
            return response()->json(['message' => 'Create support ticket endpoint']);
        });
        Route::get('/faq', function() {
            return response()->json(['message' => 'Get FAQ endpoint']);
        });
    });
});

// Mobile Public Information Routes (No Authentication Required)
Route::prefix('mobile/public')->group(function () {
    Route::get('/app-info', function() {
        return response()->json([
            'app_name' => '1000Proxy Mobile',
            'version' => '1.0.0',
            'min_version' => '1.0.0',
            'update_required' => false,
            'maintenance_mode' => false
        ]);
    });

    Route::get('/server-status', function() {
        return response()->json([
            'status' => 'operational',
            'last_updated' => now()->toISOString()
        ]);
    });

    Route::get('/features', function() {
        return response()->json([
            'features' => [
                'multiple_protocols',
                'global_servers',
                'instant_setup',
                'high_speed',
                '24_7_support'
            ]
        ]);
    });
});
