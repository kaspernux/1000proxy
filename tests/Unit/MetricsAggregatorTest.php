<?php

namespace Tests\Unit;

use App\Services\Dashboard\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

class MetricsAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_revenue_summary(): void
    {
        Order::factory()->create(['payment_status' => 'paid', 'grand_amount' => 100]);
        Order::factory()->create(['payment_status' => 'paid', 'grand_amount' => 50]);
        Cache::forget('dash.revenue.summary');
        $agg = app(MetricsAggregator::class);
        $summary = $agg->revenueSummary();
        $this->assertEquals(150, (int)$summary['total']);
    }

    public function test_server_summary_structure(): void
    {
        $agg = app(MetricsAggregator::class);
        $summary = $agg->serverSummary();
        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('online', $summary);
        $this->assertArrayHasKey('utilization', $summary);
    }

    public function test_active_connections_trend_structure(): void
    {
        $agg = app(MetricsAggregator::class);
        $trend = $agg->activeConnectionsTrend();
        $this->assertArrayHasKey('current', $trend);
        $this->assertArrayHasKey('chart', $trend);
    }
}
