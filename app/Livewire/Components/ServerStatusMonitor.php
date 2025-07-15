<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Server;
use App\Services\XUIService;
use Carbon\Carbon;

class ServerStatusMonitor extends Component
{
    // Component state
    public $servers = [];
    public $selectedServerId = null;
    public $autoRefresh = true;
    public $refreshInterval = 30; // seconds
    public $filterStatus = 'all'; // all, online, offline, warning
    public $sortBy = 'status'; // status, name, location, uptime
    public $sortDirection = 'asc';
    
    // Real-time status
    public $isLoading = false;
    public $lastUpdated;
    public $connectionErrors = [];
    
    // Statistics
    public $stats = [
        'total' => 0,
        'online' => 0,
        'offline' => 0,
        'warning' => 0,
        'average_response_time' => 0
    ];

    protected $listeners = [
        'server.status.updated' => 'handleServerStatusUpdate',
        'refresh-servers' => 'refreshServers',
        'echo:server-status,ServerStatusUpdated' => 'handleRealtimeUpdate'
    ];

    public function mount()
    {
        $this->lastUpdated = now();
        $this->loadServers();
    }

    public function render()
    {
        return view('livewire.components.server-status-monitor');
    }

    /**
     * Load servers with current status
     */
    public function loadServers()
    {
        $this->isLoading = true;
        
        try {
            $query = Server::with(['serverPlans', 'serverInbounds'])
                ->withCount(['serverPlans', 'activeClients'])
                ->select([
                    'id', 'name', 'hostname', 'port', 'username', 'password',
                    'country', 'flag_emoji', 'status', 'last_checked_at',
                    'response_time', 'uptime_percentage', 'created_at'
                ]);

            // Apply status filter
            if ($this->filterStatus !== 'all') {
                $query->where('status', $this->filterStatus);
            }

            // Apply sorting
            switch ($this->sortBy) {
                case 'name':
                    $query->orderBy('name', $this->sortDirection);
                    break;
                case 'location':
                    $query->orderBy('country', $this->sortDirection);
                    break;
                case 'uptime':
                    $query->orderBy('uptime_percentage', $this->sortDirection);
                    break;
                case 'response_time':
                    $query->orderBy('response_time', $this->sortDirection);
                    break;
                default:
                    $query->orderByRaw("FIELD(status,'online','warning','offline')");
                    if ($this->sortDirection === 'desc') {
                        $query->orderByRaw("FIELD(status,'offline','warning','online')");
                    }
            }

            $this->servers = $query->get()->map(function ($server) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'hostname' => $server->hostname,
                    'port' => $server->port,
                    'country' => $server->country,
                    'flag_emoji' => $server->flag_emoji,
                    'status' => $server->status,
                    'last_checked_at' => $server->last_checked_at?->format('Y-m-d H:i:s'),
                    'last_checked_human' => $server->last_checked_at?->diffForHumans(),
                    'response_time' => $server->response_time,
                    'uptime_percentage' => $server->uptime_percentage,
                    'server_plans_count' => $server->server_plans_count,
                    'active_clients_count' => $server->active_clients_count,
                    'connection_url' => "https://{$server->hostname}:{$server->port}",
                    'status_color' => $this->getStatusColor($server->status),
                    'status_icon' => $this->getStatusIcon($server->status),
                    'is_recently_checked' => $server->last_checked_at && $server->last_checked_at->isAfter(now()->subMinutes(5))
                ];
            })->toArray();

            $this->calculateStats();
            $this->lastUpdated = now();
            
        } catch (\Exception $e) {
            $this->addError('load_error', 'Failed to load servers: ' . $e->getMessage());
            logger()->error('ServerStatusMonitor: Failed to load servers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Refresh all server statuses
     */
    public function refreshServers()
    {
        $this->loadServers();
        $this->dispatch('server-status-refreshed');
    }

    /**
     * Check status of a specific server
     */
    public function checkServerStatus($serverId)
    {
        try {
            $server = Server::findOrFail($serverId);
            $xuiService = new XUIService($server);
            
            $startTime = microtime(true);
            $isOnline = $xuiService->testConnection();
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Update server status
            $server->update([
                'status' => $isOnline ? 'online' : 'offline',
                'response_time' => $responseTime,
                'last_checked_at' => now()
            ]);

            // Update uptime percentage
            $this->updateUptimePercentage($server);

            // Remove connection error if resolved
            unset($this->connectionErrors[$serverId]);

            $this->loadServers();
            
            $this->dispatch('server-checked', [
                'serverId' => $serverId,
                'status' => $server->status,
                'responseTime' => $responseTime
            ]);

        } catch (\Exception $e) {
            $this->connectionErrors[$serverId] = $e->getMessage();
            logger()->error('ServerStatusMonitor: Failed to check server', [
                'server_id' => $serverId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check all servers
     */
    public function checkAllServers()
    {
        $this->isLoading = true;
        
        foreach ($this->servers as $server) {
            $this->checkServerStatus($server['id']);
        }
        
        $this->isLoading = false;
        $this->dispatch('all-servers-checked');
    }

    /**
     * Toggle auto-refresh
     */
    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('start-auto-refresh', $this->refreshInterval);
        } else {
            $this->dispatch('stop-auto-refresh');
        }
    }

    /**
     * Update refresh interval
     */
    public function updateRefreshInterval($interval)
    {
        $this->refreshInterval = max(10, min(300, (int) $interval)); // 10-300 seconds
        
        if ($this->autoRefresh) {
            $this->dispatch('update-refresh-interval', $this->refreshInterval);
        }
    }

    /**
     * Filter servers by status
     */
    public function filterByStatus($status)
    {
        $this->filterStatus = $status;
        $this->loadServers();
    }

    /**
     * Sort servers
     */
    public function sortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        $this->loadServers();
    }

    /**
     * Select/deselect server
     */
    public function selectServer($serverId)
    {
        $this->selectedServerId = $this->selectedServerId === $serverId ? null : $serverId;
    }

    /**
     * Get selected server details
     */
    public function getSelectedServerProperty()
    {
        if (!$this->selectedServerId) {
            return null;
        }

        return collect($this->servers)->firstWhere('id', $this->selectedServerId);
    }

    /**
     * Handle real-time server status updates
     */
    #[On('echo:server-status,ServerStatusUpdated')]
    public function handleRealtimeUpdate($data)
    {
        $serverId = $data['server_id'];
        $newStatus = $data['status'];
        $responseTime = $data['response_time'] ?? null;

        // Update server in current list
        foreach ($this->servers as $index => $server) {
            if ($server['id'] == $serverId) {
                $this->servers[$index]['status'] = $newStatus;
                $this->servers[$index]['response_time'] = $responseTime;
                $this->servers[$index]['last_checked_at'] = now()->format('Y-m-d H:i:s');
                $this->servers[$index]['last_checked_human'] = 'Just now';
                $this->servers[$index]['status_color'] = $this->getStatusColor($newStatus);
                $this->servers[$index]['status_icon'] = $this->getStatusIcon($newStatus);
                break;
            }
        }

        $this->calculateStats();
        
        $this->dispatch('server-status-updated', [
            'serverId' => $serverId,
            'status' => $newStatus,
            'responseTime' => $responseTime
        ]);
    }

    /**
     * Calculate server statistics
     */
    private function calculateStats()
    {
        $total = count($this->servers);
        $online = 0;
        $offline = 0;
        $warning = 0;
        $totalResponseTime = 0;
        $responseTimeCount = 0;

        foreach ($this->servers as $server) {
            switch ($server['status']) {
                case 'online':
                    $online++;
                    break;
                case 'offline':
                    $offline++;
                    break;
                case 'warning':
                    $warning++;
                    break;
            }

            if ($server['response_time'] > 0) {
                $totalResponseTime += $server['response_time'];
                $responseTimeCount++;
            }
        }

        $this->stats = [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'warning' => $warning,
            'average_response_time' => $responseTimeCount > 0 ? round($totalResponseTime / $responseTimeCount) : 0
        ];
    }

    /**
     * Update server uptime percentage
     */
    private function updateUptimePercentage($server)
    {
        // Calculate uptime based on recent checks (last 24 hours)
        $checks = \DB::table('server_status_logs')
            ->where('server_id', $server->id)
            ->where('created_at', '>=', now()->subDay())
            ->get();

        if ($checks->count() > 0) {
            $onlineChecks = $checks->where('status', 'online')->count();
            $uptimePercentage = ($onlineChecks / $checks->count()) * 100;
            
            $server->update(['uptime_percentage' => round($uptimePercentage, 1)]);
        }
    }

    /**
     * Get status color
     */
    private function getStatusColor($status)
    {
        return match($status) {
            'online' => 'text-green-600',
            'offline' => 'text-red-600',
            'warning' => 'text-yellow-600',
            default => 'text-gray-600'
        };
    }

    /**
     * Get status icon
     */
    private function getStatusIcon($status)
    {
        return match($status) {
            'online' => '🟢',
            'offline' => '🔴',
            'warning' => '🟡',
            default => '⚪'
        };
    }

    /**
     * Export server status report
     */
    public function exportReport()
    {
        $filename = 'server-status-' . now()->format('Y-m-d-H-i-s') . '.csv';
        
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($handle, [
                'Server Name',
                'Location',
                'Status',
                'Response Time (ms)',
                'Uptime %',
                'Plans Count',
                'Active Clients',
                'Last Checked'
            ]);
            
            // CSV data
            foreach ($this->servers as $server) {
                fputcsv($handle, [
                    $server['name'],
                    $server['country'],
                    $server['status'],
                    $server['response_time'],
                    $server['uptime_percentage'],
                    $server['server_plans_count'],
                    $server['active_clients_count'],
                    $server['last_checked_human']
                ]);
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
