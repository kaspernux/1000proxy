<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\CustomerRegistrationController;
use Illuminate\Support\Facades\Log;

// Unique email
$email = 'sim+' . time() . '@example.com';

// Build a Request instance similar to a POST form
$request = Request::create('/register', 'POST', [
    'name' => 'Simulated Test',
    'email' => $email,
    'password' => 'TestPassword123!',
    'password_confirmation' => 'TestPassword123!',
    'terms_accepted' => '1',
]);

// Instantiate controller and call store
$controller = new CustomerRegistrationController();

try {
    $response = $controller->store($request);

    if ($response instanceof Illuminate\Http\RedirectResponse) {
        echo "Redirected to: " . $response->getTargetUrl() . "\n";
        // Inspect session flash
        if (session()->has('status')) {
            echo "Status flash: " . session('status') . "\n";
        }
        if (session()->has('errors')) {
            echo "Errors present in session.\n";
            var_dump(session('errors')->getMessages());
        }
    } else {
        echo "Response class: " . get_class($response) . "\n";
        echo (string) $response->getContent() . "\n";
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

return 0;
