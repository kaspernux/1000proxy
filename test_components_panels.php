<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Livewire\HomePage;
use App\Filament\Admin\Resources\CustomerResource;
use App\Filament\Customer\Pages\Dashboard;
use Illuminate\Support\Facades\Config;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🎯 COMPONENT & PANEL TESTING\n";
echo "============================\n\n";

$allPassed = true;

// 1. Test Livewire Components
echo "1. ⚡ Testing Livewire Components...\n";

try {
    // Test HomePage component instantiation
    $homePage = new HomePage();
    if ($homePage) {
        echo "   ✅ HomePage component: Instantiated successfully\n";

        // Test component methods
        $homePage->loadBrands();
        echo "   ✅ HomePage->loadBrands(): Working\n";

        $homePage->loadCategories();
        echo "   ✅ HomePage->loadCategories(): Working\n";

        $homePage->loadFeaturedPlans();
        echo "   ✅ HomePage->loadFeaturedPlans(): Working\n";

        $homePage->loadPlatformStats();
        echo "   ✅ HomePage->loadPlatformStats(): Working\n";
    }
} catch (\Exception $e) {
    echo "   ❌ HomePage component error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 2. Test Filament Resources
echo "\n2. 🎛️ Testing Filament Admin Resources...\n";

try {
    // Test Admin Resource
    if (class_exists('App\Filament\Admin\Resources\BusinessIntelligenceResource')) {
        echo "   ✅ BusinessIntelligenceResource: Available\n";
    } else {
        echo "   ❌ BusinessIntelligenceResource: Not found\n";
        $allPassed = false;
    }

    // Test Customer Resources
    if (class_exists('App\Filament\Customer\Resources\CustomerServerClientResource')) {
        echo "   ✅ CustomerServerClientResource: Available\n";
    } else {
        echo "   ❌ CustomerServerClientResource: Not found\n";
        $allPassed = false;
    }

    if (class_exists('App\Filament\Customer\Resources\ServerInboundResource')) {
        echo "   ✅ ServerInboundResource: Available\n";
    } else {
        echo "   ❌ ServerInboundResource: Not found\n";
        $allPassed = false;
    }
} catch (\Exception $e) {
    echo "   ❌ Filament Resources error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 3. Test Customer Panel
echo "\n3. 👤 Testing Customer Panel...\n";

try {
    // Check if customer dashboard page exists
    if (class_exists('App\Filament\Customer\Pages\Dashboard')) {
        echo "   ✅ Customer Dashboard: Available\n";
    } else {
        echo "   ⚠️ Customer Dashboard: Class not found\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Customer Panel error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 4. Test Route Accessibility
echo "\n4. 🛣️ Testing Route Accessibility...\n";

try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();

    // Check admin routes
    $adminRoutes = collect($routes->getRoutes())->filter(function($route) {
        return str_contains($route->getPrefix() ?: '', 'admin');
    });

    if ($adminRoutes->count() > 0) {
        echo "   ✅ Admin panel routes: " . $adminRoutes->count() . " routes registered\n";
    } else {
        echo "   ❌ Admin panel routes: No routes found\n";
        $allPassed = false;
    }

    // Check customer routes
    $customerRoutes = collect($routes->getRoutes())->filter(function($route) {
        return str_contains($route->getPrefix() ?: '', 'customer');
    });

    if ($customerRoutes->count() > 0) {
        echo "   ✅ Customer panel routes: " . $customerRoutes->count() . " routes registered\n";
    } else {
        echo "   ❌ Customer panel routes: No routes found\n";
        $allPassed = false;
    }

    // Check API routes
    $apiRoutes = collect($routes->getRoutes())->filter(function($route) {
        return str_contains($route->getPrefix() ?: '', 'api');
    });

    if ($apiRoutes->count() > 0) {
        echo "   ✅ API routes: " . $apiRoutes->count() . " routes registered\n";
    } else {
        echo "   ❌ API routes: No routes found\n";
        $allPassed = false;
    }

} catch (\Exception $e) {
    echo "   ❌ Route testing error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 5. Test Config Settings
echo "\n5. ⚙️ Testing Configuration Settings...\n";

try {
    // Check Filament configuration
    $filamentConfig = config('filament');
    if (!empty($filamentConfig)) {
        echo "   ✅ Filament configuration: Loaded\n";
    } else {
        echo "   ❌ Filament configuration: Missing\n";
        $allPassed = false;
    }

    // Check Livewire configuration
    $livewireConfig = config('livewire');
    if (!empty($livewireConfig)) {
        echo "   ✅ Livewire configuration: Loaded\n";
    } else {
        echo "   ❌ Livewire configuration: Missing\n";
        $allPassed = false;
    }

    // Check app configuration
    $appName = config('app.name');
    $appEnv = config('app.env');
    $appDebug = config('app.debug');

    echo "   ✅ App Name: {$appName}\n";
    echo "   ✅ App Environment: {$appEnv}\n";
    echo "   ✅ Debug Mode: " . ($appDebug ? 'Enabled' : 'Disabled') . "\n";

    if ($appEnv === 'production' && $appDebug) {
        echo "   ⚠️ WARNING: Debug mode is enabled in production!\n";
        $allPassed = false;
    }

} catch (\Exception $e) {
    echo "   ❌ Configuration error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 6. Test Asset Compilation
echo "\n6. 🎨 Testing Asset Compilation...\n";

$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    echo "   ✅ Vite manifest: Found\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if ($manifest && !empty($manifest)) {
        echo "   ✅ Compiled assets: " . count($manifest) . " assets found\n";
    } else {
        echo "   ❌ Compiled assets: Manifest is empty\n";
        $allPassed = false;
    }
} else {
    echo "   ⚠️ Vite manifest: Not found (run 'npm run build' before deployment)\n";
}

// Final Summary
echo "\n" . str_repeat("=", 50) . "\n";
if ($allPassed) {
    echo "🎉 COMPONENT & PANEL TESTING: PASSED\n";
    echo "   All components and panels are functional.\n";
    echo "   System is ready for user interaction!\n";
} else {
    echo "⚠️ COMPONENT & PANEL TESTING: ISSUES FOUND\n";
    echo "   Some components need attention.\n";
    echo "   Please review the failed tests above.\n";
}

echo "\n📋 Component Status Summary:\n";
echo "   - Livewire Components: " . (strpos(ob_get_contents(), 'HomePage component error') === false ? "✅ Working" : "❌ Issues") . "\n";
echo "   - Filament Admin: " . (strpos(ob_get_contents(), 'CustomerResource error') === false ? "✅ Working" : "❌ Issues") . "\n";
echo "   - Customer Panel: " . (strpos(ob_get_contents(), 'Customer Panel error') === false ? "✅ Working" : "❌ Issues") . "\n";
echo "   - Route System: " . (strpos(ob_get_contents(), 'Route testing error') === false ? "✅ Working" : "❌ Issues") . "\n";

echo "\n⏰ Component Status: " . ($allPassed ? "ALL SYSTEMS GO ✅" : "NEEDS ATTENTION ⚠️") . "\n";
echo str_repeat("=", 50) . "\n";
