<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServerClient;
use App\Services\XUIService;

$orderId = 466;
$client = ServerClient::where('order_id', $orderId)->first();

$output = ['timestamp' => date('c'), 'order_id' => $orderId];
if (!$client) {
    $output['error'] = "ServerClient not found for order_id {$orderId}";
    echo json_encode($output, JSON_PRETTY_PRINT);
    exit(0);
}

$output['client'] = [
    'id' => $client->id,
    'email' => $client->email,
    'server_inbound_id' => $client->server_inbound_id,
    'server_id' => $client->server_id,
    'remote_up' => $client->remote_up,
    'remote_down' => $client->remote_down,
    'api_sync_status' => $client->api_sync_status,
];

$inbound = $client->inbound;
if ($inbound) {
    $output['inbound'] = [
        'id' => $inbound->id,
        'server_id' => $inbound->server_id,
        'remark' => $inbound->remark,
        'port' => $inbound->port,
    ];
} else {
    $output['inbound'] = null;
}

$server = $inbound?->server ?? $client->server ?? null;
if ($server) {
    $output['server'] = [
        'id' => $server->id,
        'name' => $server->name ?? null,
        'panel_url' => $server->panel_url ?? null,
        'username' => (bool)($server->username ?? null) ? 'SET' : 'MISSING',
        'password' => (bool)($server->password ?? null) ? 'SET' : 'MISSING',
    ];
} else {
    $output['server'] = null;
}

if ($server) {
    try {
        $svc = new XUIService($server);
    } catch (Throwable $e) {
        $output['xui_service_error'] = $e->getMessage();
        echo json_encode($output, JSON_PRETTY_PRINT);
        exit(0);
    }

    // Try UUID lookup
    try {
        $byUuid = $svc->getClientByUuid((string)$client->id);
        $output['xui_by_uuid'] = $byUuid ?: null;
    } catch (Throwable $e) {
        $output['xui_by_uuid_error'] = $e->getMessage();
    }

    // Try email lookup
    try {
        $byEmail = $svc->getClientByEmail((string)$client->email);
        $output['xui_by_email'] = $byEmail ?: null;
    } catch (Throwable $e) {
        $output['xui_by_email_error'] = $e->getMessage();
    }

    // Try getClientIps by id and by email
    try {
        $ipsById = $svc->getClientIps((string)$client->id);
        $output['ips_by_id'] = is_array($ipsById) ? $ipsById : null;
    } catch (Throwable $e) {
        $output['ips_by_id_error'] = $e->getMessage();
    }
    try {
        $ipsByEmail = $svc->getClientIps((string)$client->email);
        $output['ips_by_email'] = is_array($ipsByEmail) ? $ipsByEmail : null;
    } catch (Throwable $e) {
        $output['ips_by_email_error'] = $e->getMessage();
    }

    // Try a basic authenticated request to list inbounds (sanity)
    try {
        $inbounds = $svc->makeAuthenticatedRequest('GET', 'panel/api/inbounds/list');
        $output['xui_inbounds_list_keys'] = is_array($inbounds) ? array_keys($inbounds) : null;
    } catch (Throwable $e) {
        $output['xui_inbounds_list_error'] = $e->getMessage();
    }
}

echo json_encode($output, JSON_PRETTY_PRINT);
