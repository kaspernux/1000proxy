<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$id = '999be034-88ee-4843-aaad-e7a7d4350d2e';
$osc = App\Models\OrderServerClient::where('server_client_id', $id)->first();
echo ($osc ? json_encode($osc->toArray(), JSON_PRETTY_PRINT) : "NOOSC\n");
