<?php
// One-shot: fetch client IPs from XUI for order 466 and create/update InboundClientIP record
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

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
$identifier = $client->id ?: $client->email;
try {
    $ips = $svc->getClientIps($identifier);
    echo "IPS_RAW:" . json_encode($ips) . "\n";
    $ipsText = '';
    if (is_array($ips) && count($ips) > 0) {
        $ipsText = implode("\n", array_map('strval', $ips));
    }
    \App\Models\InboundClientIP::updateOrCreate(['client_email' => $client->email], ['ips' => $ipsText]);
    echo "INBOUND_IP_UPDATED\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(3);
}
