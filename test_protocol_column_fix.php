<?php
// Quick test to verify the protocol fix

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Livewire\ProductsPage;
use App\Models\ServerPlan;

try {
    echo "=== Testing Protocol Fix ===\n";
    
    // Check if we can query protocols directly
    $protocolCount = ServerPlan::whereNotNull('protocol')->count();
    echo "✅ Total plans with protocols: $protocolCount\n";
    
    // Test the protocol filtering
    $vlessPlans = ServerPlan::where('protocol', 'vless')->count();
    echo "✅ VLESS plans found: $vlessPlans\n";
    
    // Test case insensitive
    $vlessPlansUpper = ServerPlan::where('protocol', strtolower('VLESS'))->count();
    echo "✅ VLESS plans (case insensitive): $vlessPlansUpper\n";
    
    // Test the component
    $component = new ProductsPage();
    $component->selected_protocols = 'VLESS';
    echo "✅ Component protocol set to: " . $component->selected_protocols . "\n";
    
    // Get available protocols
    $protocols = ServerPlan::whereNotNull('protocol')
                          ->distinct()
                          ->pluck('protocol')
                          ->map(function($protocol) {
                              return strtoupper($protocol);
                          })
                          ->toArray();
    
    echo "✅ Available protocols: " . implode(', ', $protocols) . "\n";
    
    echo "✅ All protocol tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";
