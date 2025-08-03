<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "Testing login functionality...\n";

// Get the demo customer
$customer = Customer::where('email', 'demo@1000proxy.io')->first();

if (!$customer) {
    echo "Demo customer not found. Creating one...\n";
    $customer = Customer::create([
        'name' => 'Demo User',
        'email' => 'demo@1000proxy.io',
        'password' => Hash::make('123456789'),
        'is_active' => 1,
        'email_verified_at' => now()
    ]);
    echo "Demo customer created.\n";
} else {
    echo "Demo customer found: {$customer->email}\n";
    echo "Updating password...\n";
    $customer->password = Hash::make('123456789');
    $customer->is_active = 1;
    $customer->save();
    echo "Password updated.\n";
}

// Test authentication
echo "Testing authentication...\n";
$credentials = ['email' => 'demo@1000proxy.io', 'password' => '123456789'];

if (Auth::guard('customer')->attempt($credentials)) {
    echo "✅ Authentication successful!\n";
    $user = Auth::guard('customer')->user();
    echo "Logged in as: {$user->name} ({$user->email})\n";
} else {
    echo "❌ Authentication failed!\n";
    
    // Check if password verification works directly
    if (Hash::check('123456789', $customer->password)) {
        echo "✅ Password hash verification works\n";
    } else {
        echo "❌ Password hash verification failed\n";
    }
    
    // Check if customer is active
    echo "Customer active status: " . ($customer->is_active ? 'Yes' : 'No') . "\n";
}

echo "Test completed.\n";
