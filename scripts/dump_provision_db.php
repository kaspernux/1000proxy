<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServerClient;
use App\Models\OrderServerClient;
use Illuminate\Support\Facades\DB;

echo "Recent ServerClient rows:\n";
$sc = ServerClient::orderBy('id', 'desc')->limit(10)->get();
foreach ($sc as $s) {
    echo "id={$s->id} server_id={$s->server_id} order_id={$s->order_id} plan_id={$s->plan_id} created_at={$s->created_at}\n";
}

echo "\nRecent OrderServerClient rows:\n";
$osc = OrderServerClient::orderBy('id', 'desc')->limit(20)->get();
foreach ($osc as $o) {
    echo "id={$o->id} order_id={$o->order_id} order_item_id={$o->order_item_id} server_client_id={$o->server_client_id} dedicated_inbound_id={$o->dedicated_inbound_id} created_at={$o->created_at}\n";
}

// Check for OSCs belonging to order 517
$orderId = 517;
echo "\nOSCs for order {$orderId}:\n";
$found = OrderServerClient::where('order_id', $orderId)->get();
if ($found->isEmpty()) {
    echo "NONE\n";
} else {
    foreach ($found as $f) {
        echo "id={$f->id} server_client_id={$f->server_client_id} order_item_id={$f->order_item_id}\n";
    }
}

// Output latest ServerClient with server_client id referenced in snapshot files (if any)
echo "\n-- Done --\n";
