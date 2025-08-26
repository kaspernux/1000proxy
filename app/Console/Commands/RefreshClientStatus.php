<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerClient;
use App\Services\XUIService;
use App\Services\CacheService;

class RefreshClientStatus extends Command
{
    protected $signature = 'clients:refresh-status {--customer=* : Only refresh for given customer IDs} {--limit=500 : Max clients to process per run}';
    protected $description = 'Refresh is_online flags and live traffic caches for active clients.';

    public function handle(): int
    {
        $customerFilter = collect((array) $this->option('customer'))->filter()->map(fn($v) => (int) $v)->values();
        $limit = (int) $this->option('limit') ?: 500;

        $query = ServerClient::query()
            ->whereIn('status', ['active', 'up'])
            ->with(['server', 'serverInbound.server'])
            ->orderByDesc('updated_at');

        if ($customerFilter->isNotEmpty()) {
            $query->whereIn('customer_id', $customerFilter);
        }

        $clients = $query->take($limit)->get();
        if ($clients->isEmpty()) {
            $this->info('No clients to refresh.');
            return self::SUCCESS;
        }

        $byServer = $clients->groupBy(function (ServerClient $c) {
            return $c->server_id ?: optional($c->serverInbound)->server_id;
        });

        $cache = app(CacheService::class);
        $updated = 0;

        foreach ($byServer as $serverId => $group) {
            $server = optional($group->first())->server ?: optional($group->first()->serverInbound)->server;
            if (!$server) { continue; }

            $xui = app()->makeWith(XUIService::class, ['server' => $server]);

            // Fetch online clients once per server
            $onlines = [];
            try {
                $resp = $xui->getOnlineClients();
                if (is_array($resp)) {
                    $onlines = $resp['obj'] ?? $resp['online'] ?? $resp;
                }
            } catch (\Throwable $e) {
                // ignore
            }
            $onlineEmails = collect($onlines)->filter()->values();

            foreach ($group as $client) {
                // Update is_online based on email appearances
                try {
                    if ($onlineEmails->isNotEmpty()) {
                        $isOnline = $onlineEmails->contains($client->email);
                        if ($client->is_online !== $isOnline) {
                            $client->is_online = $isOnline;
                            $client->saveQuietly();
                            $updated++;
                        }
                    }
                } catch (\Throwable $e) { /* ignore */ }

                // Prime live traffic cache (prefer UUID, fallback email)
                try {
                    $remote = $xui->getClientByUuid($client->id);
                } catch (\Throwable $e) { $remote = null; }
                if (!$remote && $client->email) {
                    try { $remote = $xui->getClientByEmail($client->email); } catch (\Throwable $e) { /* ignore */ }
                }
                if (is_array($remote)) {
                    $up = (int) ($remote['up'] ?? 0);
                    $down = (int) ($remote['down'] ?? 0);
                    $total = (int) ($remote['total'] ?? ($up + $down));
                    $cache->cacheClientTraffic($client->id, ['up' => $up, 'down' => $down, 'total' => $total]);
                }
            }
        }

        $this->info("Refreshed status/traffic for {$clients->count()} clients; updated={$updated}.");
        return self::SUCCESS;
    }
}
