<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminDashboardStatsWidget;
use App\Filament\Widgets\InfrastructureHealthWidget;
use App\Filament\Widgets\AdminChartsWidget;
use App\Services\Dashboard\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerClient;

class DashboardWidgetsCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Minimal seed data to populate metrics
        Order::factory()->create(['payment_status' => 'paid', 'grand_amount' => 123.45]);
        Server::factory()->create(['status' => 'up']);
        ServerClient::factory()->create();
    }

    public function test_admin_dashboard_stats_widget_revenue_cache_invalidation(): void
    {
        $widget = app(AdminDashboardStatsWidget::class);
        // Warm cache
        app(MetricsAggregator::class)->revenueSummary();
        $this->assertTrue(Cache::has('dash.revenue.summary'));
        // Trigger event handler
        $widget->handleRevenueRefresh();
        $this->assertFalse(Cache::has('dash.revenue.summary'), 'Revenue summary cache should be cleared');
    }

    public function test_admin_dashboard_stats_widget_server_cache_invalidation(): void
    {
        $widget = app(AdminDashboardStatsWidget::class);
        app(MetricsAggregator::class)->serverSummary();
        $this->assertTrue(Cache::has('dash.server.summary'));
        $widget->handleServerRefresh();
        $this->assertFalse(Cache::has('dash.server.summary'), 'Server summary cache should be cleared');
    }

    public function test_infrastructure_health_widget_cache_invalidation(): void
    {
        $widget = app(InfrastructureHealthWidget::class);
        app(MetricsAggregator::class)->serverSummary();
        app(MetricsAggregator::class)->serverTraffic();
        $this->assertTrue(Cache::has('dash.server.summary'));
        $this->assertTrue(Cache::has('dash.server.traffic'));
        $widget->handleServerEvent();
        $this->assertFalse(Cache::has('dash.server.summary'));
        $this->assertFalse(Cache::has('dash.server.traffic'));
    }

    public function test_admin_charts_widget_refresh_on_order_paid_event(): void
    {
        $widget = app(AdminChartsWidget::class);
        // getData uses queries directly; ensure calling $refresh path does not error
        $dataBefore = $this->invokeWidgetData($widget);
        $this->assertIsArray($dataBefore);
        // simulate new order then refresh
        Order::factory()->create(['payment_status' => 'paid', 'grand_amount' => 55]);
        // Force clear any aggregator caches possibly used elsewhere
        Cache::flush();
        $dataAfter = $this->invokeWidgetData($widget);
        $this->assertIsArray($dataAfter);
    }

    private function invokeWidgetData($widget): array
    {
        // Access protected getData via reflection
        $ref = new \ReflectionClass($widget);
        $method = $ref->getMethod('getData');
        $method->setAccessible(true);
        return $method->invoke($widget);
    }
}
