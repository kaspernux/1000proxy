<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;

class XuiConfigurePanel extends Command
{
    protected $signature = 'xui:configure {serverId}
        {--host= : Panel host, e.g., amsterdam.1000proxy.me}
        {--port= : Panel port, e.g., 1111}
        {--path= : Web base path (no leading slash), e.g., proxy}
        {--scheme= : http or https (default: derive from panel_url or https)}';

    protected $description = 'Configure 3X-UI panel connection details (host/port/web base path) and normalize panel_url';

    public function handle(): int
    {
        $id = (int) $this->argument('serverId');
        $server = Server::find($id);
        if (!$server) {
            $this->error("Server #{$id} not found");
            return self::FAILURE;
        }

        $host = $this->option('host');
        $port = $this->option('port');
        $path = $this->option('path');
        $scheme = $this->option('scheme');

        // Derive current scheme if not provided
        if (!$scheme) {
            $parsed = parse_url($server->panel_url ?? '');
            $scheme = $parsed['scheme'] ?? 'https';
        }

        $updates = [];
        if ($host !== null) {
            $updates['host'] = preg_replace('#^https?://#i', '', trim($host));
        }
        if ($port !== null) {
            $updates['panel_port'] = (int) $port;
        }
        if ($path !== null) {
            $updates['web_base_path'] = trim($path, '/');
        }

        if (!empty($updates)) {
            $server->fill($updates);
        }

        // Build normalized panel_url: scheme://host:port/path
        $finalHost = $server->getPanelHost();
        $finalPort = $server->getPanelPort();
        $finalPath = ltrim($server->getPanelWebPath(), '/');
        $panelUrl = $scheme . '://' . $finalHost . ':' . $finalPort . ($finalPath ? '/' . $finalPath : '');

        $server->panel_url = $panelUrl;
        $server->save();

        $this->info('Updated panel configuration:');
        $this->line('  Host:   ' . $finalHost);
        $this->line('  Port:   ' . $finalPort);
        $this->line('  Path:   ' . ($finalPath ?: '(none)'));
        $this->line('  URL:    ' . $panelUrl);

        return self::SUCCESS;
    }
}
