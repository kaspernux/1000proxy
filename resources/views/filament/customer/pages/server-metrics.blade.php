<x-filament-panels::page>
    @php($metrics = $this->getMetricsData())
    @php($performance = collect($metrics['performance'] ?? []))
    @php($usage = $metrics['usage'] ?? [])
    @php($upload = ($usage['data_transfer']['upload'] ?? 0))
    @php($download = ($usage['data_transfer']['download'] ?? 0))
    @php($totalTransfer = max(1, $upload + $download))
    @php($peakHours = collect($usage['peak_hours'] ?? []))
    <div class="fi-section-content-ctn space-y-24 py-12" wire:poll.360s="refreshMetrics">
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 space-y-24">
        <!-- Hero Header -->
        <div class="fi-section-header mb-4 pb-2">
            <div class="fi-section-header-wrapper">
                <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                    <div class="flex-1 min-w-0">
                        <h1 class="fi-section-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20 mr-3 flex-shrink-0">
                                    <x-heroicon-s-chart-bar class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                </div>
                                <span class="truncate">Server Metrics Dashboard</span>
                            </div>
                        </h1>
                        <p class="fi-section-header-description mt-2 text-sm text-gray-500 dark:text-gray-400 leading-6">
                            Real-time operational insights across your active infrastructure
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gradient Statistics Dashboard (Order Management Style) -->
    <div class="grid gap-8 md:gap-10 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 mb-20">
            <!-- Active Servers -->
            <x-filament::section class="bg-gradient-to-br from-primary-500 to-blue-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-8">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-server class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-primary-100">Active Servers</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">{{ $performance->count() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-primary-200 text-xs">
                            <x-heroicon-o-arrow-trending-up class="h-3 w-3 mr-1" /> Monitored now
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Average Uptime -->
            <x-filament::section class="bg-gradient-to-br from-success-500 to-emerald-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-8">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-check-circle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-success-100">Avg Uptime</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($performance->avg('uptime'),1) }}%</p>
                            </div>
                        </div>
                        <div class="flex items-center text-success-200 text-xs">
                            <div class="w-2 h-2 bg-success-200 rounded-full mr-1 animate-pulse"></div>
                            Reliability window
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Average Latency -->
            <x-filament::section class="bg-gradient-to-br from-warning-500 to-orange-500 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-8">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-bolt class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-warning-100">Avg Latency</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($performance->avg('avg_latency'),0) }}ms</p>
                            </div>
                        </div>
                        <div class="flex items-center text-warning-200 text-xs">
                            <x-heroicon-o-clock class="h-3 w-3 mr-1" /> Response time
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Data Used -->
            <x-filament::section class="bg-gradient-to-br from-purple-500 to-indigo-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-8">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-chart-bar class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-purple-100">Data Used</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format(($usage['total_bandwidth_used'] ?? 0)/1024,1) }}GB</p>
                            </div>
                        </div>
                        <div class="flex items-center text-purple-200 text-xs">
                            <x-heroicon-o-arrow-path class="h-3 w-3 mr-1" /> Total transfer
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

    <!-- Performance Metrics Row (mirroring order management secondary row) -->
    <div class="mt-8 mb-24">
        <div class="grid gap-8 md:gap-10 grid-cols-1 md:grid-cols-3">
                <!-- Peak Uptime -->
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-success-600" />
                            Peak Uptime
                        </div>
                    </x-slot>
                    <div class="p-6 md:p-8">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Best Server</span>
                            <x-filament::badge color="success" size="xs">{{ number_format($performance->max('uptime'),1) }}%</x-filament::badge>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-success-500 h-2 rounded-full" style="width: {{ min(100, number_format($performance->max('uptime'),1)) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Highest observed uptime</p>
                    </div>
                </x-filament::section>

                <!-- Latency Spread -->
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-bolt class="w-5 h-5 text-warning-600" />
                            Latency Spread
                        </div>
                    </x-slot>
                    <div class="p-6 md:p-8">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Worst</span>
                            <x-filament::badge color="warning" size="xs">{{ number_format($performance->max('avg_latency'),0) }}ms</x-filament::badge>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-warning-500 h-2 rounded-full" style="width: {{ min(100, ($performance->max('avg_latency')/ max(1,$performance->max('avg_latency'))) * 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Peak latency recorded</p>
                    </div>
                </x-filament::section>

                <!-- Transfer Last 24h -->
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-arrow-path class="w-5 h-5 text-primary-600" />
                            24h Transfer
                        </div>
                    </x-slot>
                    <div class="p-6 md:p-8">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Data</span>
                            <x-filament::badge color="primary" size="xs">{{ number_format(($usage['last_24h_transfer'] ?? 0)/1024,2) }}GB</x-filament::badge>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format(($usage['last_24h_transfer'] ?? 0)/1024,2) }}GB</p>
                            <p class="text-xs text-gray-500">Rolling window</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

    <!-- Time Range / Performance Overview -->
    <div class="bg-white py-16 dark:bg-gray-800 rounded-2xl p-10 md:p-12 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col space-y-10">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 p-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20">
                                <x-heroicon-o-chart-bar class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Performance Overview</h3>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-prose">
                            Comparative performance signals for the selected window. Switch ranges to analyze trend deltas.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['24h' => '24h', '7d' => '7d', '30d' => '30d', '90d' => '90d'] as $range => $label)
                            <button
                                wire:click="updateTimeRange('{{ $range }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition shadow-sm {{ $selectedTimeRange === $range ? 'bg-blue-600 text-white ring-1 ring-blue-500/50' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                            >{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <!-- Key metrics chips -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 p-4 md:p-5 rounded-xl bg-gray-50 dark:bg-gray-700/40">
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-700/30">
                        <span class="flex h-8 w-8 items-center justify-center rounded-md bg-green-100 dark:bg-green-900/30">
                            <x-heroicon-o-check-circle class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Avg Uptime</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($performance->avg('uptime'),1) }}%</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-700/30">
                        <span class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-100 dark:bg-amber-900/30">
                            <x-heroicon-o-bolt class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Avg Latency</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($performance->avg('avg_latency'),0) }}ms</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-700/30">
                        <span class="flex h-8 w-8 items-center justify-center rounded-md bg-purple-100 dark:bg-purple-900/30">
                            <x-heroicon-o-arrow-path class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Transfer</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(($usage['total_bandwidth_used'] ?? 0)/1024,1) }}GB</p>
                        </div>
                    </div>
                </div>
                <!-- Chart Placeholder Enhanced -->
                <div class="relative group rounded-2xl overflow-hidden pt-14 pb-6 px-5 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-850/40 border border-gray-200 dark:border-gray-700 shadow-sm">
                    <!-- Top toolbar -->
                    <div class="absolute inset-x-0 top-0 h-12 flex items-center justify-between px-5 bg-white/80 dark:bg-gray-900/60 backdrop-blur-sm border-b border-gray-200/80 dark:border-gray-700/70 z-20">
                        <div class="flex items-center gap-2 text-[11px] font-semibold tracking-wide text-gray-700 dark:text-gray-200">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-blue-100 dark:bg-blue-900/40">
                                <x-heroicon-o-bolt class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                            </span>
                            <span class="uppercase">Live Performance</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] px-2 py-1 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 text-white font-medium shadow-sm animate-pulse">LIVE</span>
                            <button type="button" wire:click="refreshMetrics" class="p-1.5 rounded-md bg-white/70 dark:bg-gray-800/70 backdrop-blur border border-gray-200 dark:border-gray-700 shadow hover:shadow-md hover:bg-white dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                <x-heroicon-o-arrow-path class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                            </button>
                        </div>
                    </div>
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute inset-0 opacity-40 mix-blend-overlay bg-[radial-gradient(circle_at_1px_1px,#e2e8f0_1px,transparent_0)] dark:bg-[radial-gradient(circle_at_1px_1px,#475569_1px,transparent_0)] [background-size:34px_34px]"></div>
                        <div class="absolute inset-0 bg-gradient-to-tr from-blue-500/5 via-transparent to-purple-500/10 animate-pulse [animation-duration:6s]"></div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-6 h-full">
                        <div class="flex-1 flex flex-col">
                            <!-- Aspect placeholder area (replace with real chart component) -->
                            <div class="relative flex-1 aspect-[16/9] md:aspect-auto md:h-72 rounded-xl bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 border border-dashed border-gray-300 dark:border-gray-600 overflow-hidden">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center space-y-1">
                                        <p class="text-gray-600 dark:text-gray-300 font-medium flex items-center justify-center gap-2">
                                            <x-heroicon-o-chart-bar class="w-5 h-5 text-blue-500" /> Data Visualization
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Real-time server performance</p>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500">(Chart component placeholder)</p>
                                    </div>
                                </div>
                                <!-- Simulated animated series (decorative) -->
                                <div class="absolute inset-x-0 bottom-0 flex items-end gap-1 px-3 pb-2 h-full">
                                    @for($i=0;$i<42;$i++)
                                        @php($h = rand(10,95))
                                        <span class="flex-1 relative bg-gradient-to-t from-blue-500/40 to-blue-400/60 dark:from-blue-600/30 dark:to-blue-400/50 rounded-sm origin-bottom scale-y-0 animate-[grow_1.2s_ease-out_forwards] [animation-delay:{{ $i * 40 }}ms]" style="--tw-scale-y: {{ $h/100 }}"></span>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <!-- Side mini stats -->
                        <div class="w-full md:w-56 flex md:flex-col gap-4 md:gap-5 justify-between">
                            <div class="flex-1 md:flex-none p-4 rounded-xl bg-white/70 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow transition">
                                <p class="text-[11px] font-medium tracking-wide text-gray-500 dark:text-gray-400">Uptime Range</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($performance->min('uptime'),1) }}% – {{ number_format($performance->max('uptime'),1) }}%</p>
                            </div>
                            <div class="flex-1 md:flex-none p-4 rounded-xl bg-white/70 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow transition">
                                <p class="text-[11px] font-medium tracking-wide text-gray-500 dark:text-gray-400">Latency Spread</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($performance->min('avg_latency'),0) }} – {{ number_format($performance->max('avg_latency'),0) }}ms</p>
                            </div>
                            <div class="flex-1 md:flex-none p-4 rounded-xl bg-white/70 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow transition">
                                <p class="text-[11px] font-medium tracking-wide text-gray-500 dark:text-gray-400">Data / Server</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $performance->count() ? number_format((($usage['total_bandwidth_used'] ?? 0)/1024)/$performance->count(),2) : '0.00' }}GB</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Legend -->
                <div class="flex flex-wrap items-center gap-x-6 gap-y-3 pt-8 mt-4 border-t border-gray-200 dark:border-gray-700 text-[11px] tracking-wide">
                    <div class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="h-2.5 w-2.5 rounded-sm bg-green-500 shadow-sm"></span> Uptime</div>
                    <div class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500 shadow-sm"></span> Latency</div>
                    <div class="flex items-center gap-1 text-gray-600 dark:text-gray-300"><span class="h-2.5 w-2.5 rounded-sm bg-blue-500 shadow-sm"></span> Transfer</div>
                    <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400"><span class="h-2.5 w-2.5 rounded-sm bg-purple-500 shadow-sm"></span> Derived</div>
                </div>
            </div>
        </div>

        <!-- Usage Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-14">
            <!-- Bandwidth Usage -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 md:p-10">
                <div class="flex items-start justify-between mb-6 p-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/30">
                            <x-heroicon-o-arrow-path class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </span>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bandwidth Usage</h3>
                    </div>
                    <span class="text-[11px] px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ number_format(($upload + $download)/1024,2) }}GB total</span>
                </div>
                <div class="space-y-5">
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs uppercase tracking-wide">
                            <span class="text-gray-500 dark:text-gray-400">Upload</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($upload / 1024, 2) }}GB ({{ number_format(($upload / $totalTransfer) * 100,1) }}%)</span>
                        </div>
                        <div class="relative h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-indigo-500" style="width: {{ ($upload / $totalTransfer) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs uppercase tracking-wide">
                            <span class="text-gray-500 dark:text-gray-400">Download</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($download / 1024, 2) }}GB ({{ number_format(($download / $totalTransfer) * 100,1) }}%)</span>
                        </div>
                        <div class="relative h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500" style="width: {{ ($download / $totalTransfer) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pt-2 text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-blue-500"></span> Upload</div>
                        <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-green-500"></span> Download</div>
                    </div>
                </div>
            </div>

            <!-- Peak Usage Hours -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 md:p-10">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/30">
                            <x-heroicon-o-clock class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </span>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Peak Usage Hours</h3>
                    </div>
                    <span class="text-[11px] px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Last 24h</span>
                </div>
                @php($bars = $peakHours->take(24))
                @if($bars->isNotEmpty())
                    <div class="flex items-end gap-1 h-32">
                        @foreach($bars as $hour => $val)
                            @php($pct = min(100, ($val['value'] ?? $val) / max(1, $bars->max('value')) * 100))
                            <div class="group relative flex-1">
                                <div class="w-full rounded-t-md bg-gradient-to-t from-amber-400 to-amber-200 dark:from-amber-600 dark:to-amber-400" style="height: {{ max(4,$pct) }}%"></div>
                                <div class="absolute -bottom-5 left-1/2 -translate-x-1/2 text-[10px] text-gray-400 dark:text-gray-500">{{ str_pad($hour,2,'0',STR_PAD_LEFT) }}</div>
                                <div class="absolute -top-6 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition text-[10px] px-1.5 py-0.5 rounded bg-gray-900 text-white">{{ number_format($val['value'] ?? $val) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="h-32 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <x-heroicon-o-clock class="w-8 h-8 text-gray-400 mx-auto mb-1" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">24-hour usage pattern</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">No data</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    <!-- Server Performance Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 md:p-8">
        <div class="px-6 md:px-8 py-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Server Performance Details</h3>
            </div>
            {{ $this->table }}
        </div>

        <!-- Alerts and Notifications -->
        @if(count($this->getMetricsData()['performance'] ?? []) > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-6 md:p-8">
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
        </div> <!-- /max-w wrapper -->
    </div>

    {{-- Removed JS polling; now using wire:poll.30s on the main container to avoid stacking intervals and perceived continuous refresh. --}}
</x-filament-panels::page>
