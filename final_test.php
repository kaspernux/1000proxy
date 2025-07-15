<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\Artisan;

echo "=== Clearing Cache ===\n";
try {
    Artisan::call('cache:clear');
    echo "Cache cleared: " . Artisan::output() . "\n";

    Artisan::call('config:clear');
    echo "Config cleared: " . Artisan::output() . "\n";

    Artisan::call('route:clear');
    echo "Routes cleared: " . Artisan::output() . "\n";

    Artisan::call('view:clear');
    echo "Views cleared: " . Artisan::output() . "\n";

    echo "All caches cleared successfully!\n";
} catch (Exception $e) {
    echo "Cache clear error: " . $e->getMessage() . "\n";
}

echo "\n=== Configuration Status ===\n";

// Check QR configuration
if (config('simple-qrcode')) {
    echo "QR Code configuration loaded: YES\n";
    echo "QR Writer: " . config('simple-qrcode.writer') . "\n";
    echo "QR Size: " . config('simple-qrcode.size') . "\n";
} else {
    echo "QR Code configuration loaded: NO\n";
}

// Test QR generation
echo "\n=== Testing QR Generation ===\n";
try {
    $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate('test');
    echo "QR Code generation: SUCCESS\n";
    echo "QR Code type: " . gettype($qrCode) . "\n";
    echo "QR Code length: " . strlen($qrCode) . " characters\n";
} catch (Exception $e) {
    echo "QR Code generation: FAILED - " . $e->getMessage() . "\n";
}
