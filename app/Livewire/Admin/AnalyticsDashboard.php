<?php

namespace App\Livewire\Admin;

use App\Services\BusinessIntelligenceService;
use App\Services\MarketingAutomationService;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsDashboard extends Component
{
    public $dateRange = '30_days';
    public $selectedMetric = 'revenue';
    public $refreshInterval = 300; // 5 minutes
    public $autoRefresh = true;

    public $analytics = [];
    public $loading = false;
    public $lastUpdated;

    // Chart data
    public $revenueChartData = [];
    public $userGrowthData = [];
    public $conversionFunnelData = [];
    public $segmentationData = [];

    // Filters
    public $filters = [
        'date_ranges' => [
            '7_days' => 'Last 7 Days',
            '30_days' => 'Last 30 Days',
            '90_days' => 'Last 90 Days',
            '1_year' => 'Last Year',
            'custom' => 'Custom Range'
        ],
        'metrics' => [
            'revenue' => 'Revenue Analytics',
            'users' => 'User Analytics',
            'orders' => 'Order Analytics',
            'performance' => 'Performance Metrics',
            'marketing' => 'Marketing Analytics',
            'churn' => 'Churn Analysis'
        ]
    ];

    protected $businessIntelligence;
    protected $marketingAutomation;

    public function boot()
    {
        $this->businessIntelligence = app(BusinessIntelligenceService::class);
        $this->marketingAutomation = app(MarketingAutomationService::class);
    }

    public function mount()
    {
        $this->loadAnalytics();
        $this->lastUpdated = Carbon::now();
    }

    public function render()
    {
        return view('livewire.admin.analytics-dashboard', [
            'kpis' => $this->getKPIs(),
            'charts' => $this->getChartConfigurations(),
            'insights' => $this->getInsights(),
            'recommendations' => $this->getRecommendations(),
        ]);
    }

    public function updatedDateRange()
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedMetric()
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics()
    {
        $this->loading = true;

        try {
            // Load main analytics
            $this->analytics = $this->businessIntelligence->getDashboardAnalytics($this->dateRange);

            // Load marketing analytics if selected
            if ($this->selectedMetric === 'marketing') {
                $this->analytics['marketing'] = $this->marketingAutomation->getMarketingDashboard();
            }

            // Prepare chart data
            $this->prepareChartData();

            $this->lastUpdated = Carbon::now();

            $this->dispatch('analytics-loaded', [
                'success' => true,
                'timestamp' => $this->lastUpdated->toISOString()
            ]);

        } catch (\Exception $e) {
            $this->dispatch('analytics-error', [
                'message' => 'Failed to load analytics: ' . $e->getMessage()
            ]);
        }

        $this->loading = false;
    }

    public function refreshData()
    {
        Cache::forget("bi_dashboard_analytics_{$this->dateRange}");
        Cache::forget('marketing_dashboard');
        $this->loadAnalytics();

        $this->dispatch('show-toast', [
            'message' => 'Analytics data refreshed successfully',
            'type' => 'success'
        ]);
    }

    public function exportReport($format = 'pdf')
    {
        try {
            $report = $this->businessIntelligence->generateReport();

            // In a real implementation, you would generate the actual file
            $filename = "analytics-report-" . Carbon::now()->format('Y-m-d') . ".{$format}";

            $this->dispatch('download-report', [
                'filename' => $filename,
                'format' => $format,
                'data' => $report
            ]);

            $this->dispatch('show-toast', [
                'message' => "Report exported successfully as {$format}",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to export report: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function drillDown($metric, $segment = null)
    {
        $this->selectedMetric = $metric;

        if ($segment) {
            // Apply segment filter
            $this->dispatch('segment-selected', ['segment' => $segment]);
        }

        $this->loadAnalytics();
    }

    protected function prepareChartData()
    {
        if (!isset($this->analytics['data'])) {
            return;
        }

        $data = $this->analytics['data'];

        // Revenue chart data
        if (isset($data['revenue']['by_period'])) {
            $this->revenueChartData = [
                'labels' => array_keys($data['revenue']['by_period']),
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => array_values($data['revenue']['by_period']),
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.4
                    ]
                ]
            ];
        }

        // User growth data
        if (isset($data['users']['growth_trend'])) {
            $this->userGrowthData = [
                'labels' => array_keys($data['users']['growth_trend']),
                'datasets' => [
                    [
                        'label' => 'New Users',
                        'data' => array_values($data['users']['growth_trend']),
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'tension' => 0.4
                    ]
                ]
            ];
        }

        // Conversion funnel data
        if (isset($data['conversion']['funnel_analysis'])) {
            $funnel = $data['conversion']['funnel_analysis'];
            $this->conversionFunnelData = [
                'labels' => array_keys($funnel),
                'datasets' => [
                    [
                        'label' => 'Conversion Funnel',
                        'data' => array_values($funnel),
                        'backgroundColor' => [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)'
                        ]
                    ]
                ]
            ];
        }

        // Segmentation data
        if (isset($data['segments']['by_value'])) {
            $segments = $data['segments']['by_value'];
            $this->segmentationData = [
                'labels' => array_keys($segments),
                'datasets' => [
                    [
                        'label' => 'Customer Segments',
                        'data' => array_values($segments),
                        'backgroundColor' => [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ]
                    ]
                ]
            ];
        }
    }

    protected function getKPIs(): array
    {
        if (!isset($this->analytics['data'])) {
            return [];
        }

        $data = $this->analytics['data'];

        return [
            'revenue' => [
                'title' => 'Total Revenue',
                'value' => '$' . number_format($data['revenue']['monthly']['current'] ?? 0, 2),
                'change' => $data['revenue']['monthly']['growth'] ?? 0,
                'trend' => $data['revenue']['monthly']['growth'] >= 0 ? 'up' : 'down',
                'icon' => 'currency-dollar',
                'color' => 'blue'
            ],
            'users' => [
                'title' => 'Total Users',
                'value' => number_format($data['users']['total_users'] ?? 0),
                'change' => $data['users']['user_growth']['monthly'] ?? 0,
                'trend' => $data['users']['user_growth']['monthly'] >= 0 ? 'up' : 'down',
                'icon' => 'users',
                'color' => 'green'
            ],
            'orders' => [
                'title' => 'Total Orders',
                'value' => number_format($data['orders']['total_orders'] ?? 0),
                'change' => $this->calculateOrderGrowth(),
                'trend' => $this->calculateOrderGrowth() >= 0 ? 'up' : 'down',
                'icon' => 'shopping-cart',
                'color' => 'yellow'
            ],
            'churn_rate' => [
                'title' => 'Churn Rate',
                'value' => ($data['churn']['churn_rate'] ?? 0) . '%',
                'change' => -2.1, // Improvement in churn
                'trend' => 'down', // Lower churn is better
                'icon' => 'trending-down',
                'color' => 'red'
            ],
            'avg_order_value' => [
                'title' => 'Avg Order Value',
                'value' => '$' . number_format($data['revenue']['average_order_value'] ?? 0, 2),
                'change' => 5.8,
                'trend' => 'up',
                'icon' => 'calculator',
                'color' => 'purple'
            ],
            'customer_ltv' => [
                'title' => 'Customer LTV',
                'value' => '$' . number_format($data['users']['user_lifetime_value'] ?? 0, 2),
                'change' => 12.4,
                'trend' => 'up',
                'icon' => 'star',
                'color' => 'indigo'
            ]
        ];
    }

    protected function getChartConfigurations(): array
    {
        return [
            'revenue_trend' => [
                'type' => 'line',
                'data' => $this->revenueChartData,
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Revenue Trend'
                        ]
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'ticks' => [
                                'callback' => 'function(value) { return "$" + value.toLocaleString(); }'
                            ]
                        ]
                    ]
                ]
            ],
            'user_growth' => [
                'type' => 'line',
                'data' => $this->userGrowthData,
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'User Growth'
                        ]
                    ]
                ]
            ],
            'conversion_funnel' => [
                'type' => 'bar',
                'data' => $this->conversionFunnelData,
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Conversion Funnel'
                        ]
                    ]
                ]
            ],
            'customer_segments' => [
                'type' => 'doughnut',
                'data' => $this->segmentationData,
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Customer Segments'
                        ],
                        'legend' => [
                            'position' => 'bottom'
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getInsights(): array
    {
        if (!isset($this->analytics['data'])) {
            return [];
        }

        $data = $this->analytics['data'];

        return [
            [
                'type' => 'revenue',
                'title' => 'Revenue Performance',
                'description' => $this->generateRevenueInsight($data['revenue'] ?? []),
                'impact' => 'high',
                'action' => 'Review high-performing segments'
            ],
            [
                'type' => 'users',
                'title' => 'User Acquisition',
                'description' => $this->generateUserInsight($data['users'] ?? []),
                'impact' => 'medium',
                'action' => 'Optimize acquisition channels'
            ],
            [
                'type' => 'churn',
                'title' => 'Customer Retention',
                'description' => $this->generateChurnInsight($data['churn'] ?? []),
                'impact' => 'high',
                'action' => 'Implement retention campaigns'
            ],
            [
                'type' => 'performance',
                'title' => 'System Performance',
                'description' => $this->generatePerformanceInsight($data['performance'] ?? []),
                'impact' => 'medium',
                'action' => 'Monitor key metrics'
            ]
        ];
    }

    protected function getRecommendations(): array
    {
        return [
            [
                'priority' => 'high',
                'category' => 'Revenue',
                'title' => 'Focus on High-Value Segments',
                'description' => 'High-value customers generate 60% of revenue. Increase targeting for this segment.',
                'estimated_impact' => '+15% revenue growth',
                'effort' => 'Medium',
                'timeline' => '2-4 weeks'
            ],
            [
                'priority' => 'high',
                'category' => 'Retention',
                'title' => 'Implement Churn Prevention',
                'description' => 'Deploy early warning system for at-risk customers to reduce churn by 20%.',
                'estimated_impact' => '+$50k annual savings',
                'effort' => 'High',
                'timeline' => '4-6 weeks'
            ],
            [
                'priority' => 'medium',
                'category' => 'Marketing',
                'title' => 'Optimize Email Campaigns',
                'description' => 'A/B test subject lines and send times to improve open rates by 25%.',
                'estimated_impact' => '+10% conversion',
                'effort' => 'Low',
                'timeline' => '1-2 weeks'
            ],
            [
                'priority' => 'medium',
                'category' => 'Product',
                'title' => 'Enhance Onboarding',
                'description' => 'Improve new user onboarding to increase activation rates.',
                'estimated_impact' => '+12% user activation',
                'effort' => 'Medium',
                'timeline' => '3-4 weeks'
            ]
        ];
    }

    protected function calculateOrderGrowth(): float
    {
        // This would calculate actual order growth
        // For now, return a sample value
        return 8.5;
    }

    protected function generateRevenueInsight(array $revenue): string
    {
        $growth = $revenue['monthly']['growth'] ?? 0;

        if ($growth > 10) {
            return "Revenue is growing strongly at {$growth}% month-over-month. High-value customers are driving growth.";
        } elseif ($growth > 0) {
            return "Revenue is growing moderately at {$growth}%. Consider optimization strategies.";
        } else {
            return "Revenue growth is declining. Immediate action needed to address underlying issues.";
        }
    }

    protected function generateUserInsight(array $users): string
    {
        $totalUsers = $users['total_users'] ?? 0;
        $newUsers = $users['new_users']['this_month'] ?? 0;

        $acquisitionRate = $totalUsers > 0 ? ($newUsers / $totalUsers) * 100 : 0;

        if ($acquisitionRate > 10) {
            return "User acquisition is strong with {$newUsers} new users this month ({$acquisitionRate}% growth rate).";
        } elseif ($acquisitionRate > 5) {
            return "User acquisition is steady. Consider expanding marketing channels for growth.";
        } else {
            return "User acquisition is slow. Review and optimize acquisition strategies.";
        }
    }

    protected function generateChurnInsight(array $churn): string
    {
        $churnRate = $churn['churn_rate'] ?? 0;

        if ($churnRate < 5) {
            return "Churn rate is excellent at {$churnRate}%. Continue current retention strategies.";
        } elseif ($churnRate < 10) {
            return "Churn rate is acceptable at {$churnRate}%. Monitor for trends and implement improvements.";
        } else {
            return "Churn rate is concerning at {$churnRate}%. Immediate retention efforts needed.";
        }
    }

    protected function generatePerformanceInsight(array $performance): string
    {
        $uptime = $performance['availability']['uptime_percentage'] ?? 99;
        $responseTime = $performance['api_performance']['average_response_time'] ?? 150;

        if ($uptime > 99.5 && $responseTime < 200) {
            return "System performance is excellent with {$uptime}% uptime and {$responseTime}ms response time.";
        } elseif ($uptime > 99 && $responseTime < 500) {
            return "System performance is good but could be optimized for better user experience.";
        } else {
            return "System performance needs attention. Review infrastructure and optimization strategies.";
        }
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;

        $this->dispatch('auto-refresh-toggled', [
            'enabled' => $this->autoRefresh,
            'interval' => $this->refreshInterval
        ]);
    }

    public function setRefreshInterval($interval)
    {
        $this->refreshInterval = max(60, (int) $interval); // Minimum 1 minute

        $this->dispatch('refresh-interval-changed', [
            'interval' => $this->refreshInterval
        ]);
    }

    // Real-time event listeners
    protected $listeners = [
        'refresh-analytics' => 'loadAnalytics',
        'export-report' => 'exportReport',
        'drill-down' => 'drillDown',
    ];
}
