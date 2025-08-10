<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Admin\Widgets\{
    RevenueByMethodWidget,
    UserGrowthWidget,
    UserGrowthChartWidget,
    LocationPopularityWidget,
    ProtocolUsageWidget,
    RevenueForecastWidget,
    TrendsWidget,
    RecommendationsWidget,
    OrderMetricsWidget,
    ChurnPredictionWidget,
    PWAInstallationWidget,
    ServerOpsOverviewWidget
};
// Unified cross-namespace widgets (new consolidated dashboard + infra + combined growth/revenue chart)
use App\Filament\Widgets\{AdminDashboardStatsWidget, InfrastructureHealthWidget, AdminChartsWidget};
use Illuminate\Support\Facades\Storage;

class AdminMainDashboard extends BaseDashboard
{

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationLabel = 'Admin Dashboard';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'admin';

    protected int $exportNotificationsCount = 0;
    protected $listeners = ['refreshExportNotifications' => 'updateExportNotificationsCount'];
    public array $recentExports = [];

    public function getHeading(): string
    {
        return 'Admin Control Center';
    }

    public function getWidgets(): array
    {
        // Layout ordering philosophy:
        // 1. Global KPIs (AdminDashboardStatsWidget)
        // 2. Infrastructure health (InfrastructureHealthWidget)
        // 3. Core revenue & growth multi-series chart (AdminChartsWidget)
        // 4. Specialized revenue breakdown & forecasts
        // 5. User growth / churn / recommendations & protocol/location intelligence
        return [
            AdminDashboardStatsWidget::class,
            InfrastructureHealthWidget::class,
            AdminChartsWidget::class,
            // Operational overview (maintenance counts & quick server ops summary)
            ServerOpsOverviewWidget::class,
            RevenueByMethodWidget::class,
            RevenueForecastWidget::class,
            OrderMetricsWidget::class,
            UserGrowthChartWidget::class,
            UserGrowthWidget::class,
            TrendsWidget::class,
            ChurnPredictionWidget::class,
            ProtocolUsageWidget::class,
            LocationPopularityWidget::class,
            RecommendationsWidget::class,
            // PWA status near end (infrastructure/app readiness utility)
            PWAInstallationWidget::class,
        ];
    }

    public function mount(): void
    {
        $this->exportNotificationsCount = auth()->user()?->notifications()
            ->where('type', \App\Notifications\ExportReadyNotification::class)
            ->whereNull('read_at')
            ->count();
        $this->loadRecentExports();
    }

    protected function loadRecentExports(): void
    {
        $dirs = ['exports/orders', 'exports/analytics'];
        $all = [];
        foreach ($dirs as $dir) {
            if (Storage::disk('local')->exists($dir)) {
                foreach (Storage::disk('local')->files($dir) as $file) {
                    $all[] = [
                        'path' => $file,
                        'name' => basename($file),
                        'url' => route('admin.download-export', ['path' => base64_encode($file)]),
                        'time' => Storage::disk('local')->lastModified($file),
                    ];
                }
            }
        }
        $this->recentExports = collect($all)->sortByDesc('time')->take(10)->values()->all();
    }

    public function updateExportNotificationsCount(): void
    {
        $this->exportNotificationsCount = auth()->user()?->notifications()
            ->where('type', \App\Notifications\ExportReadyNotification::class)
            ->whereNull('read_at')
            ->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_orders')
                ->label(fn() => 'Export Orders CSV' . ($this->exportNotificationsCount ? ' (' . $this->exportNotificationsCount . ' ready)' : ''))
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                    \Filament\Forms\Components\DatePicker::make('to')->label('To'),
                    \Filament\Forms\Components\Select::make('status')->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])->placeholder('Any Status'),
                ])
                ->action(function(array $data) {
                    if (!empty($data['from']) && !empty($data['to']) && $data['from'] > $data['to']) {
                        $this->dispatchBrowserEvent('notify', ['message' => 'Invalid date range (From > To)']);
                        return;
                    }
                    \App\Jobs\ExportOrdersJob::dispatch(auth()->id(), array_filter([
                        'from' => $data['from'] ?? null,
                        'to' => $data['to'] ?? null,
                        'status' => $data['status'] ?? null,
                    ]));
                    $this->dispatchBrowserEvent('notify', ['message' => 'Orders export queued']);
                }),
            \Filament\Actions\Action::make('generate_analytics_report')
                ->label('Generate Analytics Report')
                ->icon('heroicon-o-chart-bar')
                ->form([
                    \Filament\Forms\Components\Select::make('segment')->options([
                        'all' => 'All',
                        'revenue' => 'Revenue Focus',
                        'servers' => 'Servers',
                        'users' => 'Users',
                    ])->default('all'),
                ])
                ->action(function(array $data) {
                    $filters = [];
                    if (($data['segment'] ?? 'all') !== 'all') {
                        $filters['segment'] = $data['segment'];
                    }
                    \App\Jobs\GenerateAnalyticsReportJob::dispatch(auth()->id(), $filters);
                    $this->dispatchBrowserEvent('notify', ['message' => 'Analytics report queued']);
                }),
            \Filament\Actions\Action::make('recent_exports')
                ->label('Recent Exports')
                ->icon('heroicon-o-clock')
                ->action(fn() => null)
                ->modalHeading('Recent Exports')
                ->modalContent(view('filament.admin.partials.recent-exports', ['exports' => $this->recentExports]))
                ->modalSubmitAction(false),
        ];
    }
}
