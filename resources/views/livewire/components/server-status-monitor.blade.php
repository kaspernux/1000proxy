{{-- Server Status Monitor - Real-time server monitoring with WebSocket integration --}}
<div x-data="{ 
        autoRefreshTimer: null,
        startAutoRefresh(interval) {
            this.stopAutoRefresh();
            this.autoRefreshTimer = setInterval(() => {
                $wire.refreshServers();
            }, interval * 1000);
        },
        stopAutoRefresh() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
                this.autoRefreshTimer = null;
            }
        },
        updateRefreshInterval(interval) {
            if (this.autoRefreshTimer) {
                this.startAutoRefresh(interval);
            }
        }
    }"
    x-init="@if($autoRefresh) startAutoRefresh({{ $refreshInterval }}) @endif"
    @start-auto-refresh.window="startAutoRefresh($event.detail)"
    @stop-auto-refresh.window="stopAutoRefresh()"
    @update-refresh-interval.window="updateRefreshInterval($event.detail)"
    class="server-status-monitor bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">

    {{-- Header with Controls --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Server Status Monitor
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Real-time monitoring of all proxy servers
                    @if($lastUpdated)
                        • Last updated: {{ $lastUpdated->diffForHumans() }}
                    @endif
                </p>
            </div>

            {{-- Control Buttons --}}
            <div class="flex items-center space-x-3">
                {{-- Refresh Button --}}
                <button wire:click="refreshServers" 
                        :disabled="$wire.isLoading"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': $wire.isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>

                {{-- Check All Button --}}
                <button wire:click="checkAllServers"
                        :disabled="$wire.isLoading"
                        class="flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Check All
                </button>

                {{-- Auto-refresh Toggle --}}
                <div class="flex items-center">
                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" 
                               wire:model.live="autoRefresh" 
                               wire:change="toggleAutoRefresh"
                               class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        Auto-refresh
                    </label>
                    @if($autoRefresh)
                        <select wire:model.live="refreshInterval" 
                                wire:change="updateRefreshInterval($event.target.value)"
                                class="ml-2 text-xs border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                            <option value="10">10s</option>
                            <option value="30">30s</option>
                            <option value="60">1m</option>
                            <option value="120">2m</option>
                            <option value="300">5m</option>
                        </select>
                    @endif
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Servers</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-green-600 dark:text-green-400">Online</div>
                <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $stats['online'] }}</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-red-600 dark:text-red-400">Offline</div>
                <div class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stats['offline'] }}</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Warning</div>
                <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['warning'] }}</div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Avg Response</div>
                <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['average_response_time'] }}ms</div>
            </div>
        </div>
    </div>

    {{-- Filters and Sorting --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <div class="flex flex-wrap items-center justify-between gap-4">
            {{-- Status Filter --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Filter:</span>
                <div class="flex space-x-1">
                    @foreach(['all' => 'All', 'online' => 'Online', 'offline' => 'Offline', 'warning' => 'Warning'] as $status => $label)
                        <button wire:click="filterByStatus('{{ $status }}')"
                                class="px-3 py-1 text-xs font-medium rounded-full transition-colors {{ $filterStatus === $status 
                                    ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' 
                                    : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Export Button --}}
            <button wire:click="exportReport"
                    class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    {{-- Server List --}}
    <div class="overflow-hidden">
        @if($isLoading)
            <div class="flex items-center justify-center py-12">
                <div class="flex items-center space-x-3">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    <span class="text-gray-600 dark:text-gray-400">Loading servers...</span>
                </div>
            </div>
        @elseif(empty($servers))
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No servers found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first server.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            @foreach([
                                'status' => 'Status',
                                'name' => 'Server Name',
                                'location' => 'Location',
                                'response_time' => 'Response Time',
                                'uptime' => 'Uptime'
                            ] as $column => $label)
                                <th wire:click="sortBy('{{ $column }}')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center space-x-1">
                                        <span>{{ $label }}</span>
                                        @if($sortBy === $column)
                                            <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'transform rotate-180' : '' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($servers as $server)
                            <tr wire:key="server-{{ $server['id'] }}" 
                                class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                wire:click="selectServer({{ $server['id'] }})">
                                
                                {{-- Status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-lg mr-2">{{ $server['status_icon'] }}</span>
                                        <div>
                                            <div class="text-sm font-medium {{ $server['status_color'] }}">
                                                {{ ucfirst($server['status']) }}
                                            </div>
                                            @if($server['last_checked_human'])
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $server['last_checked_human'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Server Name --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $server['name'] }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $server['hostname'] }}:{{ $server['port'] }}
                                    </div>
                                </td>

                                {{-- Location --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-lg mr-2">{{ $server['flag_emoji'] }}</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $server['country'] }}</span>
                                    </div>
                                </td>

                                {{-- Response Time --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($server['response_time'])
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $server['response_time'] }}ms
                                        </div>
                                        <div class="w-16 bg-gray-200 dark:bg-gray-600 rounded-full h-1">
                                            <div class="h-1 rounded-full {{ $server['response_time'] < 100 ? 'bg-green-500' : ($server['response_time'] < 300 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                 style="width: {{ min(100, ($server['response_time'] / 500) * 100) }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>

                                {{-- Uptime --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($server['uptime_percentage'])
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $server['uptime_percentage'] }}%
                                        </div>
                                        <div class="w-16 bg-gray-200 dark:bg-gray-600 rounded-full h-1">
                                            <div class="h-1 rounded-full {{ $server['uptime_percentage'] >= 95 ? 'bg-green-500' : ($server['uptime_percentage'] >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                 style="width: {{ $server['uptime_percentage'] }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>

                                {{-- Details --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ $server['server_plans_count'] }} plans</div>
                                    <div>{{ $server['active_clients_count'] }} clients</div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click.stop="checkServerStatus({{ $server['id'] }})"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 transition-colors">
                                        Check
                                    </button>
                                    <a href="{{ $server['connection_url'] }}" target="_blank"
                                       class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Selected Server Details Panel --}}
    @if($selectedServer)
        <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Server Details</h3>
                <button wire:click="selectServer(null)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Connection</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Hostname:</span> {{ $selectedServer['hostname'] }}</div>
                        <div><span class="font-medium">Port:</span> {{ $selectedServer['port'] }}</div>
                        <div><span class="font-medium">Status:</span> 
                            <span class="{{ $selectedServer['status_color'] }}">{{ ucfirst($selectedServer['status']) }}</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Performance</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Response Time:</span> {{ $selectedServer['response_time'] ?? 'N/A' }}ms</div>
                        <div><span class="font-medium">Uptime:</span> {{ $selectedServer['uptime_percentage'] ?? 'N/A' }}%</div>
                        <div><span class="font-medium">Last Check:</span> {{ $selectedServer['last_checked_human'] ?? 'Never' }}</div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Usage</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Server Plans:</span> {{ $selectedServer['server_plans_count'] }}</div>
                        <div><span class="font-medium">Active Clients:</span> {{ $selectedServer['active_clients_count'] }}</div>
                        <div><span class="font-medium">Location:</span> {{ $selectedServer['flag_emoji'] }} {{ $selectedServer['country'] }}</div>
                    </div>
                </div>
            </div>

            @if(isset($connectionErrors[$selectedServer['id']]))
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                    <div class="text-sm text-red-700 dark:text-red-400">
                        <strong>Connection Error:</strong> {{ $connectionErrors[$selectedServer['id']] }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Real-time connection indicator --}}
<div wire:loading.flex class="fixed bottom-4 right-4 items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg">
    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
    <span class="text-sm font-medium">Updating...</span>
</div>

{{-- Toast notifications for server events --}}
@script
<script>
    // Listen for server status events
    $wire.on('server-status-updated', (data) => {
        if (window.showNotification) {
            const status = data.status;
            const type = status === 'online' ? 'success' : (status === 'offline' ? 'error' : 'warning');
            window.showNotification(type, `Server ${data.serverId} is now ${status}`);
        }
    });
    
    $wire.on('server-checked', (data) => {
        if (window.showNotification) {
            window.showNotification('info', `Server ${data.serverId} checked - ${data.responseTime}ms`);
        }
    });
    
    $wire.on('all-servers-checked', () => {
        if (window.showNotification) {
            window.showNotification('success', 'All servers checked successfully');
        }
    });
</script>
@endscript
