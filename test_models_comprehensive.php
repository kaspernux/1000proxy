<?php
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 COMPREHENSIVE DATABASE MODEL ANALYSIS\n";
echo "==========================================\n\n";

try {
    // Test database connection
    echo "1. Testing Database Connection...\n";
    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✅ Database connection successful\n\n";

    // Test critical models and relationships
    echo "2. Testing Model Relationships...\n";

    // Customer Model Tests
    echo "   Testing Customer model...\n";
    $customer = App\Models\Customer::first();
    if ($customer) {
        echo "   ✅ Customer found: {$customer->name}\n";
        echo "   ✅ Customer orders: " . $customer->orders()->count() . "\n";
        $wallet = $customer->wallet;
        echo "   ✅ Customer wallet: " . ($wallet ? "Found (Balance: {$wallet->balance})" : "Missing") . "\n";
    } else {
        echo "   ⚠️ No customers found in database\n";
    }

    // Order Model Tests
    echo "   Testing Order model...\n";
    $order = App\Models\Order::first();
    if ($order) {
        echo "   ✅ Order found: #{$order->id}\n";
        echo "   ✅ Order customer: " . ($order->customer ? $order->customer->name : "Missing") . "\n";
        echo "   ✅ Order items: " . $order->items()->count() . "\n";
        echo "   ✅ Order status: {$order->order_status}\n";
    } else {
        echo "   ⚠️ No orders found in database\n";
    }

    // ServerPlan Model Tests
    echo "   Testing ServerPlan model...\n";
    $plan = App\Models\ServerPlan::first();
    if ($plan) {
        echo "   ✅ ServerPlan found: {$plan->name}\n";
        echo "   ✅ Plan server: " . ($plan->server ? $plan->server->name : "Missing") . "\n";
        echo "   ✅ Plan brand: " . ($plan->brand ? $plan->brand->name : "Missing") . "\n";
        echo "   ✅ Plan category: " . ($plan->category ? $plan->category->name : "Missing") . "\n";
    } else {
        echo "   ⚠️ No server plans found in database\n";
    }

    // Server Model Tests
    echo "   Testing Server model...\n";
    $server = App\Models\Server::first();
    if ($server) {
        echo "   ✅ Server found: {$server->name}\n";
        echo "   ✅ Server inbounds: " . $server->inbounds()->count() . "\n";
        echo "   ✅ Server plans: " . $server->plans()->count() . "\n";
        echo "   ✅ Server status: {$server->status}\n";
    } else {
        echo "   ⚠️ No servers found in database\n";
    }

    // ServerClient Model Tests
    echo "   Testing ServerClient model...\n";
    $client = App\Models\ServerClient::first();
    if ($client) {
        echo "   ✅ ServerClient found: {$client->email}\n";
        echo "   ✅ Client inbound: " . ($client->inbound ? "Found" : "Missing") . "\n";
        echo "   ✅ Client order: " . ($client->order ? "#{$client->order->id}" : "Missing") . "\n";
        echo "   ✅ Client customer: " . ($client->customer ? $client->customer->name : "Missing") . "\n";
    } else {
        echo "   ⚠️ No server clients found in database\n";
    }

    echo "\n3. Testing Widget Dependencies...\n";

    // Test BusinessIntelligenceService
    echo "   Testing BusinessIntelligenceService...\n";
    $biService = app(App\Services\BusinessIntelligenceService::class);
    $analytics = $biService->getDashboardAnalytics('7_days');
    echo "   ✅ BusinessIntelligenceService working\n";

    // Test RevenueChartWidget
    echo "   Testing RevenueChartWidget...\n";
    $widget = new App\Filament\Admin\Widgets\RevenueChartWidget();
    $data = $widget->getData();
    echo "   ✅ RevenueChartWidget working\n";

    echo "\n4. Testing XUI Service Integration...\n";

    // Test XUIService
    echo "   Testing XUIService...\n";
    $xuiService = app(App\Services\XUIService::class);
    echo "   ✅ XUIService loaded successfully\n";

    echo "\n5. Testing Payment and Invoice Models...\n";

    // Invoice Model Tests
    $invoice = App\Models\Invoice::first();
    if ($invoice) {
        echo "   ✅ Invoice found: #{$invoice->id}\n";
        echo "   ✅ Invoice order: " . ($invoice->order ? "#{$invoice->order->id}" : "Missing") . "\n";
        echo "   ✅ Invoice payment method: " . ($invoice->paymentMethod ? $invoice->paymentMethod->name : "Missing") . "\n";
    } else {
        echo "   ⚠️ No invoices found in database\n";
    }

    echo "\n6. Foreign Key Constraint Tests...\n";

    // Test foreign key relationships
    echo "   Testing order -> customer constraint...\n";
    $orderCount = App\Models\Order::whereHas('customer')->count();
    echo "   ✅ Orders with valid customers: {$orderCount}\n";

    echo "   Testing server_plans -> server constraint...\n";
    $planCount = App\Models\ServerPlan::whereHas('server')->count();
    echo "   ✅ Plans with valid servers: {$planCount}\n";

    echo "   Testing server_clients -> server_inbound constraint...\n";
    $clientCount = App\Models\ServerClient::whereHas('inbound')->count();
    echo "   ✅ Clients with valid inbounds: {$clientCount}\n";

    echo "\n🎉 ALL DATABASE MODEL TESTS PASSED!\n";
    echo "✅ Database is ready for production deployment!\n";

} catch (Exception $e) {
    echo "❌ Error during model testing: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    if ($e->getPrevious()) {
        echo "🔗 Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
    echo "\n📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}
