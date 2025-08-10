<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Filament\Admin\Pages\AdminMainDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

class AdminDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dashboard_uses_curated_widget_set()
    {
        $dashboard = app(AdminMainDashboard::class);
        $ref = new ReflectionClass($dashboard);
        $method = $ref->getMethod('getWidgets');
        $method->setAccessible(true);
        $widgets = $method->invoke($dashboard);

        $expected = [
            \App\Filament\Widgets\AdminDashboardStatsWidget::class,
            \App\Filament\Widgets\InfrastructureHealthWidget::class,
            \App\Filament\Widgets\AdminChartsWidget::class,
            \App\Filament\Admin\Widgets\ServerOpsOverviewWidget::class,
            \App\Filament\Admin\Widgets\RevenueByMethodWidget::class,
            \App\Filament\Admin\Widgets\RevenueForecastWidget::class,
            \App\Filament\Admin\Widgets\OrderMetricsWidget::class,
            \App\Filament\Admin\Widgets\UserGrowthChartWidget::class,
            \App\Filament\Admin\Widgets\UserGrowthWidget::class,
            \App\Filament\Admin\Widgets\TrendsWidget::class,
            \App\Filament\Admin\Widgets\ChurnPredictionWidget::class,
            \App\Filament\Admin\Widgets\ProtocolUsageWidget::class,
            \App\Filament\Admin\Widgets\LocationPopularityWidget::class,
            \App\Filament\Admin\Widgets\RecommendationsWidget::class,
            \App\Filament\Admin\Widgets\PWAInstallationWidget::class,
        ];

        $this->assertSame($expected, $widgets, 'Widget list does not match curated ordering.');

        $removed = [
            'App\\Filament\\Admin\\Widgets\\RevenueOverviewWidget',
            'App\\Filament\\Admin\\Widgets\\RevenueChartWidget',
            // ServerPerformanceWidget & ServerUsageWidget also deprecated (moved to unified infra / location widgets)
            'App\\Filament\\Admin\\Widgets\\ServerPerformanceWidget',
            'App\\Filament\\Admin\\Widgets\\ServerUsageWidget',
        ];
        foreach ($removed as $class) {
            $this->assertNotContains($class, $widgets, $class.' should have been removed');
        }
    }
}
