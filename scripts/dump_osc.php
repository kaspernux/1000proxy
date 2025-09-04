<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\OrderServerClient;
foreach (OrderServerClient::orderBy('id','desc')->take(20)->get() as $o) {
    echo json_encode([
        'id' => $o->id,
        'order_id' => $o->order_id,
        'order_item_id' => $o->order_item_id,
        'server_inbound_id' => $o->server_inbound_id,
        'dedicated_inbound_id' => $o->dedicated_inbound_id,
        'server_client_id' => $o->server_client_id,
        'created_at' => (string) $o->created_at,
    ], JSON_PRETTY_PRINT) . PHP_EOL;
}
