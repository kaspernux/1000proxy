<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;
use App\Services\ServerPlanProvisioningService;

class ProvisionServerPlans extends Command
{
    protected $signature = 'plans:provision {server_id} {template=chicago} {--brand_id=}';
    protected $description = 'Provision plans for a server using a named template from config/plan_templates.php';

    protected ServerPlanProvisioningService $service;

    public function __construct(ServerPlanProvisioningService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $serverId = $this->argument('server_id');
        $template = $this->argument('template') ?? 'chicago';

        /** @var Server|null $server */
        $server = Server::find($serverId);

        if (!$server) {
            $this->error('Server not found: ' . $serverId);
            return 1;
        }

        $templates = config('plan_templates.' . $template);
        if (!is_array($templates) || empty($templates)) {
            $this->error('Template not found or empty: ' . $template);
            return 1;
        }

        $options = [];
        if ($this->option('brand_id')) { $options['brand_id'] = (int) $this->option('brand_id'); }

        $this->info("Provisioning " . count($templates) . " plans for server {$server->name} (id={$server->id}) using template '{$template}'...");

        $results = $this->service->createPlansForServer($server, $templates, $options);

        foreach ($results as $r) {
            $this->line("- {$r['slug']} : {$r['status']}" . (isset($r['message']) ? " ({$r['message']})" : ''));
        }

        $this->info('Done.');
        return 0;
    }
}
