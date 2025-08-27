<?php

namespace App\Filament\Widgets;

use App\Models\Server;
use App\Services\Dashboard\MetricsAggregator;
use App\Services\XUIService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * UnifiedOpsOverviewWidget
 * Merges AdminDashboardStatsWidget (business KPIs) and InfrastructureHealthWidget (infra KPIs)
 * into a single, polished overview with fast polling and event-driven refresh.
 */
class UnifiedOpsOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.unified-ops-overview-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '45s';

    public array $business = [];
    public array $infra = [];

    protected $listeners = [
        'refreshRevenueMetrics' => 'refreshData',
        'orderPaid' => 'refreshData',
        'serverStatusUpdated' => 'refreshData',
        'refreshInfrastructureHealth' => 'refreshData',
    ];

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->business = $this->buildBusinessKpis();
        $this->infra = $this->buildInfraKpis();
        $this->dispatch('$refresh');
    }

    protected function buildBusinessKpis(): array
    {
        /** @var MetricsAggregator $metrics */
        $metrics = App::make(MetricsAggregator::class);
        $rev = $metrics->revenueSummary();
        $cust = $metrics->customerSummary();

        // Lightweight derived stats
        $ordersPaid = \App\Models\Order::where('payment_status', 'paid')->count();
        $customersTotal = (int) ($cust['total'] ?? 0);
        $conversion = $customersTotal === 0 ? 0 : round(($ordersPaid / $customersTotal) * 100, 1);

        return [
            [
                'label' => __('Total Revenue'),
                'value' => '$' . number_format($rev['total'] ?? 0, 2),
                'desc' => __('All-time revenue'),
                'icon' => 'heroicon-m-banknotes',
                'color' => 'success',
            ],
            [
                'label' => __('Monthly Revenue'),
                'value' => '$' . number_format($rev['monthTotal'] ?? 0, 2),
                'desc' => ($rev['growth'] ?? 0) . '% ' . __('vs last month'),
                'icon' => ($rev['growth'] ?? 0) >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'color' => ($rev['growth'] ?? 0) >= 0 ? 'success' : 'danger',
            ],
            [
                'label' => __('Total Customers'),
                'value' => number_format($cust['total'] ?? 0),
                'desc' => ($cust['newThisMonth'] ?? 0) . ' ' . __('new this month'),
                'icon' => 'heroicon-m-user-group',
                'color' => 'info',
            ],
            [
                'label' => __('Active Clients'),
                'value' => number_format($cust['activeClients'] ?? ($cust['active'] ?? 0)),
                'desc' => ($cust['subscriptionGrowth'] ?? 0) . '% ' . __('vs last month'),
                'icon' => ($cust['subscriptionGrowth'] ?? 0) >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'color' => ($cust['subscriptionGrowth'] ?? 0) >= 0 ? 'success' : 'danger',
            ],
            [
                'label' => __('Retention'),
                'value' => number_format($cust['retention'] ?? 0, 1) . '%',
                'desc' => __('90-day retention'),
                'icon' => 'heroicon-m-heart',
                'color' => 'purple',
            ],
            [
                'label' => __('Conversion Rate'),
                'value' => number_format($conversion, 1) . '%',
                'desc' => __('Visitor → customer'),
                'icon' => 'heroicon-m-chart-bar',
                'color' => 'cyan',
            ],
        ];
    }

    protected function buildInfraKpis(): array
    {
        /** @var MetricsAggregator $metrics */
        $metrics = App::make(MetricsAggregator::class);
        $server = $metrics->serverSummary();
        $traffic = $metrics->serverTraffic();
        $connections = $metrics->activeConnectionsTrend();
        $xui = $this->getXuiPanelStatus();
        $cpu = $this->getAverageCpuLoad();

        return [
            [
                'label' => __('Server Fleet'),
                'value' => ($server['online'] ?? 0) . '/' . ($server['total'] ?? 0) . ' ' . __('Online'),
                'desc' => $this->serverFleetDescription($server),
                'icon' => ($server['online'] ?? 0) >= (($server['total'] ?? 0) * 0.9) ? 'heroicon-m-check-circle' : ((($server['online'] ?? 0) >= (($server['total'] ?? 0) * 0.7)) ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-x-circle'),
                'color' => ($server['online'] ?? 0) >= (($server['total'] ?? 0) * 0.9) ? 'success' : ((($server['online'] ?? 0) >= (($server['total'] ?? 0) * 0.7)) ? 'warning' : 'danger'),
            ],
            [
                'label' => __('X-UI Panels'),
                'value' => ($xui['percentage'] ?? 0) . '% ' . __('Connected'),
                'desc' => ($xui['connected'] ?? 0) . ' / ' . ($xui['total'] ?? 0) . ' ' . __('panels'),
                'icon' => ($xui['percentage'] ?? 0) >= 90 ? 'heroicon-m-globe-alt' : ((($xui['percentage'] ?? 0) >= 70) ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-x-circle'),
                'color' => ($xui['percentage'] ?? 0) >= 90 ? 'success' : ((($xui['percentage'] ?? 0) >= 70) ? 'warning' : 'danger'),
            ],
            [
                'label' => __('Active Connections'),
                'value' => number_format($connections['current'] ?? 0),
                'desc' => (string) ($connections['description'] ?? ''),
                'icon' => (string) ($connections['icon'] ?? 'heroicon-m-signal'),
                'color' => (string) ($connections['color'] ?? 'info'),
            ],
            [
                'label' => __('System Traffic'),
                'value' => (string) ($traffic['displayTotal'] ?? '0 B'),
                'desc' => '↑ ' . ($traffic['displayUp'] ?? '0 B') . ' ↓ ' . ($traffic['displayDown'] ?? '0 B'),
                'icon' => 'heroicon-m-signal',
                'color' => 'info',
            ],
            [
                'label' => __('Avg CPU Load'),
                'value' => ($cpu['avg'] ?? 0) . '%',
                'desc' => (string) ($cpu['description'] ?? ''),
                'icon' => (string) ($cpu['icon'] ?? 'heroicon-m-cpu-chip'),
                'color' => (string) ($cpu['color'] ?? 'gray'),
            ],
        ];
    }

    private function serverFleetDescription(array $server): string
    {
        $dist = (array) ($server['statusDistribution'] ?? []);
        $down = (int) ($dist['down'] ?? 0);
        $paused = (int) ($dist['paused'] ?? 0);
        return $down . ' ' . __('down') . ', ' . $paused . ' ' . __('paused');
    }

    private function getXuiPanelStatus(): array
    {
        return Cache::remember('infra.xui.status', 300, function () {
            $servers = Server::where('status', 'up')->get();
            $connected = 0; $failed = 0;
            foreach ($servers as $server) {
                try {
                    $service = new XUIService($server);
                    if ($service->testConnection()) { $connected++; } else { $failed++; }
                } catch (\Throwable $e) { $failed++; }
            }
            $total = $servers->count();
            $percentage = $total > 0 ? round(($connected / $total) * 100, 1) : 0;
            return compact('connected','failed','total','percentage');
        });
    }

    private function getAverageCpuLoad(): array
    {
        return Cache::remember('infra.cpu.avg', 300, function () {
            $servers = Server::where('status','up')->get();
            if ($servers->isEmpty()) {
                return [ 'avg' => 0, 'description' => __('No active servers'), 'icon' => 'heroicon-m-cpu-chip', 'color' => 'gray' ];
            }
            $loads = [];
            foreach ($servers as $s) {
                $metrics = $s->performance_metrics;
                $load = null;
                if (is_array($metrics) && isset($metrics['cpu_load'])) { $load = (float)$metrics['cpu_load']; }
                elseif (is_string($metrics)) { $decoded = json_decode($metrics, true); if (isset($decoded['cpu_load'])) $load = (float)$decoded['cpu_load']; }
                if ($load !== null) { $loads[] = $load; }
            }
            $avg = empty($loads) ? 0 : round(array_sum($loads)/count($loads),1);
            $description = $avg === 0 ? __('No telemetry') : ($avg < 50 ? __('Optimal') : ($avg < 70 ? __('Normal') : ($avg < 85 ? __('High load') : __('Critical'))));
            $icon = $avg < 85 ? 'heroicon-m-cpu-chip' : 'heroicon-m-fire';
            $color = $avg < 70 ? 'success' : ($avg < 85 ? 'warning' : 'danger');
            return compact('avg','description','icon','color');
        });
    }
}
