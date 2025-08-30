<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\ServerClient;
use App\Models\ClientTraffic;
use Carbon\Carbon;
use App\Services\XUIService;

class SyncXuiClients extends Command
{
    protected $signature = 'xui:sync-clients {--limit=200 : Max clients to sync per run}';
    protected $description = 'Sync active clients from XUI panels (traffic + IPs) and cache results';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Starting XUI sync (limit={$limit})");

        $query = ServerClient::query()
            ->where('enable', true)
            ->where('status', 'active')
            ->orderBy('last_api_sync_at', 'asc')
            ->limit($limit);

        $count = 0;
        foreach ($query->get() as $client) {
            $count++;
            try {
                $server = $client->inbound?->server ?? $client->server ?? null;
                if (!$server) {
                    Log::warning('xui.sync: no server for client', ['client_id' => $client->id]);
                    continue;
                }
                $svc = new XUIService($server);

                // Prefer UUID lookup
                $remote = null;
                if (!empty($client->id)) {
                    $remote = $svc->getClientByUuid($client->id);
                }
                if (empty($remote) && !empty($client->email)) {
                    $remote = $svc->getClientByEmail($client->email);
                }

                if (is_array($remote)) {
                    $client->update([
                        'remote_up' => $remote['up'] ?? $client->remote_up,
                        'remote_down' => $remote['down'] ?? $client->remote_down,
                        'remote_total' => $remote['total'] ?? ($remote['up'] + $remote['down']),
                        'last_api_sync_at' => now(),
                        'api_sync_status' => 'success',
                    ]);

                    $key = "xui_client_traffic_{$server->id}_{$client->id}";
                    Cache::put($key, $remote, 30);

                    // Ensure a ClientTraffic snapshot exists for UI lists
                    try {
                        ClientTraffic::updateOrCreate(
                            ['email' => $client->email, 'server_inbound_id' => $client->server_inbound_id],
                            [
                                'up' => (int) ($remote['up'] ?? 0),
                                'down' => (int) ($remote['down'] ?? 0),
                                'total' => (int) ($remote['total'] ?? (($remote['up'] ?? 0) + ($remote['down'] ?? 0))),
                                'enable' => (bool) ($client->enable ?? true),
                                'customer_id' => $client->customer_id,
                                // store expiry_time as integer milliseconds (matches migration)
                                'expiry_time' => is_numeric($client->expiry_time) ? (int) $client->expiry_time : null,
                            ]
                        );
                    } catch (\Throwable $e) {
                        Log::debug('xui.sync_client_create_traffic_failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
                    }
                } else {
                    $client->update(['api_sync_status' => 'not_found', 'last_api_sync_at' => now()]);
                }

                // Fetch IPs and log the raw response for debugging
                    try {
                        $identifier = $client->id ?: $client->email;
                        $ipsRaw = $svc->getClientIps($identifier);
                        Log::debug('xui.getClientIps', ['server_id' => $server->id, 'client_id' => $client->id, 'identifier' => $identifier, 'ips_raw' => $ipsRaw]);

                        // Normalize and validate IPs (accept IPv4/IPv6 only)
                        $normalized = [];
                        if (is_array($ipsRaw) && count($ipsRaw) > 0) {
                            foreach ($ipsRaw as $item) {
                                // Direct string entry
                                if (is_string($item) && filter_var($item, FILTER_VALIDATE_IP)) {
                                    $normalized[] = $item;
                                    continue;
                                }

                                // If it's an array/object, try common fields
                                if (is_array($item) || is_object($item)) {
                                    $obj = (array) $item;
                                    $candidates = ['ip','address','remote_addr','remoteAddress','host','addr','client_ip'];
                                    foreach ($candidates as $k) {
                                        if (isset($obj[$k]) && filter_var((string)$obj[$k], FILTER_VALIDATE_IP)) {
                                            $normalized[] = (string)$obj[$k];
                                            continue 2;
                                        }
                                    }

                                    // Fallback: search stringified content for an IP pattern
                                    $asString = json_encode($obj);
                                    if (preg_match_all('/((?:\d{1,3}\.){3}\d{1,3})/', $asString, $m)) {
                                        foreach ($m[1] as $ip) {
                                            if (filter_var($ip, FILTER_VALIDATE_IP)) $normalized[] = $ip;
                                        }
                                    }
                                }
                            }
                        }

                        // Dedupe and keep order
                        $normalized = array_values(array_unique($normalized));
                        $ipsText = implode("\n", $normalized);

                        // Create or update local record (allow empty ips to indicate snapshot)
                        \App\Models\InboundClientIP::updateOrCreate(
                            ['client_email' => $client->email],
                            ['ips' => $ipsText]
                        );

                        // Cache live IPs only when present
                        if (!empty($normalized)) {
                            $cacheKey = "xui_client_ips_{$server->id}_{$client->id}";
                            Cache::put($cacheKey, $normalized, 30);
                        }
                    } catch (\Throwable $e) {
                        Log::debug('xui.getClientIps_error', ['server_id' => $server->id, 'client_id' => $client->id, 'error' => $e->getMessage()]);
                        // Ensure there's at least an empty local record so admin can see and edit it
                        try {
                            \App\Models\InboundClientIP::updateOrCreate(['client_email' => $client->email], ['ips' => '']);
                        } catch (\Throwable $_) {
                            // swallow
                        }
                    }

            } catch (\Throwable $e) {
                Log::error('xui.sync_client_failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("XUI sync completed. Processed: {$count}");
        return 0;
    }
}
