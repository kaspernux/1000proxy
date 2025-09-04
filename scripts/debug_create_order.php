<?php
// Debug script: simulate authenticated customer POST to /api/orders and print response/exception
require __DIR__ . '/../vendor/autoload.php';
putenv('APP_ENV=testing');
putenv('PROVISION_DEBUG_XUI=1');
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $customer = App\Models\Customer::factory()->create();
    $server = App\Models\Server::factory()->create(['is_active' => true]);
    $plan = App\Models\ServerPlan::factory()->create(['server_id' => $server->id]);

    // ensure wallet has big balance
    $customer->wallet()->update(['balance' => 1000.00]);

    $request = Illuminate\Http\Request::create(
        '/api/orders',
        'POST',
        [
            'server_id' => $server->id,
            'plan_id' => $plan->id,
            'quantity' => 2,
            'duration' => 3,
        ],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            // create a personal access token and set Authorization header
            'HTTP_AUTHORIZATION' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ]
    );

    // set the auth resolver (actingAs equivalent for guard 'customer')
    $request->setUserResolver(function () use ($customer) {
        return $customer;
    });

    $response = $app->handle($request);

    echo "Status: " . $response->getStatusCode() . "\n";
    $content = (string) $response->getContent();
    echo "Content:\n" . substr($content, 0, 10000) . "\n";
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}


