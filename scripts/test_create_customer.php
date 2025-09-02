<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the application kernel so Eloquent, config, and services are available
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Str;

$email = 'test+' . time() . '@example.com';

try {
    $customer = Customer::create([
        'name' => 'Automated Test User',
        'email' => $email,
        'password' => 'TestPassword123!',
        'is_active' => true,
    ]);

    echo "CREATED: id={$customer->id} email={$customer->email}\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

return 0;
