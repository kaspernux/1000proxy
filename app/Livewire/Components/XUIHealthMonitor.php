<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\Server;
use App\Models\ServerClient;
use App\Services\XUIService;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use App\Livewire\Traits\LivewireAlertV4;

class XUIHealthMonitor extends Component
{
    use LivewireAlertV4;

    #[Reactive]
    public $servers;

    public $selectedServer = null;
    public $refreshInterval = 30; // seconds
    public $autoRefresh = true;
    public $healthData = [];
    public $clientStats = [];
    public $systemMetrics = [];
    public $lastUpdate = null;
    public $monitoringActive = true;

    protected $listeners = [
        'refreshHealth' => 'refreshAllHealth',
        'serverStatusChanged' => 'handleServerStatusChange'
    ];

    public function mount($servers = null)
    {
        $this->servers = $servers ?? Server::where('is_active', true)->get();
        $this->loadAllHealthData();
        $this->lastUpdate = now();
    }

    public function render()
    {
        return view('livewire.components.xui-health-monitor', [
            'healthSummary' => $this->getHealthSummary(),
            'alertsCount' => $this->getAlertsCount(),
            'serverGroups' => $this->groupServersByStatus()
        ]);
    }

    public function refreshAllHealth()
    {
        $this->loadAllHealthData();
        $this->lastUpdate = now();

        $this->alert('success', 'Health data refreshed!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function selectServer($serverId)
    {
        $this->selectedServer = $serverId;
        $this->loadServerDetails($serverId);
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;

        if ($this->autoRefresh) {
            $this->dispatch('startAutoRefresh', interval: $this->refreshInterval);
        } else {
            $this->dispatch('stopAutoRefresh');
        }
    }

    public function changeRefreshInterval($interval)
    {
        $this->refreshInterval = max(5, min(300, $interval)); // Between 5 seconds and 5 minutes

        if ($this->autoRefresh) {
            $this->dispatch('updateRefreshInterval', interval: $this->refreshInterval);
        }
    }

    public function testServerConnection($serverId)
    {
        $server = $this->servers->find($serverId);
        if (!$server) return;

        try {
            $xuiService = new XUIService($server);
            $loginResult = $xuiService->login();

            $this->healthData[$serverId]['connection_test'] = [
                'success' => $loginResult,
                'response_time' => rand(50, 300), // Simulated response time
                'tested_at' => now(),
                'error' => $loginResult ? null : 'Authentication failed'
            ];

            if ($loginResult) {
                $this->alert('success', "Connection to {$server->name} successful!", [
                    'position' => 'top-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
            } else {
                $this->alert('error', "Connection to {$server->name} failed: Authentication failed", [
                    'position' => 'top-end',
                    'timer' => 5000,
                    'toast' => true,
                ]);
            }
        } catch (\Exception $e) {
            $this->healthData[$serverId]['connection_test'] = [
                'success' => false,
                'response_time' => null,
                'tested_at' => now(),
                'error' => $e->getMessage()
            ];

            $this->alert('error', "Error testing {$server->name}: " . $e->getMessage(), [
                'position' => 'top-end',
                'timer' => 5000,
                'toast' => true,
            ]);
        }
    }

    public function restartServer($serverId)
    {
        $server = $this->servers->find($serverId);
        if (!$server) return;

        try {
            // This would restart the XUI service
            $this->alert('warning', "Restarting {$server->name}...", [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);

            // Simulate restart process
            $this->healthData[$serverId]['status'] = 'restarting';

            // In production, this would call actual restart API
            // For demo, simulate restart completion after delay
            $this->dispatch('simulateRestart', serverId: $serverId);

        } catch (\Exception $e) {
            $this->alert('error', "Error restarting {$server->name}: " . $e->getMessage(), [
                'position' => 'top-end',
                'timer' => 5000,
                'toast' => true,
            ]);
        }
    }

    #[On('serverStatusChanged')]
    public function handleServerStatusChange($serverId, $status)
    {
        if (isset($this->healthData[$serverId])) {
            $this->healthData[$serverId]['status'] = $status;
        }

        $server = $this->servers->find($serverId);
        if ($server) {
            $this->alert('info', "{$server->name} status changed to: {$status}", [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    private function loadAllHealthData()
    {
        foreach ($this->servers as $server) {
            $this->healthData[$server->id] = $this->generateHealthData($server);
            $this->clientStats[$server->id] = $this->getClientStats($server);
        }
    }

    private function loadServerDetails($serverId)
    {
        $server = $this->servers->find($serverId);
        if (!$server) return;

        $this->systemMetrics[$serverId] = $this->generateSystemMetrics($server);
    }

    private function generateHealthData($server)
    {
        // Simulate health data - in production this would call actual XUI APIs
        $isOnline = rand(1, 10) > 1; // 90% uptime simulation

        return [
            'status' => $isOnline ? 'online' : 'offline',
            'response_time' => $isOnline ? rand(10, 200) : null,
            'uptime' => rand(85, 100) . '%',
            'last_check' => now(),
            'version' => '1.8.' . rand(1, 5),
            'cpu_usage' => rand(10, 80),
            'memory_usage' => rand(20, 90),
            'disk_usage' => rand(30, 85),
            'network_in' => rand(100, 5000) . ' KB/s',
            'network_out' => rand(200, 8000) . ' KB/s',
            'active_connections' => rand(0, 500),
            'total_bandwidth' => rand(1, 100) . ' GB',
            'errors_count' => rand(0, 5),
            'warnings_count' => rand(0, 10)
        ];
    }

    private function getClientStats($server)
    {
        // Get actual client stats from database
        $clientsCount = ServerClient::where('server_id', $server->id)->count();
        $activeClientsCount = ServerClient::where('server_id', $server->id)
            ->where('is_active', true)
            ->count();

        return [
            'total_clients' => $clientsCount,
            'active_clients' => $activeClientsCount,
            'inactive_clients' => $clientsCount - $activeClientsCount,
            'clients_online' => rand(0, $activeClientsCount),
            'total_traffic' => rand(1, 500) . ' GB',
            'daily_traffic' => rand(10, 50) . ' GB'
        ];
    }

    private function generateSystemMetrics($server)
    {
        return [
            'system_load' => [
                'load_1m' => round(rand(10, 300) / 100, 2),
                'load_5m' => round(rand(10, 250) / 100, 2),
                'load_15m' => round(rand(10, 200) / 100, 2)
            ],
            'memory' => [
                'total' => rand(2, 16) . ' GB',
                'used' => rand(1, 12) . ' GB',
                'free' => rand(1, 8) . ' GB',
                'usage_percent' => rand(20, 85)
            ],
            'disk' => [
                'total' => rand(20, 500) . ' GB',
                'used' => rand(10, 400) . ' GB',
                'free' => rand(5, 100) . ' GB',
                'usage_percent' => rand(30, 90)
            ],
            'network_interfaces' => [
                'eth0' => [
                    'rx_bytes' => rand(1000000, 10000000),
                    'tx_bytes' => rand(500000, 8000000),
                    'rx_packets' => rand(10000, 100000),
                    'tx_packets' => rand(8000, 80000)
                ]
            ]
        ];
    }

    private function getHealthSummary()
    {
        $online = 0;
        $offline = 0;
        $warning = 0;
        $total = count($this->healthData);

        foreach ($this->healthData as $health) {
            switch ($health['status']) {
                case 'online':
                    if ($health['cpu_usage'] > 80 || $health['memory_usage'] > 90) {
                        $warning++;
                    } else {
                        $online++;
                    }
                    break;
                case 'offline':
                    $offline++;
                    break;
                default:
                    $warning++;
            }
        }

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'warning' => $warning,
            'health_percentage' => $total > 0 ? round(($online / $total) * 100) : 0
        ];
    }

    private function getAlertsCount()
    {
        $alerts = 0;

        foreach ($this->healthData as $health) {
            if ($health['status'] === 'offline') {
                $alerts++;
            }
            if ($health['cpu_usage'] > 90) {
                $alerts++;
            }
            if ($health['memory_usage'] > 95) {
                $alerts++;
            }
            if ($health['errors_count'] > 3) {
                $alerts++;
            }
        }

        return $alerts;
    }

    private function groupServersByStatus()
    {
        $groups = [
            'online' => [],
            'offline' => [],
            'warning' => []
        ];

        foreach ($this->servers as $server) {
            $health = $this->healthData[$server->id] ?? null;
            if (!$health) continue;

            $status = $health['status'];
            if ($status === 'online' &&
                ($health['cpu_usage'] > 80 || $health['memory_usage'] > 90)) {
                $status = 'warning';
            }

            $groups[$status][] = [
                'server' => $server,
                'health' => $health,
                'clients' => $this->clientStats[$server->id] ?? []
            ];
        }

        return $groups;
    }

    // Auto-refresh polling
    public function pollHealth()
    {
        if ($this->autoRefresh && $this->monitoringActive) {
            // Only refresh if enough time has passed
            if ($this->lastUpdate && $this->lastUpdate->diffInSeconds(now()) >= $this->refreshInterval) {
                $this->refreshAllHealth();
            }
        }
    }
}
