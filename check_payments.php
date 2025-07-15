<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check payment methods
    $paymentMethods = App\Models\PaymentMethod::all(['id', 'name', 'slug', 'type', 'is_active']);
    echo 'Payment methods in database: ' . $paymentMethods->count() . PHP_EOL;

    if ($paymentMethods->count() > 0) {
        echo "Available payment methods:" . PHP_EOL;
        foreach($paymentMethods as $method) {
            $status = $method->is_active ? '✅' : '❌';
            echo "{$status} {$method->name} ({$method->slug}) - Type: {$method->type}" . PHP_EOL;
        }
    } else {
        echo "No payment methods found in database" . PHP_EOL;
    }

    echo PHP_EOL;

    // Check recent orders
    $orders = App\Models\Order::latest()->take(3)->get(['id', 'payment_status', 'payment_method', 'grand_amount']);
    echo 'Recent orders: ' . $orders->count() . PHP_EOL;

    if ($orders->count() > 0) {
        echo "Sample orders:" . PHP_EOL;
        foreach($orders as $order) {
            echo "Order #{$order->id}: {$order->payment_status}, {$order->payment_method}, \${$order->grand_amount}" . PHP_EOL;
        }
    } else {
        echo "No orders found in database" . PHP_EOL;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
