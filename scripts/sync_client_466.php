<?php
// One-shot sync for order 466 — creates/updates a ClientTraffic snapshot using XUIService
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $client = \App\Models\ServerClient::where('order_id', 466)->first();
    if (!$client) {
        echo "NO_CLIENT_466\n";
        exit(1);
    }

    $server = $client->inbound?->server ?? $client->server ?? null;
    if (!$server) {
        echo "NO_SERVER_FOR_CLIENT\n";
        exit(2);
    }

    $svc = new \App\Services\XUIService($server);
    $remote = null;
    if (!empty($client->id)) {
        $remote = $svc->getClientByUuid($client->id);
    }
    if (empty($remote) && !empty($client->email)) {
        $remote = $svc->getClientByEmail($client->email);
    }

    echo json_encode(['found_remote' => (bool)$remote, 'client_id' => $client->id, 'email' => $client->email]) . "\n";

    if ($remote && is_array($remote)) {
        $key = "xui_client_traffic_" . ($server->id ?? 'unknown') . "_" . ($client->id ?? $client->email);
        \Illuminate\Support\Facades\Cache::put($key, $remote, 30);

        \App\Models\ClientTraffic::updateOrCreate(
            ['email' => $client->email, 'server_inbound_id' => $client->server_inbound_id],
            [
                'up' => (int)($remote['up'] ?? 0),
                'down' => (int)($remote['down'] ?? 0),
                'total' => (int)($remote['total'] ?? (($remote['up'] ?? 0) + ($remote['down'] ?? 0))),
                'enable' => (bool)($client->enable ?? true),
                'customer_id' => $client->customer_id,
                // store expiry_time as integer milliseconds (matches migration schema)
                'expiry_time' => is_numeric($client->expiry_time) ? (int) $client->expiry_time : null,
            ]
        );

        echo "TRAFFIC_UPDATED\n";
    } else {
        echo "REMOTE_NOT_FOUND\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(3);
}
