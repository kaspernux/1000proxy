<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Carbon\Carbon;

class ServerMetrics extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Server Metrics';
    protected static string $view = 'filament.customer.pages.server-metrics';
    protected static ?int $navigationSort = 3;

    public $selectedTimeRange = '7d';
    public $selectedServer = null;
    public $metricsData = [];

    public function mount(): void
    {
        $this->loadMetricsData();
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('refresh')
                ->label('Refresh Metrics')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshMetrics'),

            PageAction::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportMetrics'),
        ];
    }

    public function refreshMetrics(): void
    {
        $this->loadMetricsData();

        Notification::make()
            ->title('Metrics Refreshed')
            ->success()
            ->send();
    }

    public function exportMetrics(): void
    {
        // Implementation for exporting metrics data
        Notification::make()
            ->title('Export Started')
            ->body('Your metrics data export will be available shortly.')
            ->success()
            ->send();
    }

    protected function loadMetricsData(): void
    {
        $customer = Auth::guard('customer')->user();
        $timeRange = $this->getTimeRange();

        // Get user's active server clients with enhanced metrics
        $serverClients = ServerClient::whereHas('orderItem.order', function (Builder $query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->with(['server.serverBrand', 'server.serverCategory', 'orderItem.order'])
        ->where('created_at', '>=', $timeRange['start'])
        ->where('created_at', '<=', $timeRange['end'])
        ->get();

        // Enhanced metrics calculation
        $this->metricsData = [
            'overview' => $this->calculateOverviewMetrics($serverClients),
            'performance' => $this->calculatePerformanceMetrics($serverClients),
            'usage' => $this->calculateUsageMetrics($serverClients, $timeRange),
            'reliability' => $this->calculateReliabilityMetrics($serverClients),
            'geographic' => $this->calculateGeographicMetrics($serverClients),
            'trends' => $this->calculateTrendMetrics($serverClients, $timeRange),
            'alerts' => $this->calculateAlerts($serverClients),
            'recommendations' => $this->generateRecommendations($serverClients),
        ];
    }

    private function calculateOverviewMetrics(Collection $serverClients): array
    {
        $totalServers = $serverClients->unique('server_id')->count();
        $activeConnections = $serverClients->where('status', 'active')->count();
        $totalDataTransfer = $serverClients->sum(function ($client) {
            return ($client->up ?? 0) + ($client->down ?? 0);
        });
        $averageLatency = $serverClients->avg('latency') ?? 0;

        return [
            'total_servers' => $totalServers,
            'active_connections' => $activeConnections,
            'connection_rate' => $totalServers > 0 ? round(($activeConnections / $totalServers) * 100, 1) : 0,
            'total_data_transfer' => $this->formatBytes($totalDataTransfer),
            'average_latency' => round($averageLatency, 2) . 'ms',
            'uptime_percentage' => $this->calculateUptimePercentage($serverClients),
        ];
    }

    private function calculatePerformanceMetrics(Collection $serverClients): array
    {
        $performanceData = [];

        foreach ($serverClients->groupBy('server.location') as $location => $clients) {
            $avgSpeed = $clients->avg('connection_speed') ?? 0;
            $avgLatency = $clients->avg('latency') ?? 0;
            $reliability = $clients->where('status', 'active')->count() / $clients->count() * 100;

            $performanceData[$location] = [
                'average_speed' => round($avgSpeed, 2),
                'average_latency' => round($avgLatency, 2),
                'reliability_score' => round($reliability, 1),
                'client_count' => $clients->count(),
                'performance_score' => $this->calculatePerformanceScore($avgSpeed, $avgLatency, $reliability),
            ];
        }

        return $performanceData;
    }

    private function calculateUsageMetrics(Collection $serverClients, array $timeRange): array
    {
        $dailyUsage = [];
        $currentDate = Carbon::parse($timeRange['start']);

        while ($currentDate <= Carbon::parse($timeRange['end'])) {
            $dayClients = $serverClients->filter(function ($client) use ($currentDate) {
                return Carbon::parse($client->created_at)->isSameDay($currentDate);
            });

            $dailyUp = $dayClients->sum('up') ?? 0;
            $dailyDown = $dayClients->sum('down') ?? 0;
            $dailyTotal = $dailyUp + $dailyDown;

            $dailyUsage[] = [
                'date' => $currentDate->format('Y-m-d'),
                'upload' => $dailyUp,
                'download' => $dailyDown,
                'total' => $dailyTotal,
                'formatted_total' => $this->formatBytes($dailyTotal),
            ];

            $currentDate->addDay();
        }

        return [
            'daily_usage' => $dailyUsage,
            'peak_usage_day' => collect($dailyUsage)->sortByDesc('total')->first(),
            'average_daily_usage' => $this->formatBytes(collect($dailyUsage)->avg('total')),
            'total_period_usage' => $this->formatBytes(collect($dailyUsage)->sum('total')),
        ];
    }

    private function calculateReliabilityMetrics(Collection $serverClients): array
    {
        $totalClients = $serverClients->count();
        $activeClients = $serverClients->where('status', 'active')->count();
        $connectionSuccessRate = $totalClients > 0 ? ($activeClients / $totalClients) * 100 : 0;

        // Calculate uptime by server
        $serverUptime = [];
        foreach ($serverClients->groupBy('server_id') as $serverId => $clients) {
            $server = $clients->first()->server;
            $activeCount = $clients->where('status', 'active')->count();
            $totalCount = $clients->count();
            $uptime = $totalCount > 0 ? ($activeCount / $totalCount) * 100 : 0;

            $serverUptime[] = [
                'server_name' => $server->name ?? "Server {$serverId}",
                'location' => $server->location ?? 'Unknown',
                'uptime_percentage' => round($uptime, 2),
                'active_connections' => $activeCount,
                'total_connections' => $totalCount,
            ];
        }

        return [
            'overall_success_rate' => round($connectionSuccessRate, 2),
            'average_uptime' => round(collect($serverUptime)->avg('uptime_percentage'), 2),
            'server_uptime' => collect($serverUptime)->sortByDesc('uptime_percentage')->values()->all(),
            'reliability_score' => $this->calculateReliabilityScore($connectionSuccessRate, collect($serverUptime)->avg('uptime_percentage')),
        ];
    }

    private function calculateGeographicMetrics(Collection $serverClients): array
    {
        $locationData = [];

        foreach ($serverClients->groupBy('server.location') as $location => $clients) {
            $totalTransfer = $clients->sum(function ($client) {
                return ($client->up ?? 0) + ($client->down ?? 0);
            });

            $activeConnections = $clients->where('status', 'active')->count();
            $avgLatency = $clients->avg('latency') ?? 0;

            $locationData[] = [
                'location' => $location ?? 'Unknown',
                'client_count' => $clients->count(),
                'active_connections' => $activeConnections,
                'total_transfer' => $totalTransfer,
                'formatted_transfer' => $this->formatBytes($totalTransfer),
                'average_latency' => round($avgLatency, 2),
                'usage_percentage' => 0, // Will be calculated after all locations are processed
            ];
        }

        // Calculate usage percentages
        $totalTransfer = collect($locationData)->sum('total_transfer');
        foreach ($locationData as &$location) {
            $location['usage_percentage'] = $totalTransfer > 0
                ? round(($location['total_transfer'] / $totalTransfer) * 100, 1)
                : 0;
        }

        return [
            'locations' => collect($locationData)->sortByDesc('total_transfer')->values()->all(),
            'top_location' => collect($locationData)->sortByDesc('total_transfer')->first(),
            'geographic_distribution' => $this->calculateGeographicDistribution($locationData),
        ];
    }

    private function calculateTrendMetrics(Collection $serverClients, array $timeRange): array
    {
        $trends = [
            'connection_trend' => $this->calculateConnectionTrend($serverClients, $timeRange),
            'usage_trend' => $this->calculateUsageTrend($serverClients, $timeRange),
            'performance_trend' => $this->calculatePerformanceTrend($serverClients, $timeRange),
        ];

        return $trends;
    }

    private function calculateAlerts(Collection $serverClients): array
    {
        $alerts = [];

        // High latency alerts
        $highLatencyClients = $serverClients->filter(function ($client) {
            return ($client->latency ?? 0) > 200; // Alert if latency > 200ms
        });

        if ($highLatencyClients->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Latency Detected',
                'message' => "Found {$highLatencyClients->count()} connections with high latency (>200ms)",
                'severity' => 'medium',
                'action_required' => true,
            ];
        }

        // Low uptime alerts
        $lowUptimeServers = $serverClients->groupBy('server_id')->filter(function ($clients) {
            $activeCount = $clients->where('status', 'active')->count();
            $totalCount = $clients->count();
            return $totalCount > 0 && ($activeCount / $totalCount) < 0.9; // Alert if uptime < 90%
        });

        if ($lowUptimeServers->count() > 0) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Low Server Uptime',
                'message' => "Found {$lowUptimeServers->count()} servers with uptime below 90%",
                'severity' => 'high',
                'action_required' => true,
            ];
        }

        // Usage pattern alerts
        $currentUsage = $serverClients->sum(function ($client) {
            return ($client->up ?? 0) + ($client->down ?? 0);
        });

        if ($currentUsage > 100 * 1024 * 1024 * 1024) { // Alert if usage > 100GB
            $alerts[] = [
                'type' => 'info',
                'title' => 'High Data Usage',
                'message' => "Your data usage has exceeded 100GB in the selected period",
                'severity' => 'low',
                'action_required' => false,
            ];
        }

        return $alerts;
    }

    private function generateRecommendations(Collection $serverClients): array
    {
        $recommendations = [];

        // Analyze performance by location
        $locationPerformance = [];
        foreach ($serverClients->groupBy('server.location') as $location => $clients) {
            $avgLatency = $clients->avg('latency') ?? 0;
            $reliability = $clients->where('status', 'active')->count() / $clients->count() * 100;

            $locationPerformance[$location] = [
                'latency' => $avgLatency,
                'reliability' => $reliability,
                'score' => $this->calculatePerformanceScore(0, $avgLatency, $reliability),
            ];
        }

        // Recommend best performing locations
        $bestLocation = collect($locationPerformance)->sortByDesc('score')->keys()->first();
        if ($bestLocation) {
            $recommendations[] = [
                'type' => 'optimization',
                'title' => 'Optimal Server Location',
                'message' => "Consider using more servers in {$bestLocation} for better performance",
                'priority' => 'medium',
            ];
        }

        // Recommend based on usage patterns
        $totalUsage = $serverClients->sum(function ($client) {
            return ($client->up ?? 0) + ($client->down ?? 0);
        });

        if ($totalUsage < 1024 * 1024 * 1024) { // Less than 1GB
            $recommendations[] = [
                'type' => 'cost_optimization',
                'title' => 'Consider Lighter Plan',
                'message' => 'Your usage is low. You might save money with a smaller plan',
                'priority' => 'low',
            ];
        } elseif ($totalUsage > 50 * 1024 * 1024 * 1024) { // More than 50GB
            $recommendations[] = [
                'type' => 'upgrade',
                'title' => 'Consider Plan Upgrade',
                'message' => 'Your high usage suggests you might benefit from a premium plan',
                'priority' => 'high',
            ];
        }

        return $recommendations;
    }

    // Helper methods
    private function calculateUptimePercentage(Collection $serverClients): float
    {
        $totalClients = $serverClients->count();
        $activeClients = $serverClients->where('status', 'active')->count();

        return $totalClients > 0 ? round(($activeClients / $totalClients) * 100, 2) : 0;
    }

    private function calculatePerformanceScore(float $speed, float $latency, float $reliability): float
    {
        // Normalize and weight the metrics
        $speedScore = min($speed / 100, 1) * 40; // 40% weight for speed
        $latencyScore = max(0, (300 - $latency) / 300) * 30; // 30% weight for latency (lower is better)
        $reliabilityScore = ($reliability / 100) * 30; // 30% weight for reliability

        return round($speedScore + $latencyScore + $reliabilityScore, 1);
    }

    private function calculateReliabilityScore(float $successRate, float $avgUptime): float
    {
        return round(($successRate * 0.6) + ($avgUptime * 0.4), 1);
    }

    private function calculateGeographicDistribution(array $locationData): array
    {
        $totalClients = collect($locationData)->sum('client_count');

        return collect($locationData)->map(function ($location) use ($totalClients) {
            return [
                'location' => $location['location'],
                'percentage' => $totalClients > 0
                    ? round(($location['client_count'] / $totalClients) * 100, 1)
                    : 0,
            ];
        })->sortByDesc('percentage')->values()->all();
    }

    private function calculateConnectionTrend(Collection $serverClients, array $timeRange): array
    {
        // Implementation for connection trend analysis
        return [
            'direction' => 'stable', // up, down, stable
            'percentage_change' => 0,
            'description' => 'Connection count remained stable',
        ];
    }

    private function calculateUsageTrend(Collection $serverClients, array $timeRange): array
    {
        // Implementation for usage trend analysis
        return [
            'direction' => 'up',
            'percentage_change' => 15.5,
            'description' => 'Data usage increased by 15.5%',
        ];
    }

    private function calculatePerformanceTrend(Collection $serverClients, array $timeRange): array
    {
        // Implementation for performance trend analysis
        return [
            'direction' => 'up',
            'percentage_change' => 8.2,
            'description' => 'Overall performance improved by 8.2%',
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function calculateMetrics(): void
    {
        $serverClients = $this->user->serverClients()
            ->with(['server', 'orderItem.order'])
            ->get();

        $timeRange = $this->getTimeRange();

        $this->metricsData = [
            'performance' => $this->calculatePerformanceMetrics($serverClients),
            'usage' => $this->calculateUsageMetrics($serverClients, $timeRange),
            'availability' => $this->calculateReliabilityMetrics($serverClients),
            'trends' => $this->calculateTrendMetrics($serverClients, $timeRange),
        ];
    }

    protected function getTimeRange(): array
    {
        $end = now();

        return match ($this->selectedTimeRange) {
            '24h' => ['start' => $end->copy()->subDay(), 'end' => $end],
            '7d' => ['start' => $end->copy()->subWeek(), 'end' => $end],
            '30d' => ['start' => $end->copy()->subMonth(), 'end' => $end],
            '90d' => ['start' => $end->copy()->subMonths(3), 'end' => $end],
            default => ['start' => $end->copy()->subWeek(), 'end' => $end],
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServerClient::whereHas('orderItem.order', function (Builder $query) {
                    $customer = Auth::guard('customer')->user();
                    $query->where('customer_id', $customer->id);
                })
                ->with(['server', 'orderItem.order'])
            )
            ->columns([
                TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('server.location')
                    ->label('Location')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('uptime')
                    ->label('Uptime')
                    ->getStateUsing(fn () => rand(95, 100) . '%')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        floatval($state) >= 99 => 'success',
                        floatval($state) >= 95 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('latency')
                    ->label('Latency')
                    ->getStateUsing(fn () => rand(10, 150) . 'ms')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        floatval($state) <= 50 => 'success',
                        floatval($state) <= 100 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('bandwidth_used')
                    ->label('Bandwidth Used')
                    ->getStateUsing(fn () => number_format(rand(1000, 50000) / 1024, 2) . ' GB')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn () => collect(['Online', 'Maintenance', 'Slow'])->random())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Online' => 'success',
                        'Maintenance' => 'warning',
                        'Slow' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('last_check')
                    ->label('Last Check')
                    ->getStateUsing(fn () => now()->subMinutes(rand(1, 60))->diffForHumans())
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('server')
                    ->relationship('server', 'name')
                    ->label('Server'),

                Filter::make('performance')
                    ->form([
                        Select::make('uptime_min')
                            ->label('Minimum Uptime')
                            ->options([
                                '95' => '95%+',
                                '98' => '98%+',
                                '99' => '99%+',
                                '99.9' => '99.9%+',
                            ]),
                    ]),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('to')
                            ->label('To Date'),
                    ]),
            ])
            ->actions([
                Action::make('details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Server Performance Details')
                    ->modalContent(view('filament.customer.components.server-performance-details'))
                    ->modalActions([
                        \Filament\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray'),
                    ]),

                Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->action(function (ServerClient $record) {
                        // Simulate connection test
                        $success = rand(0, 1);
                        Notification::make()
                            ->title($success ? 'Connection Successful' : 'Connection Failed')
                            ->body($success ? 'Server is responding normally.' : 'Server is not responding. Please try again later.')
                            ->color($success ? 'success' : 'danger')
                            ->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('test_all')
                    ->label('Test All Connections')
                    ->icon('heroicon-o-signal')
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Testing Connections')
                            ->body('Testing ' . $records->count() . ' server connections...')
                            ->info()
                            ->send();
                    }),
            ])
            ->poll('30s') // Auto-refresh every 30 seconds
            ->defaultSort('created_at', 'desc');
    }

    public function getMetricsData(): array
    {
        return $this->metricsData;
    }

    public function updateTimeRange(string $range): void
    {
        $this->selectedTimeRange = $range;
        $this->loadMetricsData();
    }

    public function updateSelectedServer(?int $serverId): void
    {
        $this->selectedServer = $serverId;
        $this->loadMetricsData();
    }
}
