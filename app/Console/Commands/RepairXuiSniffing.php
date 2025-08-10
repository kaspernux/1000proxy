<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Server};
use App\Services\XUIService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RepairXuiSniffing extends Command
{
    protected $signature = 'xui:repair-sniffing {server : Server ID} {--insecure : Disable TLS verification}';
    protected $description = 'Repair inbounds on a server whose sniffing config was stored as an array instead of object (prevents xray-core start).';

    public function handle(): int
    {
        $server = Server::find($this->argument('server'));
        if (!$server) { $this->error('Server not found'); return 1; }
        if ($this->option('insecure')) { app()->instance('xui.insecure', true); }

        $xui = new XUIService($server);
        if (!$xui->testConnection()) { $this->error('Login failed'); return 1; }

        $this->info('Fetching remote inbounds...');
        $inbounds = $xui->listInbounds();
        $fixed = 0; $skipped = 0; $errors = 0;
        foreach ($inbounds as $remote) {
            $id = $remote['id'] ?? null;
            if (!$id) { continue; }
            $sniffRaw = $remote['sniffing'] ?? null;
            $decoded = null;
            if (is_string($sniffRaw)) { $decoded = json_decode($sniffRaw, true); }
            elseif (is_array($sniffRaw)) { $decoded = $sniffRaw; }
            if ($decoded === null) { $skipped++; continue; }
            $isAssoc = is_array($decoded) && Arr::isAssoc($decoded);
            if (!$isAssoc) {
                $new = [
                    'enabled' => false,
                    'destOverride' => ['http','tls','quic','fakedns'],
                    'metadataOnly' => false,
                    'routeOnly' => false,
                ];
                try {
                    $payload = [
                        'up' => $remote['up'] ?? 0,
                        'down' => $remote['down'] ?? 0,
                        'total' => $remote['total'] ?? 0,
                        'remark' => $remote['remark'] ?? '',
                        'enable' => $remote['enable'] ?? true,
                        'expiryTime' => $remote['expiryTime'] ?? 0,
                        'listen' => $remote['listen'] ?? '',
                        'port' => $remote['port'],
                        'protocol' => $remote['protocol'],
                        'settings' => $remote['settings'],
                        'streamSettings' => $remote['streamSettings'],
                        'tag' => $remote['tag'] ?? ('inbound-' . $remote['port']),
                        'sniffing' => json_encode($new),
                        'allocate' => $remote['allocate'] ?? json_encode(['strategy'=>'always','refresh'=>5,'concurrency'=>3]),
                    ];
                    $xui->updateInbound((int)$id, $payload);
                    $fixed++;
                    $this->line("Fixed sniffing for inbound #{$id} (port {$remote['port']})");
                } catch (\Throwable $e) {
                    $errors++;
                    Log::warning('Failed repairing sniffing', ['inbound_id'=>$id,'error'=>$e->getMessage()]);
                }
            } else {
                $skipped++;
            }
        }
        $this->table(['metric','value'], [
            ['fixed',$fixed],
            ['skipped',$skipped],
            ['errors',$errors],
        ]);
        $this->info('Done.');
        return 0;
    }
}
