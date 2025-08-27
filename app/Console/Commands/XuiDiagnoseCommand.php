<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;
use App\Services\XUIService;

class XuiDiagnoseCommand extends Command
{
    protected $signature = 'xui:diagnose {serverId?}';
    protected $description = 'Diagnose 3X-UI connectivity and authentication for one or all servers';

    public function handle(): int
    {
        $serverId = $this->argument('serverId');
        $query = Server::query();

        if ($serverId) {
            $query->where('id', (int) $serverId);
        } else {
            $query->whereNotNull('panel_url')->orWhereNotNull('host');
        }

        $servers = $query->get();
        if ($servers->isEmpty()) {
            $this->warn('No servers found with panel configuration.');
            return self::SUCCESS;
        }

        foreach ($servers as $server) {
            $this->line(str_repeat('-', 60));
            $this->info("Server #{$server->id}: {$server->name}");
            // Base URL: scheme + host only
            $panelUrl = $server->getPanelBase();
            $parsed = parse_url($panelUrl);
            $scheme = $parsed['scheme'] ?? 'http';
            $hostOnly = $server->getPanelHost() ?: ($parsed['host'] ?? '');
            $this->line('Base URL: ' . ($hostOnly ? $scheme . '://' . $hostOnly : '(unknown)'));
            // Panel URL: scheme://host:port/web_base_path
            $this->line('Panel URL: ' . $panelUrl);
            $this->line('Host: ' . ($server->host ?: '(none)') . '  Port: ' . ($server->panel_port ?: '(n/a)'));
            $this->line('Web base path: ' . ($server->web_base_path ?: '(none)'));

            $svc = new XUIService($server);

            // Try login
            $this->comment('Attempting login…');
            $ok = $svc->login();
            $this->line('Login: ' . ($ok ? '<info>OK</info>' : '<error>FAILED</error>'));
            $this->line('Session cookie name: ' . ($server->session_cookie_name ?: '(unknown)'));
            $this->line('Session valid: ' . ($server->hasValidSession() ? 'yes' : 'no'));

            // Try list inbounds
            try {
                $list = $svc->listInbounds();
                $count = is_countable($list) ? count($list) : 0;
                $this->info("Inbounds list: OK ({$count} items)");
            } catch (\Throwable $e) {
                $this->error('Inbounds list failed: ' . $e->getMessage());
            }

            // Use Symfony's built-in verbosity (-v / -vv / -vvv)
            if ($this->output->isVerbose()) {
                $this->line('Capabilities: ' . json_encode($server->api_capabilities ?? []));
                $this->line('Cookie value (truncated): ' . ($server->session_cookie ? substr($server->session_cookie, 0, 24) . '…' : '(none)'));
            }
        }

        return self::SUCCESS;
    }
}
