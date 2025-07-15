{{-- Dashboard Chart Component Template --}}
@php
    $chartType = $chartType ?? 'line';
    $title = $title ?? 'Chart';
    $height = $height ?? '400px';
    $autoRefresh = $autoRefresh ?? true;
    $theme = $theme ?? 'light';
    $realTimeData = $realTimeData ?? true;
@endphp

<div
    x-data="dashboardChart()"
    x-init="
        currentType = '{{ $chartType }}';
        theme = '{{ $theme }}';
        autoRefresh = {{ $autoRefresh ? 'true' : 'false' }};
        realTimeData = {{ $realTimeData ? 'true' : 'false' }};
        init();
    "
    {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm border border-gray-200']) }}
>
    {{-- Chart Header --}}
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
            <div x-show="loading" class="animate-spin h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full"></div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Chart Type Selector --}}
            <select
                x-model="currentType"
                @change="changeChartType(currentType)"
                class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <template x-for="(label, type) in chartTypes" :key="type">
                    <option :value="type" x-text="label"></option>
                </template>
            </select>

            {{-- Theme Toggle --}}
            <button
                @click="toggleTheme()"
                class="p-2 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md"
                title="Toggle theme"
            >
                <span x-show="theme === 'light'">🌙</span>
                <span x-show="theme === 'dark'">☀️</span>
            </button>

            {{-- Auto Refresh Toggle --}}
            <button
                @click="toggleAutoRefresh()"
                :class="autoRefresh ? 'text-green-600' : 'text-gray-400'"
                class="p-2 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md"
                title="Toggle auto refresh"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>

            {{-- Export Button --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    class="p-2 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md"
                    title="Export chart"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </button>

                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10"
                >
                    <button
                        @click="exportChart('png'); open = false"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Export as PNG
                    </button>
                    <button
                        @click="exportChart('jpg'); open = false"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Export as JPG
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart Container --}}
    <div class="p-6">
        <div class="relative" style="height: {{ $height }};">
            <canvas
                x-ref="chartCanvas"
                class="w-full h-full"
            ></canvas>

            {{-- Loading Overlay --}}
            <div
                x-show="loading"
                class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center"
            >
                <div class="text-center">
                    <div class="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                    <p class="text-sm text-gray-600">Loading chart data...</p>
                </div>
            </div>

            {{-- No Data State --}}
            <div
                x-show="!loading && (!chart || !chart.data || chart.data.datasets.length === 0)"
                class="absolute inset-0 flex items-center justify-center"
            >
                <div class="text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm">No data available</p>
                </div>
            </div>
        </div>

        {{-- Chart Status Bar --}}
        <div x-show="autoRefresh" class="mt-4 flex items-center justify-between text-xs text-gray-500">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span>Live updates enabled</span>
            </div>
            <div>
                <span>Next update in </span>
                <span x-text="Math.ceil((refreshInterval - (Date.now() % refreshInterval)) / 1000)"></span>
                <span>s</span>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js CDN (include in layout if not already included) --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

{{-- Example Usage in Comments:
<!--
<!-- Basic Chart -->
<x-ui.dashboard-chart
    title="Server Traffic"
    chart-type="line"
    height="300px"
    :auto-refresh="true"
    :real-time-data="true"
/>

<!-- Revenue Chart -->
<x-ui.dashboard-chart
    title="Revenue Analytics"
    chart-type="bar"
    theme="dark"
    height="400px"
    class="bg-gray-900 border-gray-700"
/>

<!-- Area Chart -->
<x-ui.dashboard-chart
    title="Bandwidth Usage"
    chart-type="area"
    :auto-refresh="false"
    height="250px"
/>
-->
--}}
