<?php
require __DIR__ . '/../vendor/autoload.php';
putenv('APP_ENV=testing');
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::factory()->create(['role'=>'admin','email_verified_at'=>now(),'is_active'=>true]);

// Log in via web guard
Illuminate\Support\Facades\Auth::guard('web')->login($user);

$request = Illuminate\Http\Request::create('/admin', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
$response = $app->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
// Symfony response stores headers in a HeaderBag
foreach ($response->headers->all() as $k => $v) {
    echo $k . ': ' . implode(',', $v) . "\n";
}

$body = $response->getContent();
$snippet = substr($body, 0, 4000);
echo "Body snippet:\n" . $snippet . "\n";

