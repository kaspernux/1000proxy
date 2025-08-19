<x-filament-panels::page>
<div class="fi-section-content-ctn">
    @php
        // Defensive normalization to avoid undefined index notices if upstream data is missing keys
        $analyticsData = is_array($analyticsData ?? null) ? $analyticsData : [];
        // Ensure overview subkeys have a 'value' field
        foreach (['total_revenue', 'new_customers', 'total_orders', 'active_servers'] as $k) {
            $node = data_get($analyticsData, "overview.$k", []);
            if (! is_array($node)) { $node = []; }
            if (! array_key_exists('value', $node)) { $node['value'] = 0; }
            // Preserve any existing fields
            data_set($analyticsData, "overview.$k", $node);
        }
        // Ensure performance is an array
        if (! is_array(data_get($analyticsData, 'performance'))) {
            $analyticsData['performance'] = [];
        }
        // Ensure segmentation is iterable array
        if (! is_array(data_get($analyticsData, 'segmentation'))) {
            $analyticsData['segmentation'] = [];
        }
    @endphp
    <div class="analytics-dashboard space-y-8">
        <!-- Header Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300">
                        <x-heroicon-o-chart-bar-square class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Analytics Overview</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Comprehensive business intelligence dashboard</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Time Range Selector -->
                    <select wire:model.live="timeRange"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm px-3 py-2">
                        <option value="24h">Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="90d">Last 90 Days</option>
                        <option value="1y">Last Year</option>
                    </select>

                    <!-- Metric Selector -->
                    <select wire:model.live="selectedMetric"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm px-3 py-2">
                        <option value="revenue">Revenue Focus</option>
                        <option value="users">User Analytics</option>
                        <option value="servers">Server Performance</option>
                        <option value="behavior">Customer Behavior</option>
                    </select>

                    <!-- Payment Method Filter -->
                    <select wire:model.live="paymentMethodFilter" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm px-3 py-2">
                        <option value="">All Methods</option>
                        <option value="stripe">Stripe</option>
                        <option value="paypal">PayPal</option>
                        <option value="mir">MIR</option>
                        <option value="nowpayments">Crypto</option>
                    </select>

                    <!-- Plan Filter -->
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" />
                        <input type="text" wire:model.live="planFilter" placeholder="Filter Plan"
                               class="pl-8 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm px-3 py-2" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Metrics -->
        @if(isset($analyticsData['overview']))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8 border-l-4 border-green-500 hover:shadow transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ data_get($analyticsData, 'overview.total_revenue.formatted', '$0.00') }}
                        </p>
                        <div class="flex items-center mt-2">
                            @if(data_get($analyticsData, 'overview.total_revenue.trend', 'up') === 'up')
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                <span class="text-sm text-green-500">+{{ data_get($analyticsData, 'overview.total_revenue.growth', 0) }}%</span>
                            @else
                                <x-heroicon-s-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                <span class="text-sm text-red-500">{{ data_get($analyticsData, 'overview.total_revenue.growth', 0) }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            <!-- New Customers -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8 border-l-4 border-blue-500 hover:shadow transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">New Customers</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format((int) data_get($analyticsData, 'overview.new_customers.value', 0)) }}
                        </p>
                        <div class="flex items-center mt-2">
                            @if(data_get($analyticsData, 'overview.new_customers.trend', 'up') === 'up')
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                <span class="text-sm text-green-500">+{{ data_get($analyticsData, 'overview.new_customers.growth', 0) }}%</span>
                            @else
                                <x-heroicon-s-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                <span class="text-sm text-red-500">{{ data_get($analyticsData, 'overview.new_customers.growth', 0) }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <x-heroicon-o-user-plus class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8 border-l-4 border-purple-500 hover:shadow transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Orders</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format((int) data_get($analyticsData, 'overview.total_orders.value', 0)) }}
                        </p>
                        <div class="flex items-center mt-2">
                            @if(data_get($analyticsData, 'overview.total_orders.trend', 'up') === 'up')
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                <span class="text-sm text-green-500">+{{ data_get($analyticsData, 'overview.total_orders.growth', 0) }}%</span>
                            @else
                                <x-heroicon-s-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                <span class="text-sm text-red-500">{{ data_get($analyticsData, 'overview.total_orders.growth', 0) }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                        <x-heroicon-o-shopping-cart class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>

            <!-- Server Utilization -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8 border-l-4 border-orange-500 hover:shadow transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Server Utilization</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ (float) data_get($analyticsData, 'overview.active_servers.utilization', 0) }}%
                        </p>
                        <div class="flex items-center mt-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ (int) data_get($analyticsData, 'overview.active_servers.clients', 0) }}/{{ (int) data_get($analyticsData, 'overview.active_servers.value', 0) * 100 }} clients
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Chart -->
            @if(isset($analyticsData['revenue']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                        <x-heroicon-o-currency-dollar class="w-5 h-5 text-emerald-500" /> Revenue Trends
                    </h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @php $revTotal = (float) data_get($analyticsData, 'revenue.total_period', 0); @endphp
                        Total: ${{ number_format($revTotal, 2) }}
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart" class="w-full h-full"></canvas>
                </div>
            </div>
            @endif

            <!-- User Analytics Chart -->
            @if(isset($analyticsData['users']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                        <x-heroicon-o-users class="w-5 h-5 text-blue-500" /> User Growth
                    </h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Activity Rate: {{ (int) data_get($analyticsData, 'users.activity_rate', 0) }}%
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="userChart" class="w-full h-full"></canvas>
                </div>
            </div>
            @endif
        </div>

        <!-- Detailed Analytics Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Customer Segmentation -->
            @if(isset($analyticsData['segmentation']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-chart-pie class="w-5 h-5 text-indigo-500" /> Customer Segments
                </h3>
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
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-red-500" /> Churn Analysis
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Churn Rate</span>
                        <span class="text-lg font-bold text-red-600">{{ (float) data_get($analyticsData, 'churn.churn_rate', 0) }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Retention Rate</span>
                        <span class="text-lg font-bold text-green-600">{{ (float) data_get($analyticsData, 'churn.retention_rate', 0) }}%</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">At Risk Customers</span>
                            <span class="font-medium">{{ (int) data_get($analyticsData, 'churn.at_risk_customers', 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Performance Metrics -->
            @if(isset($analyticsData['performance']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-cpu-chip class="w-5 h-5 text-orange-500" /> System Performance
                </h3>
                <div class="space-y-4">
                    @foreach(($analyticsData['performance'] ?? []) as $metric => $data)
                    @php
                        $val = is_array($data) ? (data_get($data, 'value') ?? null) : (is_object($data) ? ($data->value ?? null) : (is_numeric($data) ? $data : null));
                        $unit = is_array($data) ? (data_get($data, 'unit', '')) : (is_object($data) ? ($data->unit ?? '') : '');
                        $status = is_array($data) ? (data_get($data, 'status', 'good')) : (is_object($data) ? ($data->status ?? 'good') : 'good');
                        if ($val === null && is_array($data)) {
                            // Attempt to derive a number from a nested array (e.g., first numeric)
                            foreach ($data as $v) { if (is_numeric($v)) { $val = $v; break; } }
                        }
                        $val = $val ?? 0;
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ ucwords(str_replace('_', ' ', $metric)) }}
                        </span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium">{{ $val }}{{ $unit }}</span>
                            <div class="w-2 h-2 rounded-full {{ $status === 'good' ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Revenue Forecast -->
        @if(isset($analyticsData['forecasting']) && !empty($analyticsData['forecasting']['forecast']))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-purple-500" /> Revenue Forecast
                </h3>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Confidence:</span>
                    @php $conf = data_get($analyticsData, 'forecasting.confidence', 'medium'); @endphp
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        {{ $conf === 'high' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                           ($conf === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                        {{ ucfirst($conf) }}
                    </span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="forecastChart" class="w-full h-full"></canvas>
            </div>
        </div>
        @endif

        <!-- Geographic Analytics -->
    @php $geoLocations = collect(data_get($analyticsData, 'geographic.revenue_by_location', [])); @endphp
    @if($geoLocations->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-heroicon-o-globe-alt class="w-5 h-5 text-teal-500" /> Geographic Revenue Distribution
            </h3>
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
                        @foreach($geoLocations as $location)
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 lg:p-8">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-heroicon-o-heart class="w-5 h-5 text-rose-500" /> Customer Behavior Insights
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format((float) data_get($analyticsData, 'customer_behavior.avg_lifetime_value', 0), 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Avg Customer Lifetime Value</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ (float) data_get($analyticsData, 'customer_behavior.repeat_purchase_rate', 0) }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Repeat Purchase Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ (int) data_get($analyticsData, 'customer_behavior.peak_hour', 0) }}:00
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
                        labels: {!! json_encode(collect(data_get($analyticsData, 'revenue.daily_revenue', []))->pluck('formatted_date')) !!},
                        datasets: [{
                            label: 'Daily Revenue',
                            data: {!! json_encode(collect(data_get($analyticsData, 'revenue.daily_revenue', []))->pluck('revenue')) !!},
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
                        labels: {!! json_encode(collect(data_get($analyticsData, 'users.daily_registrations', []))->pluck('formatted_date')) !!},
                        datasets: [{
                            label: 'New Registrations',
                            data: {!! json_encode(collect(data_get($analyticsData, 'users.daily_registrations', []))->pluck('count')) !!},
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
                const hourData = {!! json_encode(data_get($analyticsData, 'customer_behavior.purchase_patterns', [])) !!};

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
