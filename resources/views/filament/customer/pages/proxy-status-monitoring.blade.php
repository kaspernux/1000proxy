<x-filament-panels::page>
    <div class="fi-section-content-ctn">
    <div class="space-y-10">
        <!-- Modern Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
            <div>
                <p class="text-base text-gray-600 dark:text-gray-400">Live monitoring, health scores, and actionable alerts for all your proxies.</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bg-gradient-to-br from-primary-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-list-bullet class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Total Proxies</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['total_proxies'] ?? 0 }}</p>
            </div>
            <div class="bg-gradient-to-br from-success-500 to-emerald-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-check-circle class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Online</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['online_proxies'] ?? 0 }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-pink-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-x-circle class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Offline</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['offline_proxies'] ?? 0 }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center">
                <x-heroicon-o-heart class="h-8 w-8 mb-2 text-white" />
                <p class="text-lg font-semibold">Health Score</p>
                <p class="text-3xl font-bold">{{ $overallMetrics['health_score'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Alerts Section -->
        @if(!empty($alertsAndIssues))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                @foreach($alertsAndIssues as $alert)
                    <div class="rounded-xl shadow-lg border p-6 flex items-start gap-4 {{
                        $alert['type'] === 'error' ? 'bg-gradient-to-r from-red-50 to-red-100 border-red-200 dark:from-red-900/20 dark:to-red-800/20 dark:border-red-800' :
                        ($alert['type'] === 'warning' ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 border-yellow-200 dark:from-yellow-900/20 dark:to-yellow-800/20 dark:border-yellow-800' :
                        'bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200 dark:from-blue-900/20 dark:to-blue-800/20 dark:border-blue-800')
                    }}">
                        <div>
                            @if($alert['type'] === 'error')
                                <x-heroicon-o-x-circle class="h-7 w-7 text-red-400" />
                            @elseif($alert['type'] === 'warning')
                                <x-heroicon-o-exclamation-triangle class="h-7 w-7 text-yellow-400" />
                            @else
                                <x-heroicon-o-information-circle class="h-7 w-7 text-blue-400" />
                            @endif
                        </div>
                        <div>
                            <h3 class="text-base font-semibold mb-1 {{
                                $alert['type'] === 'error' ? 'text-red-800 dark:text-red-200' :
                                ($alert['type'] === 'warning' ? 'text-yellow-800 dark:text-yellow-200' :
                                'text-blue-800 dark:text-blue-200')
                            }}">
                                {{ $alert['title'] }}
                            </h3>
                            <div class="text-sm mb-2 {{
                                $alert['type'] === 'error' ? 'text-red-700 dark:text-red-300' :
                                ($alert['type'] === 'warning' ? 'text-yellow-700 dark:text-yellow-300' :
                                'text-blue-700 dark:text-blue-300')
                            }}">
                                <p>{{ $alert['message'] }}</p>
                            </div>
                            @if(!empty($alert['proxies']))
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($alert['proxies'] as $proxy)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">{{ $proxy }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Proxy Status Table -->
        <div class="my-16">
            <x-filament::section class="shadow-xl rounded-xl">
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                            <x-heroicon-o-list-bullet class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Proxy Statuses</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Comprehensive monitoring with real-time updates</p>
                        </div>
                    </div>
                </x-slot>
                <div class="p-6">
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 rounded-xl overflow-hidden">
                            <thead class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/10 dark:to-primary-800/10">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/5">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Location</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Latency</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Uptime</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider w-1/6">Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proxyStatuses as $proxy)
                                    <tr class="hover:bg-primary-100 dark:hover:bg-primary-900/20 transition border-b last:border-b-0">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-semibold flex items-center gap-2 truncate">
                                            <x-heroicon-o-server class="w-4 h-4 text-primary-500 dark:text-primary-400" />
                                            <span class="truncate">{{ $proxy['name'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2 truncate">
                                            <x-heroicon-o-map-pin class="w-4 h-4 text-primary-400 dark:text-primary-300" />
                                            <span class="truncate">{{ $proxy['location'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold shadow-sm border {{
                                                $proxy['status'] === 'online' ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700' :
                                                ($proxy['status'] === 'offline' ? 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700' :
                                                'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700')
                                            }}">
                                                @if($proxy['status'] === 'online')
                                                    <x-heroicon-o-check-circle class="w-3 h-3 mr-1 text-green-600 dark:text-green-400" />
                                                @elseif($proxy['status'] === 'offline')
                                                    <x-heroicon-o-x-circle class="w-3 h-3 mr-1 text-red-600 dark:text-red-400" />
                                                @else
                                                    <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1 text-yellow-600 dark:text-yellow-400" />
                                                @endif
                                                {{ ucfirst($proxy['status']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono flex items-center gap-1 whitespace-nowrap">
                                            <x-heroicon-o-bolt class="w-3 h-3 text-primary-400 dark:text-primary-300" />
                                            {{ $proxy['latency'] }}<span class="text-xs text-gray-500 dark:text-gray-400">ms</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono flex items-center gap-1 whitespace-nowrap">
                                            <x-heroicon-o-clock class="w-3 h-3 text-primary-400 dark:text-primary-300" />
                                            {{ $proxy['uptime'] }}<span class="text-xs text-gray-500 dark:text-gray-400">%</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono flex items-center gap-1 whitespace-nowrap">
                                            <x-heroicon-o-chart-bar class="w-3 h-3 text-primary-400 dark:text-primary-300" />
                                            {{ $proxy['data_usage']['total'] ?? 0 }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">No proxies found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
                                