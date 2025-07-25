<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ServerManagementService;
use App\Models\Server;

class ServerManagementCommand extends Command
{
    protected $signature = 'server:manage
                            {action : The action to perform (health-check|provision|monitor|configure)}
                            {--server-id= : Specific server ID for actions}
                            {--all : Apply action to all servers}
                            {--name= : Server name for provisioning}
                            {--country= : Country for provisioning}
                            {--city= : City for provisioning}
                            {--ip= : IP address for provisioning}
                            {--panel-url= : X-UI panel URL for provisioning}
                            {--username= : Panel username for provisioning}
                            {--password= : Panel password for provisioning}
                            {--max-clients= : Maximum clients for provisioning}
                            {--bandwidth-limit= : Bandwidth limit in GB for provisioning}';

    protected $description = 'Manage server operations including health checks, provisioning, monitoring, and configuration';

    protected ServerManagementService $serverManagementService;

    public function __construct(ServerManagementService $serverManagementService)
    {
        parent::__construct();
        $this->serverManagementService = $serverManagementService;
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'health-check':
                return $this->handleHealthCheck();

            case 'provision':
                return $this->handleProvisioning();

            case 'monitor':
                return $this->handleMonitoring();

            case 'configure':
                return $this->handleConfiguration();

            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: health-check, provision, monitor, configure');
                return 1;
        }
    }

    protected function handleHealthCheck(): int
    {
        $this->info('🔍 Running server health checks...');

        if ($this->option('all')) {
            // Bulk health check
            $results = $this->serverManagementService->performBulkHealthCheck();

            $this->info("✅ Health check completed!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Servers', $results['total_servers']],
                    ['Healthy Servers', $results['healthy_servers']],
                    ['Unhealthy Servers', $results['unhealthy_servers']],
                    ['Errors', count($results['errors'])]
                ]
            );

            if (!empty($results['server_details'])) {
                $this->newLine();
                $this->info('📊 Detailed Server Status:');

                $tableData = [];
                foreach ($results['server_details'] as $server) {
                    $tableData[] = [
                        $server['name'],
                        $server['location'],
                        $server['status'],
                        $server['response_time'] . 'ms',
                        number_format($server['uptime_percentage'], 1) . '%',
                        $server['active_clients'],
                        empty($server['issues']) ? 'None' : implode(', ', $server['issues'])
                    ];
                }

                $this->table(
                    ['Server', 'Location', 'Status', 'Response Time', 'Uptime', 'Clients', 'Issues'],
                    $tableData
                );
            }

            if (!empty($results['errors'])) {
                $this->newLine();
                $this->error('❌ Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->error("Server {$error['server_id']}: {$error['error']}");
                }
            }

            return $results['unhealthy_servers'] > 0 ? 1 : 0;

        } elseif ($serverId = $this->option('server-id')) {
            // Single server health check
            $server = Server::findOrFail($serverId);

            $result = $this->serverManagementService->checkServerHealth($server);

            $this->info("🏥 Health check for server: {$server->name}");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Status', $result['status']],
                    ['Response Time', $result['response_time'] . 'ms'],
                    ['Uptime', number_format($result['uptime_percentage'], 1) . '%'],
                    ['Active Clients', $result['active_clients']],
                    ['Bandwidth Usage', number_format($result['bandwidth_usage'], 2) . ' GB'],
                    ['CPU Usage', $result['cpu_usage'] . '%'],
                    ['Memory Usage', $result['memory_usage'] . '%'],
                    ['Disk Usage', $result['disk_usage'] . '%']
                ]
            );

            if (!empty($result['issues'])) {
                $this->newLine();
                $this->warn('⚠️  Issues detected:');
                foreach ($result['issues'] as $issue) {
                    $this->warn("  • {$issue}");
                }
                return 1;
            }

            $this->info('✅ Server is healthy!');
            return 0;

        } else {
            $this->error('Please specify either --all or --server-id=X');
            return 1;
        }
    }

    protected function handleProvisioning(): int
    {
        $this->info('🚀 Provisioning new server...');

        // Collect provisioning data
        $provisioningData = [
            'name' => $this->option('name') ?? $this->ask('Server name'),
            'country' => $this->option('country') ?? $this->ask('Country'),
            'city' => $this->option('city') ?? $this->ask('City'),
            'ip_address' => $this->option('ip') ?? $this->ask('IP address'),
            'panel_url' => $this->option('panel-url') ?? $this->ask('X-UI panel URL'),
            'panel_username' => $this->option('username') ?? $this->ask('Panel username'),
            'panel_password' => $this->option('password') ?? $this->secret('Panel password'),
            'max_clients' => $this->option('max-clients') ?? $this->ask('Maximum clients', 1000),
            'bandwidth_limit_gb' => $this->option('bandwidth-limit') ?? $this->ask('Bandwidth limit (GB)', 1000),
        ];

        // Validate required fields
        $requiredFields = ['name', 'country', 'city', 'ip_address', 'panel_url', 'panel_username', 'panel_password'];
        foreach ($requiredFields as $field) {
            if (empty($provisioningData[$field])) {
                $this->error("Field '{$field}' is required");
                return 1;
            }
        }

        $this->info('📋 Provisioning Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Name', $provisioningData['name']],
                ['Location', "{$provisioningData['city']}, {$provisioningData['country']}"],
                ['IP Address', $provisioningData['ip_address']],
                ['Panel URL', $provisioningData['panel_url']],
                ['Username', $provisioningData['panel_username']],
                ['Max Clients', $provisioningData['max_clients']],
                ['Bandwidth Limit', $provisioningData['bandwidth_limit_gb'] . ' GB'],
            ]
        );

        if (!$this->confirm('Proceed with provisioning?')) {
            $this->info('Provisioning cancelled');
            return 0;
        }

        $result = $this->serverManagementService->provisionNewServer($provisioningData);

        if ($result['success']) {
            $this->info('✅ Server provisioned successfully!');
            $this->info("Server ID: {$result['server']->id}");
            $this->info("Health Status: {$result['health_status']['status']}");

            if (!empty($result['configuration_result']['steps'])) {
                $this->newLine();
                $this->info('🔧 Configuration Steps:');
                foreach ($result['configuration_result']['steps'] as $step => $completed) {
                    $status = $completed ? '✅' : '❌';
                    $this->line("  {$status} " . str_replace('_', ' ', ucfirst($step)));
                }
            }

            return 0;
        } else {
            $this->error('❌ Server provisioning failed!');
            $this->error($result['error']);
            return 1;
        }
    }

    protected function handleMonitoring(): int
    {
        $this->info('📊 Monitoring server performance...');

        if ($serverId = $this->option('server-id')) {
            $server = Server::findOrFail($serverId);

            $result = $this->serverManagementService->monitorServerPerformance($server);

            if ($result['success']) {
                $metrics = $result['metrics'];

                $this->info("📈 Performance metrics for: {$server->name}");
                $this->table(
                    ['Metric', 'Value', 'Status'],
                    [
                        ['CPU Usage', $metrics['cpu_usage'] . '%', $metrics['cpu_usage'] > 80 ? '⚠️' : '✅'],
                        ['Memory Usage', $metrics['memory_usage'] . '%', $metrics['memory_usage'] > 85 ? '⚠️' : '✅'],
                        ['Disk Usage', $metrics['disk_usage'] . '%', $metrics['disk_usage'] > 90 ? '⚠️' : '✅'],
                        ['Bandwidth Usage', number_format($metrics['bandwidth_usage_gb'], 2) . ' GB (' . number_format($metrics['bandwidth_percentage'], 1) . '%)', $metrics['bandwidth_percentage'] > 80 ? '⚠️' : '✅'],
                        ['Active Clients', $metrics['active_clients'] . ' (' . number_format($metrics['client_percentage'], 1) . '%)', $metrics['client_percentage'] > 90 ? '⚠️' : '✅'],
                        ['Response Time', $metrics['response_time_ms'] . 'ms', $metrics['response_time_ms'] > 1000 ? '⚠️' : '✅'],
                        ['Uptime', number_format($metrics['uptime_percentage'], 2) . '%', $metrics['uptime_percentage'] < 99 ? '⚠️' : '✅'],
                    ]
                );

                if (!empty($result['alerts'])) {
                    $this->newLine();
                    $this->warn('🚨 Performance Alerts:');
                    foreach ($result['alerts'] as $alert) {
                        $icon = $alert['severity'] === 'critical' ? '🔴' : '🟡';
                        $this->warn("  {$icon} {$alert['message']}");
                        $this->line("     💡 {$alert['recommendation']}");
                    }
                    return 1;
                } else {
                    $this->info('✅ No performance issues detected');
                    return 0;
                }
            } else {
                $this->error('❌ Performance monitoring failed');
                $this->error($result['error']);
                return 1;
            }
        } else {
            $this->error('Please specify --server-id=X for monitoring');
            return 1;
        }
    }

    protected function handleConfiguration(): int
    {
        $this->info('⚙️  Server configuration management...');

        if ($serverId = $this->option('server-id')) {
            $server = Server::findOrFail($serverId);

            $this->info("Configuring server: {$server->name}");

            $configType = $this->choice(
                'What type of configuration would you like to manage?',
                ['inbounds', 'limits', 'security', 'networking'],
                'limits'
            );

            $configChanges = [];

            switch ($configType) {
                case 'limits':
                    $maxClients = $this->ask('Maximum clients', $server->max_clients);
                    $bandwidthLimit = $this->ask('Bandwidth limit (GB)', $server->bandwidth_limit_gb);

                    $configChanges['limits'] = [
                        'max_clients' => (int) $maxClients,
                        'bandwidth_limit_gb' => (int) $bandwidthLimit
                    ];
                    break;

                case 'inbounds':
                    $this->info('Inbound configuration management coming soon...');
                    return 0;

                case 'security':
                    $this->info('Security configuration management coming soon...');
                    return 0;

                case 'networking':
                    $this->info('Networking configuration management coming soon...');
                    return 0;
            }

            if (!empty($configChanges)) {
                $result = $this->serverManagementService->manageServerConfiguration($server, $configChanges);

                if ($result['success']) {
                    $this->info('✅ Server configuration updated successfully!');

                    foreach ($result['configuration_results'] as $type => $typeResult) {
                        $status = $typeResult['success'] ? '✅' : '❌';
                        $this->line("  {$status} {$type}: {$typeResult['message']}");
                    }

                    return 0;
                } else {
                    $this->error('❌ Configuration update failed');
                    $this->error($result['error']);
                    return 1;
                }
            }
        } else {
            $this->error('Please specify --server-id=X for configuration');
            return 1;
        }

        return 0;
    }
}
