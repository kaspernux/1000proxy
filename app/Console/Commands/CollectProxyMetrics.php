<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerClient;
use App\Models\ClientMetric;
use App\Models\Server;
use App\Services\XUIService;
use Illuminate\Support\Facades\Log;

class CollectProxyMetrics extends Command
{
    protected $signature = 'metrics:collect {--limit=500 : Max clients per run}';
    protected $description = 'Collect online/latency/traffic metrics for active proxy clients';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $count = 0; $saved = 0;

        ServerClient::with(['server'])
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->chunkById(100, function ($clients) use (&$count, &$saved) {
                foreach ($clients as $client) {
                    $count++;
                    try {
                        if (!$client->server) {
                            continue;
                        }
                        $server = $client->server;
                        // Prefer existing flags if present
                        $isOnline = is_bool($client->is_online) ? (bool) $client->is_online : null;
                        $latency = null;
                        if ($isOnline === null) {
                            $isOnline = $this->testConnection($server);
                        }
                        if ($isOnline) {
                            $latency = $this->measureLatency($server);
                        } else {
                            $latency = 0;
                        }

                        // Traffic total bytes if available
                        $totalBytes = null;
                        if (is_numeric($client->remote_up) || is_numeric($client->remote_down)) {
                            $totalBytes = (int) ($client->remote_up + $client->remote_down);
                        }

                        ClientMetric::create([
                            'server_client_id' => $client->id,
                            'customer_id' => $client->customer_id,
                            'server_id' => $client->server_id,
                            'is_online' => (bool) $isOnline,
                            'latency_ms' => $latency,
                            'total_bytes' => $totalBytes,
                            'measured_at' => now(),
                        ]);
                        $saved++;
                    } catch (\Throwable $e) {
                        Log::warning('Metric collection error', [
                            'client' => $client->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }, 'id');

        $this->info("Collected metrics for {$saved}/{$count} clients");
        return self::SUCCESS;
    }

    private function testConnection(Server $server): bool
    {
        try {
            $svc = new XUIService($server);
            return $svc->testConnection();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function measureLatency(Server $server): int
    {
        $host = $server->host ?: ($server->ip ?: '127.0.0.1');
        $port = (int) ($server->port ?: ($server->panel_port ?: 443));
        $timeout = 1.0;
        $start = microtime(true);
        try {
            $errno = 0; $err = '';
            $conn = @fsockopen($host, $port, $errno, $err, $timeout);
            if ($conn) {
                fclose($conn);
                $ms = (int) round((microtime(true) - $start) * 1000);
                return max(1, min($ms, 2000));
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 0;
    }
}
