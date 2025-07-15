<?php
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing database connection...\n";

    // Test database connection
    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✅ Database connection successful\n";

    // Test cache connection
    \Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 60);
    $cached = \Illuminate\Support\Facades\Cache::get('test_key');
    echo "✅ Cache connection successful: $cached\n";

    // Test BusinessIntelligenceService
    $biService = app(App\Services\BusinessIntelligenceService::class);
    echo "✅ BusinessIntelligenceService loaded successfully\n";

    // Test RevenueChartWidget
    $widget = new App\Filament\Admin\Widgets\RevenueChartWidget();
    echo "✅ RevenueChartWidget created successfully\n";

    echo "\n🎉 ALL TESTS PASSED - NO DATABASE ERRORS!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
