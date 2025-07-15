<?php
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing HomePage component...\n";

    // Create a mock request
    $request = \Illuminate\Http\Request::create('/', 'GET');

    // Set up the request context
    app('request')->replace($request->all());

    // Test HomePage component creation
    $homePage = new App\Livewire\HomePage();
    echo "✅ HomePage component created successfully\n";

    // Test view rendering
    $view = $homePage->render();
    echo "✅ HomePage view rendered successfully\n";

    echo "\n🎉 HomePage component is working!\n";

} catch (Exception $e) {
    echo "❌ Error with HomePage: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    if ($e->getPrevious()) {
        echo "Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
}
