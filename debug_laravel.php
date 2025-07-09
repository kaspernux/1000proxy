<?php
// Simple PHP test script to verify Laravel bootstrapping
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 1000proxy Laravel Debug Test ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Working Directory: " . getcwd() . "\n\n";

// Check if vendor directory exists
if (!is_dir('vendor')) {
    echo "✗ Vendor directory not found. Please run 'composer install' first.\n";
    exit(1);
}

// Check if Laravel is available
if (!file_exists('vendor/autoload.php')) {
    echo "✗ Composer autoloader not found. Please run 'composer install' first.\n";
    exit(1);
}

// Try to bootstrap Laravel
try {
    require_once 'vendor/autoload.php';
    
    // Create Laravel application
    $app = require_once 'bootstrap/app.php';
    
    // Boot the kernel
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "✓ Laravel application bootstrapped successfully\n";
    
    // Test service resolution
    $services = [
        'MonitoringService' => 'App\Services\MonitoringService',
        'CacheService' => 'App\Services\CacheService',
        'XUIService' => 'App\Services\XUIService',
    ];
    
    foreach ($services as $name => $class) {
        try {
            $service = $app->make($class);
            echo "✓ $name resolved successfully\n";
        } catch (Exception $e) {
            echo "✗ $name failed to resolve: " . $e->getMessage() . "\n";
        }
    }
    
    // Test database connection
    try {
        $connection = $app->make('db')->connection();
        $connection->getPdo();
        echo "✓ Database connection successful\n";
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    }
    
    // Test configuration
    $config = $app->make('config');
    echo "✓ App Environment: " . $config->get('app.env') . "\n";
    echo "✓ App Debug: " . ($config->get('app.debug') ? 'true' : 'false') . "\n";
    echo "✓ Database Driver: " . $config->get('database.default') . "\n";
    
} catch (Exception $e) {
    echo "✗ Laravel bootstrap failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Laravel Debug Test Complete ===\n";
echo "Application is ready for development!\n";
?>
