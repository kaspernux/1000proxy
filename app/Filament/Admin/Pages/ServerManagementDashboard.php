<?php

namespace App\Filament\Admin\Pages;

use App\Services\ServerManagementService;
use App\Models\Server;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class ServerManagementDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static string $view = 'filament.admin.pages.server-management-dashboard';
    protected static ?string $navigationLabel = 'Server Management';
    protected static ?string $title = 'Server Management Dashboard';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'Infrastructure';

    public array $dashboardData = [];
    public array $bulkHealthResults = [];
    public bool $showProvisioningForm = false;

    protected ServerManagementService $serverManagementService;

    public function boot(ServerManagementService $serverManagementService): void
    {
        $this->serverManagementService = $serverManagementService;
    }

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    protected function loadDashboardData(): void
    {
        $this->dashboardData = $this->serverManagementService->getManagementDashboardData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkHealthCheck')
                ->label('Run Health Check')
                ->icon('heroicon-o-heart')
                ->color('info')
                ->action(function () {
                    $this->runBulkHealthCheck();
                }),

            Action::make('provisionServer')
                ->label('Provision New Server')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    TextInput::make('name')
                        ->label('Server Name')
                        ->required()
                        ->placeholder('e.g., US-East-Gaming-01'),

                    Select::make('country')
                        ->label('Country')
                        ->required()
                        ->options([
                            'US' => 'United States',
                            'UK' => 'United Kingdom',
                            'DE' => 'Germany',
                            'FR' => 'France',
                            'JP' => 'Japan',
                            'SG' => 'Singapore',
                            'CA' => 'Canada',
                            'AU' => 'Australia'
                        ]),

                    TextInput::make('city')
                        ->label('City')
                        ->required()
                        ->placeholder('e.g., New York'),

                    TextInput::make('ip_address')
                        ->label('IP Address')
                        ->required()
                        ->placeholder('e.g., 192.168.1.100'),

                    TextInput::make('panel_url')
                        ->label('X-UI Panel URL')
                        ->required()
                        ->url()
                        ->placeholder('https://your-server.com:54321'),

                    TextInput::make('panel_username')
                        ->label('Panel Username')
                        ->required(),

                    TextInput::make('panel_password')
                        ->label('Panel Password')
                        ->password()
                        ->required(),

                    TextInput::make('max_clients')
                        ->label('Max Clients')
                        ->numeric()
                        ->default(1000),

                    TextInput::make('bandwidth_limit_gb')
                        ->label('Bandwidth Limit (GB)')
                        ->numeric()
                        ->default(1000),
                ])
                ->action(function (array $data) {
                    $this->provisionNewServer($data);
                }),

            Action::make('refreshData')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadDashboardData();

                    Notification::make()
                        ->title('Dashboard refreshed')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function runBulkHealthCheck(): void
    {
        try {
            $this->bulkHealthResults = $this->serverManagementService->performBulkHealthCheck();

            $healthyCount = $this->bulkHealthResults['healthy_servers'];
            $totalCount = $this->bulkHealthResults['total_servers'];

            Notification::make()
                ->title('Health Check Completed')
                ->body("{$healthyCount}/{$totalCount} servers are healthy")
                ->success()
                ->send();

            $this->loadDashboardData();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Health Check Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function provisionNewServer(array $data): void
    {
        try {
            $result = $this->serverManagementService->provisionNewServer($data);

            if ($result['success']) {
                Notification::make()
                    ->title('Server Provisioned Successfully')
                    ->body("Server '{$data['name']}' has been provisioned and configured")
                    ->success()
                    ->send();

                $this->loadDashboardData();
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error occurred');
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Server Provisioning Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkServerHealth(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $healthResult = $this->serverManagementService->checkServerHealth($server);

            $status = $healthResult['status'];
            $responseTime = $healthResult['response_time'];

            Notification::make()
                ->title("Server Health: {$status}")
                ->body("Response time: {$responseTime}ms")
                ->color($status === 'healthy' ? 'success' : ($status === 'warning' ? 'warning' : 'danger'))
                ->send();

            $this->loadDashboardData();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Health Check Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function monitorServerPerformance(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $performanceResult = $this->serverManagementService->monitorServerPerformance($server);

            if ($performanceResult['success']) {
                $alertsCount = count($performanceResult['alerts']);

                if ($alertsCount > 0) {
                    $alertTypes = collect($performanceResult['alerts'])
                        ->pluck('type')
                        ->join(', ');

                    Notification::make()
                        ->title('Performance Alerts Detected')
                        ->body("{$alertsCount} alerts: {$alertTypes}")
                        ->warning()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Server Performance Normal')
                        ->body('No performance issues detected')
                        ->success()
                        ->send();
                }
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Performance Monitoring Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getDashboardSummary(): array
    {
        return $this->dashboardData['summary'] ?? [
            'total_servers' => 0,
            'active_servers' => 0,
            'healthy_servers' => 0,
            'servers_with_alerts' => 0,
            'total_clients' => 0,
            'total_bandwidth_gb' => 0,
            'average_response_time' => 0,
            'overall_uptime' => 0
        ];
    }

    public function getServersByStatus(): array
    {
        return $this->dashboardData['servers_by_status'] ?? [
            'healthy' => 0,
            'warning' => 0,
            'unhealthy' => 0,
            'offline' => 0,
            'provisioning' => 0
        ];
    }

    public function getGeographicDistribution(): array
    {
        return $this->dashboardData['geographic_distribution'] ?? [];
    }

    public function getTopPerformingServers(): array
    {
        return $this->dashboardData['top_performing_servers'] ?? [];
    }

    public function getServersNeedingAttention(): array
    {
        return $this->dashboardData['servers_needing_attention'] ?? [];
    }

    public function getBulkHealthResults(): array
    {
        return $this->bulkHealthResults;
    }

    public function getRecentAlerts(): array
    {
        return $this->dashboardData['recent_alerts'] ?? [];
    }

    protected function getViewData(): array
    {
        return [
            'summary' => $this->getDashboardSummary(),
            'serversByStatus' => $this->getServersByStatus(),
            'geographicDistribution' => $this->getGeographicDistribution(),
            'topPerformingServers' => $this->getTopPerformingServers(),
            'serversNeedingAttention' => $this->getServersNeedingAttention(),
            'bulkHealthResults' => $this->getBulkHealthResults(),
            'recentAlerts' => $this->getRecentAlerts(),
        ];
    }
}
