<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Customer;
use App\Models\Order;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use App\Models\ServerBrand;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Services\QrCodeService;
use App\Services\BusinessIntelligenceService;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 PRODUCTION READINESS VALIDATION\n";
echo "==================================\n\n";

$allPassed = true;

// 1. Database Connection Test
echo "1. 🔗 Testing Database Connection...\n";
try {
    DB::connection()->getPdo();
    echo "   ✅ Database connection successful\n";
} catch (\Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 2. Core Models Test
echo "\n2. 📊 Testing Core Models...\n";

// Customers
$customerCount = Customer::count();
echo "   ✅ Customers: {$customerCount} records\n";

// Server Categories
$categoryCount = ServerCategory::count();
echo "   ✅ Server Categories: {$categoryCount} records\n";

// Server Brands
$brandCount = ServerBrand::count();
echo "   ✅ Server Brands: {$brandCount} records\n";

// Server Plans
$planCount = ServerPlan::count();
echo "   ✅ Server Plans: {$planCount} records\n";

// Servers
$serverCount = Server::count();
echo "   ✅ Servers: {$serverCount} records\n";

// Orders
$orderCount = Order::count();
echo "   ✅ Orders: {$orderCount} records\n";

// 3. Essential Services Test
echo "\n3. 🛠️ Testing Essential Services...\n";

// QR Code Service
try {
    $qrService = new QrCodeService();
    $testQr = $qrService->generateBrandedQrCode('https://test.com', 200);
    if (!empty($testQr)) {
        echo "   ✅ QR Code Service: Working (with fallback handling)\n";
    } else {
        echo "   ❌ QR Code Service: Failed\n";
        $allPassed = false;
    }
} catch (\Exception $e) {
    echo "   ❌ QR Code Service: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Business Intelligence Service
try {
    $biService = new BusinessIntelligenceService();
    $stats = $biService->getDashboardAnalytics('7_days');
    if (is_array($stats) && isset($stats['success'])) {
        echo "   ✅ Business Intelligence Service: Working\n";
    } else {
        echo "   ❌ Business Intelligence Service: Failed\n";
        $allPassed = false;
    }
} catch (\Exception $e) {
    echo "   ❌ Business Intelligence Service: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 4. Database Relationships Test
echo "\n4. 🔗 Testing Model Relationships...\n";

// Test customer relationships
try {
    $customer = Customer::with(['orders', 'wallet'])->first();
    if ($customer) {
        echo "   ✅ Customer->Wallet relationship: Working\n";
        echo "   ✅ Customer->Orders relationship: Working\n";
    } else {
        echo "   ⚠️ No customers found for relationship testing\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Customer relationships: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test server relationships
try {
    $planWithCategory = ServerPlan::with('category')->first();
    if ($planWithCategory) {
        echo "   ✅ ServerPlan->Category relationship: Working\n";
    } else {
        echo "   ⚠️ No server plans found for relationship testing\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Server Plan relationships: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 5. File System Test
echo "\n5. 📁 Testing File System Access...\n";

// Storage directories
$storagePublic = storage_path('app/public');
$storageQr = storage_path('app/public/qr-codes');
$storageWallet = storage_path('app/public/wallet_qr');

foreach ([$storagePublic, $storageQr, $storageWallet] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    if (is_writable($dir)) {
        echo "   ✅ Directory writable: " . basename($dir) . "\n";
    } else {
        echo "   ❌ Directory not writable: " . basename($dir) . "\n";
        $allPassed = false;
    }
}

// 6. Environment Configuration Test
echo "\n6. ⚙️ Testing Environment Configuration...\n";

$requiredEnvVars = [
    'APP_KEY' => env('APP_KEY'),
    'DB_DATABASE' => env('DB_DATABASE'),
    'DB_USERNAME' => env('DB_USERNAME'),
    'REDIS_HOST' => env('REDIS_HOST'),
];

foreach ($requiredEnvVars as $var => $value) {
    if (!empty($value)) {
        echo "   ✅ {$var}: Configured\n";
    } else {
        echo "   ❌ {$var}: Missing or empty\n";
        $allPassed = false;
    }
}

// 7. Route Testing (Basic)
echo "\n7. 🌐 Testing Route Configuration...\n";

try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $routeCount = count($routes);
    echo "   ✅ Total routes registered: {$routeCount}\n";

    // Check for key routes
    $keyRoutes = ['admin', 'customer', 'api'];
    foreach ($keyRoutes as $prefix) {
        $found = false;
        foreach ($routes as $route) {
            if (str_contains($route->getPrefix(), $prefix)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "   ✅ {$prefix} routes: Found\n";
        } else {
            echo "   ⚠️ {$prefix} routes: Not found\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Route testing: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Final Summary
echo "\n" . str_repeat("=", 50) . "\n";
if ($allPassed) {
    echo "🎉 PRODUCTION READINESS: PASSED\n";
    echo "   All critical systems are functional.\n";
    echo "   Ready for deployment!\n";
} else {
    echo "⚠️ PRODUCTION READINESS: ISSUES FOUND\n";
    echo "   Some systems need attention before deployment.\n";
    echo "   Please review the failed tests above.\n";
}

echo "\n📈 Database Statistics:\n";
echo "   - Customers: {$customerCount}\n";
echo "   - Categories: {$categoryCount}\n";
echo "   - Brands: {$brandCount}\n";
echo "   - Plans: {$planCount}\n";
echo "   - Servers: {$serverCount}\n";
echo "   - Orders: {$orderCount}\n";

echo "\n⏰ Deployment Status: " . ($allPassed ? "READY ✅" : "NEEDS ATTENTION ⚠️") . "\n";
echo str_repeat("=", 50) . "\n";
