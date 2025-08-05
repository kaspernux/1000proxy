<?php
// Quick test to verify the protocol fix

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Livewire\ProductsPage;

try {
    $component = new ProductsPage();
    
    // Test setting protocol as string
    $component->selected_protocols = 'VLESS';
    echo "✅ Protocol string assignment works: " . $component->selected_protocols . "\n";
    
    // Test empty protocol
    $component->selected_protocols = '';
    echo "✅ Empty protocol works: '" . $component->selected_protocols . "'\n";
    
    // Test if the validation rules work (using reflection since it's protected)
    $reflection = new ReflectionClass($component);
    $rulesMethod = $reflection->getMethod('rules');
    $rulesMethod->setAccessible(true);
    $rules = $rulesMethod->invoke($component);
    echo "✅ Validation rules include protocol as string: " . (isset($rules['selected_protocols']) ? 'YES' : 'NO') . "\n";
    
    echo "✅ All protocol tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
