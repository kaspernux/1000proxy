{{-- Inbound Traffic Monitor Component --}}
<div x-data="inboundTrafficMonitor()" class="traffic-monitor-container">
    <div class="traffic-monitor-header bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center space-x-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span>Traffic Monitor</span>
            </h3>

            <div class="flex items-center space-x-3">
                {{-- Time Range Selector --}}
                <select
                    x-model="timeRange"
                    @change="setTimeRange(timeRange)"
                    class="select-input select-sm"
                >
                    <option value="1h">Last Hour</option>
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>

                {{-- Auto Update Toggle --}}
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input
                        type="checkbox"
                        x-model="autoUpdate"
                        @change="toggleAutoUpdate()"
                        class="checkbox"
                    >
                    <span class="text-sm text-gray-600 dark:text-gray-400">Auto Update</span>
                </label>

                {{-- Refresh Button --}}
                <button
                    @click="loadTrafficData()"
                    :disabled="isLoading"
                    class="btn-secondary btn-sm flex items-center space-x-2"
                >
                    <svg
                        class="w-4 h-4"
                        :class="{ 'animate-spin': isLoading }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="isLoading" class="flex items-center justify-center py-12">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Loading traffic data...</span>
            </div>
        </div>

        {{-- Traffic Charts Grid --}}
        <div x-show="!isLoading" class="space-y-6">
            <template x-for="(data, inboundId) in trafficData" :key="inboundId">
                <div class="traffic-chart-card bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                            Inbound <span x-text="inboundId"></span>
                        </h4>

                        {{-- Traffic Summary --}}
                        <div class="flex items-center space-x-4 text-sm">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-gray-600 dark:text-gray-400">Upload:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="formatBytes(getTotalTraffic(inboundId).upload)"></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span class="text-gray-600 dark:text-gray-400">Download:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="formatBytes(getTotalTraffic(inboundId).download)"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Total: <span class="font-medium text-gray-900 dark:text-gray-100" x-text="formatBytes(getTotalTraffic(inboundId).total)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Chart Container --}}
                    <div class="chart-container" style="height: 300px;">
                        <canvas :id="`traffic-chart-${inboundId}`"></canvas>
                    </div>

                    {{-- Performance Metrics --}}
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="metric-card bg-white dark:bg-gray-800 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Average Upload Speed</h5>
                            <div class="text-lg font-bold text-green-600 dark:text-green-400" x-text="formatBytes(getAverageSpeed(inboundId).upload) + '/s'"></div>
                        </div>
                        <div class="metric-card bg-white dark:bg-gray-800 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Average Download Speed</h5>
                            <div class="text-lg font-bold text-blue-600 dark:text-blue-400" x-text="formatBytes(getAverageSpeed(inboundId).download) + '/s'"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- No Data State --}}
        <div x-show="!isLoading && Object.keys(trafficData).length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No traffic data available</h3>
            <p class="mt-1 text-sm text-gray-500">Traffic monitoring data will appear here once available.</p>
        </div>
    </div>

    {{-- Summary Statistics --}}
    <div x-show="!isLoading && Object.keys(trafficData).length > 0" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-800 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Upload</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            <span x-text="formatBytes(Object.values(trafficData).reduce((sum, data) => sum + getTotalTraffic(Object.keys(trafficData)[Object.values(trafficData).indexOf(data)]).upload, 0))"></span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-800 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Download</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            <span x-text="formatBytes(Object.values(trafficData).reduce((sum, data) => sum + getTotalTraffic(Object.keys(trafficData)[Object.values(trafficData).indexOf(data)]).download, 0))"></span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-800 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Inbounds</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            <span x-text="Object.keys(trafficData).length"></span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.traffic-chart-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.traffic-chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.metric-card {
    transition: transform 0.2s ease;
}

.metric-card:hover {
    transform: translateY(-1px);
}

.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.chart-container canvas {
    width: 100% !important;
    height: 100% !important;
}
</style>

{{-- Include Chart.js if not already included --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<script>
// Global helper function for formatting bytes
window.formatBytes = function(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};
</script>
@endpush
