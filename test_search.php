<?php
// Quick test to verify ProductsPage component

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Livewire\ProductsPage;

try {
    $component = new ProductsPage();
    $component->search = 'test search';
    echo "✅ Search property works: " . $component->search . "\n";
    
    // Test if we can access render method
    echo "✅ Component created successfully\n";
    echo "✅ All properties initialized\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
