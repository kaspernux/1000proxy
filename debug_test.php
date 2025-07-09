<?php

// Simple debug bootstrap script to test the application

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";

// Check if .env file exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "ERROR: .env file not found\n";
    exit(1);
}

echo ".env file exists\n";

// Try to load composer autoloader
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "ERROR: Composer autoloader not found. Run 'composer install'\n";
    exit(1);
}

require_once __DIR__ . '/vendor/autoload.php';
echo "Composer autoloader loaded\n";

// Try to load Laravel
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "Laravel application bootstrapped\n";
    
    // Test basic configuration
    $config = $app['config'];
    echo "App Name: " . $config->get('app.name') . "\n";
    echo "App Environment: " . $config->get('app.env') . "\n";
    echo "App Debug: " . ($config->get('app.debug') ? 'true' : 'false') . "\n";
    
    // Test database configuration
    echo "Database Connection: " . $config->get('database.default') . "\n";
    
    // Test cache configuration
    echo "Cache Driver: " . $config->get('cache.default') . "\n";
    
    // Test queue configuration
    echo "Queue Driver: " . $config->get('queue.default') . "\n";
    
    echo "\n✅ Basic Laravel bootstrap successful!\n";
    
} catch (Exception $e) {
    echo "❌ Laravel bootstrap failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Test service registration
try {
    $monitoringService = $app->make(\App\Services\MonitoringService::class);
    echo "✅ MonitoringService can be resolved\n";
} catch (Exception $e) {
    echo "❌ MonitoringService resolution failed: " . $e->getMessage() . "\n";
}

try {
    $cacheService = $app->make(\App\Services\CacheService::class);
    echo "✅ CacheService can be resolved\n";
} catch (Exception $e) {
    echo "❌ CacheService resolution failed: " . $e->getMessage() . "\n";
}

echo "\n🔍 Debug test completed!\n";
