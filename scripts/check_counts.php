<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\ServerClient;
use App\Models\OrderServerClient;
echo 'ServerClient: ' . ServerClient::count() . PHP_EOL;
echo 'OrderServerClient: ' . OrderServerClient::count() . PHP_EOL;
