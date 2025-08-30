<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServerClient;
use App\Services\XUIService;
use Illuminate\Support\Facades\Cache;

$orderId = 466;
$client = ServerClient::where('order_id', $orderId)->first();
if (!$client) {
    echo "Client not found for order_id {$orderId}\n";
    exit(1);
}

$server = $client->inbound?->server ?? $client->server ?? null;
if (!$server) {
    echo "Server not found for client\n";
    exit(1);
}

$svc = new XUIService($server);
$remote = $svc->getClientByUuid($client->id) ?: $svc->getClientByEmail($client->email);
if (!$remote) {
    echo "No remote data returned\n";
    exit(1);
}

$client->update([
    'remote_up' => $remote['up'] ?? $client->remote_up,
    'remote_down' => $remote['down'] ?? $client->remote_down,
    'remote_total' => $remote['total'] ?? ($remote['up'] + $remote['down']),
    'last_api_sync_at' => now(),
    'api_sync_status' => 'success',
]);

$key = "xui_client_traffic_{$server->id}_{$client->id}";
Cache::put($key, $remote, 30);

echo json_encode(['updated' => true, 'client_id' => $client->id, 'cache_key' => $key, 'remote' => $remote], JSON_PRETTY_PRINT) . "\n";
