@extends('layouts.app')

@section('content')

<section class="min-h-screen bg-gradient-to-br from-blue-900 via-gray-900 to-indigo-900 py-8 px-2 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Header Section -->
        <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 bg-white/10 dark:bg-gray-900/80 shadow-2xl rounded-2xl px-6 py-8 border border-white/20">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">Analytics Dashboard</h1>
                <p class="mt-2 text-lg text-white/80">Comprehensive business intelligence and performance metrics</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Date Range Selector -->
                <div class="relative">
                    <select wire:model.live="dateRange"
                        class="appearance-none bg-white/80 dark:bg-gray-800/80 border border-white/20 rounded-lg px-4 py-2 pr-8 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($filters['date_ranges'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <!-- Metric Selector -->
                <div class="relative">
                    <select wire:model.live="selectedMetric"
                        class="appearance-none bg-white/80 dark:bg-gray-800/80 border border-white/20 rounded-lg px-4 py-2 pr-8 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($filters['metrics'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <button wire:click="refreshData"
                        class="inline-flex items-center px-4 py-2 border border-white/20 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
                        title="Refresh Data">
                        <svg wire:loading.remove wire:target="refreshData" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <svg wire:loading wire:target="refreshData" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="inline-flex items-center px-4 py-2 border border-white/20 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export
                        </button>
                        <div x-show="open" @click.away="open = false"
                            class="absolute right-0 mt-2 w-48 bg-white/90 dark:bg-gray-900/90 rounded-lg shadow-lg border border-white/20 z-10">
                            <div class="py-1">
                                <button wire:click="exportReport('pdf')"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-900 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-800">
                                    Export as PDF
                                </button>
                                <button wire:click="exportReport('excel')"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-900 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-800">
                                    Export as Excel
                                </button>
                                <button wire:click="exportReport('csv')"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-900 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-800">
                                    Export as CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Last Updated Info -->
        @if($lastUpdated)
        <div class="flex flex-col md:flex-row md:items-center md:justify-between text-sm text-white/80 border-b border-white/20 pb-4">
            <div class="flex items-center mb-2 md:mb-0">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Last updated: {{ $lastUpdated->diffForHumans() }}
            </div>
            <div class="flex items-center">
                <label class="inline-flex items-center">
                    <input type="checkbox" wire:model.live="autoRefresh" class="form-checkbox h-4 w-4 text-blue-400 bg-white/20 border-white/30">
                    <span class="ml-2 text-sm">Auto refresh ({{ $refreshInterval }}s)</span>
                </label>
            </div>
        </div>
        @endif

        <!-- Loading Overlay -->
        <div wire:loading.flex wire:target="loadAnalytics" class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center">
            <div class="bg-white/90 dark:bg-gray-900/90 rounded-xl p-8 flex items-center shadow-2xl">
                <svg class="animate-spin h-8 w-8 text-blue-600 mr-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-lg text-gray-900 dark:text-gray-200">Loading analytics...</span>
            </div>
        </div>

        <!-- KPI Cards -->
        <section aria-label="KPI Cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
            @foreach($kpis as $key => $kpi)
            <div class="bg-white/10 border border-white/20 rounded-xl shadow-2xl p-6 hover:shadow-xl transition-shadow cursor-pointer backdrop-blur-md"
                wire:click="drillDown('{{ $key }}')">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-white/80">{{ $kpi['title'] }}</p>
                        <p class="text-2xl font-bold text-white">{{ $kpi['value'] }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-{{ $kpi['color'] }}-500/80 rounded-full flex items-center justify-center">
                            @switch($kpi['icon'])
                                @case('currency-dollar')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    @break
                                @case('users')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    @break
                                @case('shopping-cart')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 11-4 0v-6m4 0V9a2 2 0 10-4 0v4.01"></path>
                                    </svg>
                                    @break
                                @default
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                            @endswitch
                        </div>
                    </div>
                </div>
                @if($kpi['change'] !== null)
                <div class="mt-4 flex items-center">
                    @if($kpi['trend'] === 'up')
                        <svg class="w-4 h-4 text-green-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="text-green-300 text-sm font-medium">+{{ number_format(abs($kpi['change']), 1) }}%</span>
                    @else
                        <svg class="w-4 h-4 text-red-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                        <span class="text-red-300 text-sm font-medium">{{ number_format($kpi['change'], 1) }}%</span>
                    @endif
                    <span class="text-white/60 text-sm ml-1">vs last period</span>
                </div>
                @endif
            </div>
            @endforeach
        </section>

        <!-- Charts Grid -->
        <section aria-label="Charts" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Trend Chart -->
            <div class="bg-white/10 border border-white/20 rounded-xl shadow-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Revenue Trend</h3>
                    <button class="text-white/60 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['revenue_trend'] ?? []));
                        "></canvas>
                </div>
            </div>
            <!-- User Growth Chart -->
            <div class="bg-white/10 border border-white/20 rounded-xl shadow-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">User Growth</h3>
                    <button class="text-white/60 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="userGrowthChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['user_growth'] ?? []));
                        "></canvas>
                </div>
            </div>
            <!-- Conversion Funnel Chart -->
            <div class="bg-white/10 border border-white/20 rounded-xl shadow-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Conversion Funnel</h3>
                    <button class="text-white/60 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="conversionChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['conversion_funnel'] ?? []));
                        "></canvas>
                </div>
            </div>
            <!-- Customer Segments Chart -->
            <div class="bg-white/10 border border-white/20 rounded-xl shadow-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Customer Segments</h3>
                    <button class="text-white/60 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="segmentsChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['customer_segments'] ?? []));
                        "></canvas>
                </div>
            </div>
        </section>

        <!-- Insights and Recommendations -->
        <section aria-label="Insights and Recommendations" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Key Insights -->
            <div class="bg-white/10 border border-white/20 rounded-xl shadow-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Key Insights</h3>
                <div class="space-y-4">
                    @foreach($insights as $insight)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-1">
                            @switch($insight['impact'])
                                @case('high')
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    @break
                                @case('medium')
                                    <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                    @break
                                @default
                                    <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            @endswitch
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-white">{{ $insight['title'] }}</h4>
                            <p class="text-sm text-white/80 mt-1">{{ $insight['description'] }}</p>
                            <p class="text-xs text-blue-300 mt-2 font-medium">{{ $insight['action'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <!-- Recommendations -->
            <div class="bg-white/10 border border-white/20 rounded-xl shadow-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Recommendations</h3>
                <div class="space-y-4">
                    @foreach($recommendations as $recommendation)
                    <div class="border border-white/20 rounded-lg p-4 bg-white/5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $recommendation['priority'] === 'high' ? 'bg-red-400/20 text-red-200' : 'bg-yellow-400/20 text-yellow-200' }}">
                                {{ ucfirst($recommendation['priority']) }} Priority
                            </span>
                            <span class="text-xs text-white/60">{{ $recommendation['category'] }}</span>
                        </div>
                        <h4 class="text-sm font-medium text-white mb-1">{{ $recommendation['title'] }}</h4>
                        <p class="text-sm text-white/80 mb-2">{{ $recommendation['description'] }}</p>
                        <div class="flex items-center justify-between text-xs text-white/60">
                            <span>Impact: {{ $recommendation['estimated_impact'] }}</span>
                            <span>Timeline: {{ $recommendation['timeline'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</section>

    <!-- Last Updated Info -->
    @if($lastUpdated)
    <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-4">
        <div class="flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Last updated: {{ $lastUpdated->diffForHumans() }}
        </div>

        <div class="flex items-center">
            <label class="inline-flex items-center">
                <input type="checkbox" wire:model.live="autoRefresh" class="form-checkbox h-4 w-4 text-blue-600">
                <span class="ml-2 text-sm">Auto refresh ({{ $refreshInterval }}s)</span>
            </label>
        </div>
    </div>
    @endif

    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="loadAnalytics" class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center">
            <svg class="animate-spin h-6 w-6 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700 dark:text-gray-300">Loading analytics...</span>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
        @foreach($kpis as $key => $kpi)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow cursor-pointer"
             wire:click="drillDown('{{ $key }}')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $kpi['title'] }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $kpi['value'] }}</p>
                </div>
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-{{ $kpi['color'] }}-100 dark:bg-{{ $kpi['color'] }}-900 rounded-full flex items-center justify-center">
                        @switch($kpi['icon'])
                            @case('currency-dollar')
                                <svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                @break
                            @case('users')
                                <svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                @break
                            @case('shopping-cart')
                                <svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 11-4 0v-6m4 0V9a2 2 0 10-4 0v4.01"></path>
                                </svg>
                                @break
                            @default
                                <svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                        @endswitch
                    </div>
                </div>
            </div>

            @if($kpi['change'] !== null)
            <div class="mt-4 flex items-center">
                @if($kpi['trend'] === 'up')
                    <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-green-600 text-sm font-medium">+{{ number_format(abs($kpi['change']), 1) }}%</span>
                @else
                    <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                    <span class="text-red-600 text-sm font-medium">{{ number_format($kpi['change'], 1) }}%</span>
                @endif
                <span class="text-gray-500 text-sm ml-1">vs last period</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Trend Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue Trend</h3>
                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="h-64">
                <canvas id="revenueChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['revenue_trend'] ?? []));
                        "></canvas>
            </div>
        </div>

        <!-- User Growth Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Growth</h3>
                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="h-64">
                <canvas id="userGrowthChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['user_growth'] ?? []));
                        "></canvas>
            </div>
        </div>

        <!-- Conversion Funnel Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Conversion Funnel</h3>
                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="h-64">
                <canvas id="conversionChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['conversion_funnel'] ?? []));
                        "></canvas>
            </div>
        </div>

        <!-- Customer Segments Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Customer Segments</h3>
                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="h-64">
                <canvas id="segmentsChart"
                        x-data
                        x-init="
                            const ctx = $el.getContext('2d');
                            new Chart(ctx, @js($charts['customer_segments'] ?? []));
                        "></canvas>
            </div>
        </div>
    </div>

    <!-- Insights and Recommendations -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Key Insights -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Key Insights</h3>
            <div class="space-y-4">
                @foreach($insights as $insight)
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 mt-1">
                        @switch($insight['impact'])
                            @case('high')
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                @break
                            @case('medium')
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                @break
                            @default
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        @endswitch
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $insight['title'] }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $insight['description'] }}</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-medium">{{ $insight['action'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Recommendations -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recommendations</h3>
            <div class="space-y-4">
                @foreach($recommendations as $recommendation)
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $recommendation['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                            {{ ucfirst($recommendation['priority']) }} Priority
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $recommendation['category'] }}</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ $recommendation['title'] }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $recommendation['description'] }}</p>
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Impact: {{ $recommendation['estimated_impact'] }}</span>
                        <span>Timeline: {{ $recommendation['timeline'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Auto-refresh functionality
    document.addEventListener('livewire:init', () => {
        let refreshInterval;

        Livewire.on('auto-refresh-toggled', (event) => {
            if (event.enabled) {
                refreshInterval = setInterval(() => {
                    @this.call('loadAnalytics');
                }, event.interval * 1000);
            } else {
                clearInterval(refreshInterval);
            }
        });

        Livewire.on('refresh-interval-changed', (event) => {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = setInterval(() => {
                    @this.call('loadAnalytics');
                }, event.interval * 1000);
            }
        });

        // Start auto-refresh if enabled
        @if($autoRefresh)
            refreshInterval = setInterval(() => {
                @this.call('loadAnalytics');
            }, {{ $refreshInterval }} * 1000);
        @endif
    });

    // Toast notifications
    Livewire.on('show-toast', (event) => {
        // Implement your preferred toast notification system
        console.log(event.message);
    });

    // Export functionality
    Livewire.on('download-report', (event) => {
        // Implement actual file download
        console.log('Downloading report:', event.filename);
    });
</script>
@endpush
@endsection
