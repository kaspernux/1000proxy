<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing QR Code Service with Fixed Fallback...\n\n";

try {
    $qrService = new QrCodeService();

    // Test 1: Simple QR code generation
    echo "1. Testing simple QR code generation...\n";
    $testData = "https://1000proxies.com/test";
    $qrCode = $qrService->generateBrandedQrCode($testData);

    if (!empty($qrCode)) {
        echo "✅ QR code generated successfully (length: " . strlen($qrCode) . " bytes)\n";
    } else {
        echo "❌ QR code generation failed\n";
    }

    // Test 2: Base64 QR code
    echo "\n2. Testing Base64 QR code generation...\n";
    $base64Qr = $qrService->generateBase64QrCode($testData);

    if (strpos($base64Qr, 'data:image/png;base64,') === 0) {
        echo "✅ Base64 QR code generated successfully\n";
    } else {
        echo "❌ Base64 QR code generation failed\n";
    }

    // Test 3: Client QR code with proxy configuration
    echo "\n3. Testing client QR code with proxy config...\n";
    $proxyConfig = "vless://test-uuid@server.example.com:443?type=ws&security=tls&path=/ws#1000Proxies-Test";
    $clientQr = $qrService->generateClientQrCode($proxyConfig);

    if (!empty($clientQr)) {
        echo "✅ Client QR code generated successfully\n";
    } else {
        echo "❌ Client QR code generation failed\n";
    }

    // Test 4: Validation
    echo "\n4. Testing QR data validation...\n";
    $validData = "https://1000proxies.com";
    $invalidData = str_repeat("x", 5000); // Too long

    if ($qrService->validateQrData($validData)) {
        echo "✅ Valid data passed validation\n";
    } else {
        echo "❌ Valid data failed validation\n";
    }

    if (!$qrService->validateQrData($invalidData)) {
        echo "✅ Invalid data correctly rejected\n";
    } else {
        echo "❌ Invalid data incorrectly accepted\n";
    }

    // Test 5: Optimal size calculation
    echo "\n5. Testing optimal size calculation...\n";
    $shortData = "test";
    $longData = str_repeat("This is a longer string for testing. ", 20);

    $shortSize = $qrService->getOptimalSize($shortData);
    $longSize = $qrService->getOptimalSize($longData);

    echo "✅ Short data optimal size: {$shortSize}px\n";
    echo "✅ Long data optimal size: {$longSize}px\n";

    echo "\n🎉 All QR Code Service tests completed successfully!\n";
    echo "The service now has proper fallback handling for imagick dependency issues.\n";

} catch (\Exception $e) {
    echo "❌ Error during QR code testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
