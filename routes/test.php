<?php

use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

// QR Code Test Route
Route::get('/test-qr', function () {
    try {
        $qrCode = QrCode::size(300)->generate('vless://test-connection');

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-cache');
    } catch (Exception $e) {
        return response()->json([
            'error' => 'QR Code generation failed',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => '🎉 1000proxy application is running successfully!',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'version' => '2.0.0',
        'components' => [
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'models' => [
                'User' => class_exists('App\Models\User'),
                'Server' => class_exists('App\Models\Server'),
                'Order' => class_exists('App\Models\Order'),
                'ServerPlan' => class_exists('App\Models\ServerPlan'),
            ],
            'services' => [
                'XuiService' => class_exists('App\Services\XuiService'),
                'AdvancedProxyService' => class_exists('App\Services\AdvancedProxyService'),
            ]
        ]
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->format('c')
    ]);
});
