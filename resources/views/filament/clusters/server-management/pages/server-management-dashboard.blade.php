<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                        <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Admin Actions</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Run bulk health checks and refresh the dashboard</p>
                    </div>
                </div>
            </div>
            <div class="p-6 text-sm text-gray-600 dark:text-gray-400">
                Use the page header actions above to run health checks or provision new servers.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative"
         @if(!$pauseLive) wire:poll.keep-alive.{{ max(5, (int)$pollIntervalSec) }}s="refreshLiveData" @endif>
            <div class="absolute -top-6 right-0 text-xs text-gray-500 dark:text-gray-400" wire:loading.delay.shortest wire:target="refreshLiveData">Refreshing…</div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg" wire:key="kpi-total-servers">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-server-stack class="h-8 w-8 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Servers
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    <span wire:loading.remove wire:target="refreshLiveData">{{ $summary['total_servers'] }}</span>
                                    <span class="inline-flex items-center text-gray-400" wire:loading wire:target="refreshLiveData">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 animate-spin"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v2a.75.75 0 01-1.5 0v-2A.75.75 0 0112 2.25zm5.657 2.093a.75.75 0 011.06 1.06l-1.414 1.415a.75.75 0 01-1.06-1.06l1.414-1.415zM21 12a9 9 0 11-9-9 .75.75 0 010 1.5A7.5 7.5 0 1019.5 12a.75.75 0 011.5 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg" wire:key="kpi-healthy-servers">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-heart class="h-8 w-8 text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Healthy Servers
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    <span wire:loading.remove wire:target="refreshLiveData">{{ $summary['healthy_servers'] }} / {{ $summary['active_servers'] }}</span>
                                    <span class="inline-flex items-center text-gray-400" wire:loading wire:target="refreshLiveData">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 animate-spin"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v2a.75.75 0 01-1.5 0v-2A.75.75 0 0112 2.25zm5.657 2.093a.75.75 0 011.06 1.06l-1.414 1.415a.75.75 0 01-1.06-1.06l1.414-1.415zM21 12a9 9 0 11-9-9 .75.75 0 010 1.5A7.5 7.5 0 1019.5 12a.75.75 0 011.5 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg" wire:key="kpi-active-clients">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-users class="h-8 w-8 text-blue-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Active Clients
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    <span wire:loading.remove wire:target="refreshLiveData">{{ number_format($summary['total_clients']) }}</span>
                                    <span class="inline-flex items-center text-gray-400" wire:loading wire:target="refreshLiveData">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 animate-spin"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v2a.75.75 0 01-1.5 0v-2A.75.75 0 0112 2.25zm5.657 2.093a.75.75 0 011.06 1.06l-1.414 1.415a.75.75 0 01-1.06-1.06l1.414-1.415zM21 12a9 9 0 11-9-9 .75.75 0 010 1.5A7.5 7.5 0 1019.5 12a.75.75 0 011.5 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg" wire:key="kpi-avg-response">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-clock class="h-8 w-8 text-purple-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Avg Response Time
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    <span wire:loading.remove wire:target="refreshLiveData">{{ number_format($summary['average_response_time'], 0) }}ms</span>
                                    <span class="inline-flex items-center text-gray-400" wire:loading wire:target="refreshLiveData">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 animate-spin"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v2a.75.75 0 01-1.5 0v-2A.75.75 0 0112 2.25zm5.657 2.093a.75.75 0 011.06 1.06l-1.414 1.415a.75.75 0 01-1.06-1.06l1.414-1.415zM21 12a9 9 0 11-9-9 .75.75 0 010 1.5A7.5 7.5 0 1019.5 12a.75.75 0 011.5 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-4 sm:p-6">
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600"
                               wire:model.live="pauseLive">
                        Pause Live Updates
                    </label>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <span>Poll every</span>
                        <input type="number" min="5" step="5" class="w-20 rounded-md bg-white/70 dark:bg-gray-800/70 border-gray-300 dark:border-gray-600 text-sm"
                               wire:model.lazy="pollIntervalSec">
                        <span>seconds</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 max-h-[32rem] overflow-y-auto" @if(!$pauseLive) wire:poll.keep-alive.45s="refreshLiveData" @endif>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Status & Geographic Distribution
                        </h3>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="hidden sm:inline">Online now (est.):</span>
                            <span class="font-medium text-blue-600 dark:text-blue-400">{{ $onlineNowEstimate }}</span>
                            <span class="ml-3" wire:loading.delay.shortest wire:target="refreshLiveData">Refreshing…</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Status doughnut + legend -->
                        <div>
                            <div class="relative h-64">
                                <canvas id="serverStatusChart"></canvas>
                            </div>
                            <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span>
                                    <span class="text-gray-600 dark:text-gray-300">Healthy:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $serversByStatus['healthy'] ?? 0 }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-yellow-500"></span>
                                    <span class="text-gray-600 dark:text-gray-300">Warning:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $serversByStatus['warning'] ?? 0 }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>
                                    <span class="text-gray-600 dark:text-gray-300">Unhealthy:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $serversByStatus['unhealthy'] ?? 0 }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-gray-500"></span>
                                    <span class="text-gray-600 dark:text-gray-300">Offline:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $serversByStatus['offline'] ?? 0 }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                    <span class="text-gray-600 dark:text-gray-300">Provisioning:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $serversByStatus['provisioning'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Geographic list with utilization bars -->
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">Geographic Distribution</h4>
                            <div class="space-y-3">
                                @foreach($geographicDistribution as $location)
                                    @php
                                        $cities = collect($location['cities'] ?? [])->filter()->unique()->values();
                                        $cityCount = $cities->count();
                                        $preview = $cities->take(2)->join(', ');
                                        $total = max(1, (int)($location['server_count'] ?? 0));
                                        $healthy = (int)($location['healthy_count'] ?? 0);
                                        $pct = max(0, min(100, (int) round(($healthy / $total) * 100)));
                                        $barColor = $pct >= 80 ? 'bg-green-500' : ($pct >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                                    @endphp
                                    <div class="space-y-1" x-data="{ open: false }">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center flex-wrap gap-x-2">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $location['country'] }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    @if($preview)
                                                        ({{ $preview }}@if($cityCount > 2), +{{ $cityCount - 2 }} more @endif)
                                                    @endif
                                                </span>
                                                @if($cityCount > 2)
                                                    <button type="button" class="text-xs text-blue-600 dark:text-blue-400 hover:underline" @click="open = !open" x-text="open ? 'Hide cities' : 'Show cities ({{ $cityCount }})'"></button>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-2 text-sm">
                                                <span class="text-green-600 dark:text-green-400">{{ $location['healthy_count'] }}</span>
                                                <span class="text-gray-500 dark:text-gray-400">/</span>
                                                <span class="text-gray-900 dark:text-white">{{ $location['server_count'] }}</span>
                                            </div>
                                        </div>
                                        <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded">
                                            <div class="h-2 rounded {{ $barColor }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        @if($cityCount > 2)
                                            <div class="mt-2" x-show="open" x-cloak>
                                                <div class="flex flex-wrap gap-2 text-xs">
                                                    @foreach($cities as $city)
                                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $city }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="grid grid-cols-1 gap-6">
            {{-- Live Server Metrics --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg lg:col-span-2">
                <div class="px-4 py-5 sm:p-6 max-h-[40rem] overflow-y-auto" @if(!$pauseLive) wire:poll.keep-alive.20s="refreshLiveData" @endif>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Live Server Metrics
                    </h3>
                    @if(!empty($serverMetrics))
                        <div class="space-y-4">
                            @foreach($serverMetrics as $m)
                                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $m['name'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $m['country'] }}</div>
                                        </div>
                                        <div class="text-xs flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full
                                                @if(($m['status'] ?? 'healthy') === 'healthy') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                                @elseif(($m['status'] ?? 'warning') === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                                @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 @endif">
                                                {{ ucfirst($m['status'] ?? 'healthy') }}
                                            </span>
                                            @if(!empty($m['panel_url']))
                                                <a href="{{ $m['panel_url'] }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">Panel</a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                                <span>CPU</span>
                                                <span>{{ $m['cpu_usage'] }}%</span>
                                            </div>
                                            <div class="h-2 mt-1 bg-gray-200 dark:bg-gray-700 rounded">
                                                <div class="h-2 rounded bg-blue-500" style="width: {{ max(0, min(100, (int)$m['cpu_usage'])) }}%"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                                <span>Memory</span>
                                                <span>{{ $m['memory_usage'] }}%</span>
                                            </div>
                                            <div class="h-2 mt-1 bg-gray-200 dark:bg-gray-700 rounded">
                                                <div class="h-2 rounded bg-emerald-500" style="width: {{ max(0, min(100, (int)$m['memory_usage'])) }}%"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                                <span>Disk</span>
                                                <span>{{ $m['disk_usage'] }}%</span>
                                            </div>
                                            <div class="h-2 mt-1 bg-gray-200 dark:bg-gray-700 rounded">
                                                <div class="h-2 rounded bg-purple-500" style="width: {{ max(0, min(100, (int)$m['disk_usage'])) }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                                        <span>Response: <span class="font-medium text-gray-900 dark:text-white">{{ $m['response_time_ms'] }}ms</span></span>
                                        <span>Clients: <span class="font-medium text-gray-900 dark:text-white">{{ $m['active_clients'] }}</span></span>
                                        <span>Inbounds: <span class="font-medium text-gray-900 dark:text-white">{{ $m['inbounds_count'] ?? 0 }}</span></span>
                                        <button class="ml-auto text-blue-600 dark:text-blue-400 hover:underline" wire:click="monitorServerPerformance({{ $m['id'] }})">Monitor</button>
                                        <button class="text-purple-600 dark:text-purple-400 hover:underline" wire:click="syncInbounds({{ $m['id'] }})">Sync Inbounds</button>
                                        <button class="text-rose-600 dark:text-rose-400 hover:underline" wire:click="resetAllTraffics({{ $m['id'] }})">Reset Traffics</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No live metrics available.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 max-h-[40rem] overflow-y-auto" @if(!$pauseLive) wire:poll.keep-alive.45s="refreshLiveData" @endif>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Performance Overview</h3>
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">Top Performing Servers</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Server</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Uptime</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Response</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($topPerformingServers as $server)
                                            <tr>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $server['name'] }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $server['city'] }}, {{ $server['country'] }}</div>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">{{ number_format($server['uptime_percentage'], 1) }}%</span>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $server['response_time_ms'] }}ms</td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <button wire:click="checkServerHealth({{ $server['id'] }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm">Check Health</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700"></div>

                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">Servers Needing Attention</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Server</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Issue</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @php
                                            $serversNeedingAttentionFiltered = collect($serversNeedingAttention ?? [])
                                                ->filter(function ($s) {
                                                    return ($s['status'] ?? null) !== 'healthy';
                                                })
                                                ->values();
                                        @endphp
                                        @forelse($serversNeedingAttentionFiltered as $server)
                                            <tr>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $server['name'] }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $server['city'] }}, {{ $server['country'] }}</div>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        @if($server['status'] === 'healthy') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                                        @elseif($server['status'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                                        @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 @endif">
                                                        {{ ucfirst($server['status']) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    @if($server['uptime_percentage'] < 99)
                                                        Low uptime: {{ number_format($server['uptime_percentage'], 1) }}%
                                                    @elseif($server['response_time_ms'] > 1000)
                                                        Slow response: {{ $server['response_time_ms'] }}ms
                                                    @else
                                                        Status: {{ $server['status'] }}
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap space-x-2">
                                                    <button wire:click="checkServerHealth({{ $server['id'] }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm">Check</button>
                                                    <button wire:click="monitorServerPerformance({{ $server['id'] }})" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 text-sm">Monitor</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    <div class="flex flex-col items-center">
                                                        <x-heroicon-o-face-smile class="h-8 w-8 mb-2" />
                                                        All servers are performing well!
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="grid grid-cols-1 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 max-h-[40rem] overflow-y-auto" @if(!$pauseLive) wire:poll.keep-alive.45s="refreshLiveData" @endif>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Inbound Utilization</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Server</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Port/Proto</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current/Cap</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Util</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($inboundUtilization as $ib)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $ib['server_name'] ?? ('#'.$ib['server_id']) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $ib['port'] }} / {{ strtoupper($ib['protocol']) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $ib['current'] }} / {{ $ib['capacity'] ?? '∞' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            @if(!is_null($ib['utilization']))
                                                <div class="w-28 h-2 bg-gray-200 dark:bg-gray-700 rounded">
                                                    <div class="h-2 rounded bg-blue-500" style="width: {{ max(0, min(100, (int)$ib['utilization'])) }}%"></div>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Unlimited</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if(($ib['status'] ?? 'active') === 'active') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                                @elseif(($ib['status'] ?? '') === 'full') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100 @endif">
                                                {{ ucfirst($ib['status'] ?? 'active') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-xs space-x-2">
                                            <button
                                                x-data
                                                @click.prevent="if (confirm('Reset all clients\' traffic on this inbound?')) { $wire.resetInboundTraffics({{ $ib['server_id'] }}, {{ $ib['id'] }}) }"
                                                class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 pointer-events-none"
                                                wire:target="resetInboundTraffics"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3 animate-spin" wire:loading wire:target="resetInboundTraffics"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v2a.75.75 0 01-1.5 0v-2A.75.75 0 0112 2.25zm5.657 2.093a.75.75 0 011.06 1.06l-1.414 1.415a.75.75 0 01-1.06-1.06l1.414-1.415zM21 12a9 9 0 11-9-9 .75.75 0 010 1.5A7.5 7.5 0 1019.5 12a.75.75 0 011.5 0z" clip-rule="evenodd"/></svg>
                                                <span>Reset All Clients</span>
                                            </button>
                                            <button
                                                x-data
                                                @click.prevent="if (confirm('Delete all depleted clients for this inbound?')) { $wire.deleteDepletedClients({{ $ib['server_id'] }}, {{ $ib['id'] }}) }"
                                                class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 hover:underline"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 pointer-events-none"
                                                wire:target="deleteDepletedClients"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3 animate-spin" wire:loading wire:target="deleteDepletedClients"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v2a.75.75 0 01-1.5 0v-2A.75.75 0 01112 2.25zm5.657 2.093a.75.75 0 011.06 1.06l-1.414 1.415a.75.75 0 01-1.06-1.06l1.414-1.415zM21 12a9 9 0 11-9-9 .75.75 0 010 1.5A7.5 7.5 0 1019.5 12a.75.75 0 011.5 0z" clip-rule="evenodd"/></svg>
                                                <span>Delete Depleted</span>
                                            </button>
                                            <button class="text-gray-700 dark:text-gray-300 hover:underline"
                                                    wire:click="toggleInboundEnable({{ $ib['server_id'] }}, {{ $ib['id'] }})">
                                                {{ !empty($ib['enable']) ? 'Disable' : 'Enable' }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No inbound data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 max-h-[40rem] overflow-y-auto" @if(!$pauseLive) wire:poll.keep-alive.45s="refreshLiveData" @endif>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Clients At Risk</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Server</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Used (MB)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usage %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expiry</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($clientsAtRisk as $c)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $c['email'] }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $c['server_name'] ?? ('#'.$c['server_id']) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ number_format($c['traffic_used_mb'], 0) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded">
                                                    <div class="h-2 rounded bg-rose-500" style="width: {{ max(0, min(100, (int)$c['traffic_percentage_used'])) }}%"></div>
                                                </div>
                                                <span class="text-xs text-gray-900 dark:text-white">{{ number_format($c['traffic_percentage_used'], 0) }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $c['expiry_human'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No clients at risk.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($bulkHealthResults))
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 max-h-[40rem] overflow-y-auto" @if(!$pauseLive) wire:poll.keep-alive.60s="refreshLiveData" @endif>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Latest Health Check Results
                    </h3>

                    <div class="mb-4 flex items-center space-x-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Total:</span> {{ $bulkHealthResults['total_servers'] }}
                        </div>
                        <div class="text-sm text-green-600 dark:text-green-400">
                            <span class="font-medium">Healthy:</span> {{ $bulkHealthResults['healthy_servers'] }}
                        </div>
                        <div class="text-sm text-red-600 dark:text-red-400">
                            <span class="font-medium">Unhealthy:</span> {{ $bulkHealthResults['unhealthy_servers'] }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Server
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Response Time
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Uptime
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Clients
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Issues
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($bulkHealthResults['server_details'] as $server)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $server['name'] }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $server['location'] }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($server['status'] === 'healthy') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                                @elseif($server['status'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                                @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                                                @endif">
                                                {{ ucfirst($server['status']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($server['response_time'], 0) }}ms
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($server['uptime_percentage'], 1) }}%
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $server['active_clients'] }}
                                        </td>
                                        <td class="px-4 py-2">
                                            @if(empty($server['issues']))
                                                <span class="text-green-600 dark:text-green-400 text-sm">None</span>
                                            @else
                                                <div class="text-sm text-red-600 dark:text-red-400">
                                                    @foreach($server['issues'] as $issue)
                                                        <div>{{ $issue }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let serverStatusChart;
        function renderServerStatusChart(data) {
            const ctx = document.getElementById('serverStatusChart').getContext('2d');
            if (serverStatusChart) serverStatusChart.destroy();
            serverStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Healthy', 'Warning', 'Unhealthy', 'Offline', 'Provisioning'],
                    datasets: [{
                        data: [data.healthy, data.warning, data.unhealthy, data.offline, data.provisioning],
                        backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6B7280', '#3B82F6'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Initial render
        renderServerStatusChart(@json($serversByStatus));

        // Re-render when Livewire updates the DOM (after polling)
        document.addEventListener('livewire:navigated', () => renderServerStatusChart(@json($serversByStatus)));
        document.addEventListener('livewire:update', () => renderServerStatusChart(@json($serversByStatus)));
    </script>
    @endpush
</x-filament-panels::page>
