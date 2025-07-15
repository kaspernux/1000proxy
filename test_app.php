<?php

// Simple test to check if the application loads without errors
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

try {
    // Test if the application can boot
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "✅ Application boots successfully!\n";

    // Test if models can be loaded
    $user = new App\Models\User();
    echo "✅ User model loads successfully!\n";

    $server = new App\Models\Server();
    echo "✅ Server model loads successfully!\n";

    $order = new App\Models\Order();
    echo "✅ Order model loads successfully!\n";

    // Test if services can be instantiated
    $xuiService = $app->make(App\Services\XuiService::class, ['server' => $server]);
    echo "✅ XuiService can be instantiated!\n";

    echo "\n🎉 All basic tests passed! The application appears to be working correctly.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}
