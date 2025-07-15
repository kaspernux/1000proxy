<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-heroicon-o-server class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Servers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($this->getMetricsData()['performance'] ?? []) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-heroicon-o-signal class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Uptime</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format(collect($this->getMetricsData()['performance'] ?? [])->avg('uptime'), 1) }}%
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <x-heroicon-o-bolt class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Latency</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format(collect($this->getMetricsData()['performance'] ?? [])->avg('avg_latency'), 0) }}ms
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-heroicon-o-chart-bar class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Data Used</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format(($this->getMetricsData()['usage']['total_bandwidth_used'] ?? 0) / 1024, 1) }}GB
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Range Selector -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Performance Overview</h3>
                <div class="flex space-x-2">
                    @foreach(['24h' => '24 Hours', '7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days'] as $range => $label)
                        <button
                            wire:click="updateTimeRange('{{ $range }}')"
                            class="px-3 py-1 text-sm rounded-lg {{ $selectedTimeRange === $range ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="h-64 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <div class="text-center">
                    <x-heroicon-o-chart-bar class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                    <p class="text-gray-500 dark:text-gray-400">Performance Chart</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Real-time server performance visualization</p>
                </div>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Bandwidth Usage -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Bandwidth Usage</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Upload</span>
                            <span class="font-medium">{{ number_format(($this->getMetricsData()['usage']['data_transfer']['upload'] ?? 0) / 1024, 2) }}GB</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: 35%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Download</span>
                            <span class="font-medium">{{ number_format(($this->getMetricsData()['usage']['data_transfer']['download'] ?? 0) / 1024, 2) }}GB</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 65%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Peak Usage Hours -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Peak Usage Hours</h3>
                <div class="h-32 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                    <div class="text-center">
                        <x-heroicon-o-clock class="w-8 h-8 text-gray-400 mx-auto mb-1" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">24-hour usage pattern</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Server Performance Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Server Performance Details</h3>
            </div>
            {{ $this->table }}
        </div>

        <!-- Alerts and Notifications -->
        @if(count($this->getMetricsData()['performance'] ?? []) > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-400" />
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Performance Alert</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            Some servers are experiencing higher than normal latency. Consider switching to alternative locations for better performance.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            // Auto-refresh metrics every 30 seconds
            setInterval(() => {
                @this.call('refreshMetrics');
            }, 30000);
        });
    </script>
    @endpush
</x-filament-panels::page>
