<x-filament-panels::page>
    <div class="analytics-dashboard">
        <!-- Header Controls -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Analytics Overview</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Comprehensive business intelligence dashboard</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Time Range Selector -->
                    <select wire:model.live="timeRange"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                        <option value="24h">Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="90d">Last 90 Days</option>
                        <option value="1y">Last Year</option>
                    </select>

                    <!-- Metric Selector -->
                    <select wire:model.live="selectedMetric"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                        <option value="revenue">Revenue Focus</option>
                        <option value="users">User Analytics</option>
                        <option value="servers">Server Performance</option>
                        <option value="behavior">Customer Behavior</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Overview Metrics -->
        @if(isset($analyticsData['overview']))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $analyticsData['overview']['total_revenue']['formatted'] }}
                        </p>
                        <div class="flex items-center mt-2">
                            @if($analyticsData['overview']['total_revenue']['trend'] === 'up')
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                <span class="text-sm text-green-500">+{{ $analyticsData['overview']['total_revenue']['growth'] }}%</span>
                            @else
                                <x-heroicon-s-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                <span class="text-sm text-red-500">{{ $analyticsData['overview']['total_revenue']['growth'] }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            <!-- New Customers -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">New Customers</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($analyticsData['overview']['new_customers']['value']) }}
                        </p>
                        <div class="flex items-center mt-2">
                            @if($analyticsData['overview']['new_customers']['trend'] === 'up')
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                <span class="text-sm text-green-500">+{{ $analyticsData['overview']['new_customers']['growth'] }}%</span>
                            @else
                                <x-heroicon-s-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                <span class="text-sm text-red-500">{{ $analyticsData['overview']['new_customers']['growth'] }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <x-heroicon-o-user-plus class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Orders</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($analyticsData['overview']['total_orders']['value']) }}
                        </p>
                        <div class="flex items-center mt-2">
                            @if($analyticsData['overview']['total_orders']['trend'] === 'up')
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                <span class="text-sm text-green-500">+{{ $analyticsData['overview']['total_orders']['growth'] }}%</span>
                            @else
                                <x-heroicon-s-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                <span class="text-sm text-red-500">{{ $analyticsData['overview']['total_orders']['growth'] }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                        <x-heroicon-o-shopping-cart class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>

            <!-- Server Utilization -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Server Utilization</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $analyticsData['overview']['active_servers']['utilization'] }}%
                        </p>
                        <div class="flex items-center mt-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $analyticsData['overview']['active_servers']['clients'] }}/{{ $analyticsData['overview']['active_servers']['value'] * 100 }} clients
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-full">
                        <x-heroicon-o-server class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Revenue Chart -->
            @if(isset($analyticsData['revenue']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue Trends</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Total: ${{ number_format($analyticsData['revenue']['total_period'], 2) }}
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart" class="w-full h-full"></canvas>
                </div>
            </div>
            @endif

            <!-- User Analytics Chart -->
            @if(isset($analyticsData['users']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Growth</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Activity Rate: {{ $analyticsData['users']['activity_rate'] }}%
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="userChart" class="w-full h-full"></canvas>
                </div>
            </div>
            @endif
        </div>

        <!-- Detailed Analytics Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Customer Segmentation -->
            @if(isset($analyticsData['segmentation']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Segments</h3>
                <div class="space-y-3">
                    @foreach($analyticsData['segmentation'] as $segment => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $segment }}</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Churn Analysis -->
            @if(isset($analyticsData['churn']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Churn Analysis</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Churn Rate</span>
                        <span class="text-lg font-bold text-red-600">{{ $analyticsData['churn']['churn_rate'] }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Retention Rate</span>
                        <span class="text-lg font-bold text-green-600">{{ $analyticsData['churn']['retention_rate'] }}%</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">At Risk Customers</span>
                            <span class="font-medium">{{ $analyticsData['churn']['at_risk_customers'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Performance Metrics -->
            @if(isset($analyticsData['performance']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Performance</h3>
                <div class="space-y-4">
                    @foreach($analyticsData['performance'] as $metric => $data)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ ucwords(str_replace('_', ' ', $metric)) }}
                        </span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium">{{ $data['value'] }}{{ $data['unit'] }}</span>
                            <div class="w-2 h-2 rounded-full {{ $data['status'] === 'good' ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Revenue Forecast -->
        @if(isset($analyticsData['forecasting']) && !empty($analyticsData['forecasting']['forecast']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue Forecast</h3>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Confidence:</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        {{ $analyticsData['forecasting']['confidence'] === 'high' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                           ($analyticsData['forecasting']['confidence'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                        {{ ucfirst($analyticsData['forecasting']['confidence']) }}
                    </span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="forecastChart" class="w-full h-full"></canvas>
            </div>
        </div>
        @endif

        <!-- Geographic Analytics -->
        @if(isset($analyticsData['geographic']) && $analyticsData['geographic']['revenue_by_location']->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Geographic Revenue Distribution</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Location
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Revenue
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Orders
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Avg Order Value
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                        @foreach($analyticsData['geographic']['revenue_by_location'] as $location)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $location->location }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                ${{ number_format($location->revenue, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $location->order_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                ${{ number_format($location->order_count > 0 ? $location->revenue / $location->order_count : 0, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Customer Behavior Insights -->
        @if(isset($analyticsData['customer_behavior']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Behavior Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format($analyticsData['customer_behavior']['avg_lifetime_value'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Avg Customer Lifetime Value</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $analyticsData['customer_behavior']['repeat_purchase_rate'] }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Repeat Purchase Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $analyticsData['customer_behavior']['peak_hour'] }}:00
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Peak Purchase Hour</div>
                </div>
            </div>
            <div class="mt-6">
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">Purchase Patterns by Hour</h4>
                <div class="h-32">
                    <canvas id="behaviorChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#E5E7EB' : '#374151';
            const gridColor = isDark ? '#374151' : '#E5E7EB';

            // Revenue Chart
            @if(isset($analyticsData['revenue']))
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(collect($analyticsData['revenue']['daily_revenue'])->pluck('formatted_date')) !!},
                        datasets: [{
                            label: 'Daily Revenue',
                            data: {!! json_encode(collect($analyticsData['revenue']['daily_revenue'])->pluck('revenue')) !!},
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: textColor }
                            }
                        },
                        scales: {
                            y: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }
            @endif

            // User Growth Chart
            @if(isset($analyticsData['users']))
            const userCtx = document.getElementById('userChart');
            if (userCtx) {
                new Chart(userCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(collect($analyticsData['users']['daily_registrations'])->pluck('formatted_date')) !!},
                        datasets: [{
                            label: 'New Registrations',
                            data: {!! json_encode(collect($analyticsData['users']['daily_registrations'])->pluck('count')) !!},
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: '#3B82F6',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: textColor }
                            }
                        },
                        scales: {
                            y: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }
            @endif

            // Forecast Chart
            @if(isset($analyticsData['forecasting']) && !empty($analyticsData['forecasting']['forecast']))
            const forecastCtx = document.getElementById('forecastChart');
            if (forecastCtx) {
                new Chart(forecastCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(collect($analyticsData['forecasting']['forecast'])->pluck('formatted_date')) !!},
                        datasets: [{
                            label: 'Predicted Revenue',
                            data: {!! json_encode(collect($analyticsData['forecasting']['forecast'])->pluck('predicted_revenue')) !!},
                            borderColor: '#8B5CF6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderDash: [5, 5],
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: textColor }
                            }
                        },
                        scales: {
                            y: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }
            @endif

            // Customer Behavior Chart
            @if(isset($analyticsData['customer_behavior']))
            const behaviorCtx = document.getElementById('behaviorChart');
            if (behaviorCtx) {
                const hourLabels = Array.from({length: 24}, (_, i) => i + ':00');
                const hourData = {!! json_encode($analyticsData['customer_behavior']['purchase_patterns']) !!};

                new Chart(behaviorCtx, {
                    type: 'line',
                    data: {
                        labels: hourLabels,
                        datasets: [{
                            label: 'Purchases by Hour',
                            data: Object.values(hourData),
                            borderColor: '#F59E0B',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: textColor }
                            }
                        },
                        scales: {
                            y: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }
            @endif
        });
    </script>
    @endpush
</x-filament-panels::page>
