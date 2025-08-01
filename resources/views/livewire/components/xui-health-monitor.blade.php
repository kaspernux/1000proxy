
<section class="xui-health-monitor bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-0 md:p-8 max-w-7xl mx-auto my-8">
    <!-- Header & Controls -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-800">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">XUI Health Monitor</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Real-time monitoring of <span class="font-semibold text-blue-600 dark:text-blue-400">{{ count($servers) }}</span> servers
                @if($lastUpdate)
                    <span class="mx-2 text-gray-300 dark:text-gray-600">•</span>
                    <span>Last updated {{ $lastUpdate->diffForHumans() }}</span>
                @endif
            </p>
        </div>
        <nav aria-label="Monitor controls" class="flex flex-wrap items-center gap-2 md:gap-4">
            <!-- Auto-refresh toggle -->
            <div class="flex items-center gap-2">
                <span class="text-xs md:text-sm text-gray-600 dark:text-gray-400">Auto-refresh</span>
                <button
                    wire:click="toggleAutoRefresh"
                    type="button"
                    aria-pressed="{{ $autoRefresh ? 'true' : 'false' }}"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $autoRefresh ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                >
                    <span class="sr-only">Toggle auto-refresh</span>
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 {{ $autoRefresh ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
            </div>
            <!-- Refresh interval selector -->
            @if($autoRefresh)
                <select
                    wire:model.live="refreshInterval"
                    wire:change="changeRefreshInterval($event.target.value)"
                    class="text-xs md:text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                    aria-label="Refresh interval"
                >
                    <option value="5">5s</option>
                    <option value="15">15s</option>
                    <option value="30">30s</option>
                    <option value="60">1m</option>
                    <option value="300">5m</option>
                </select>
            @endif
            <!-- Manual refresh button -->
            <button
                wire:click="refreshAllHealth"
                wire:loading.attr="disabled"
                type="button"
                class="p-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                title="Refresh all health data"
                aria-label="Refresh all health data"
            >
                <svg class="w-4 h-4" wire:loading.class="animate-spin" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </nav>
    </header>

    <!-- Health Summary Cards -->
    <main class="px-6 pt-6">
    <section aria-label="Health summary" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
        <div class="summary-card bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Online Servers</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $healthSummary['online'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="summary-card bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-red-600 dark:text-red-400">Offline Servers</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $healthSummary['offline'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="summary-card bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Warning Servers</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $healthSummary['warning'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-800 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="summary-card bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">System Health</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $healthSummary['health_percentage'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- Alerts Banner -->
    @if($alertsCount > 0)
        <aside class="mb-8 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-center gap-3" aria-live="polite">
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-red-700 dark:text-red-300 font-medium">
                {{ $alertsCount }} active {{ $alertsCount === 1 ? 'alert' : 'alerts' }} require attention
            </span>
        </aside>
    @endif

    <!-- Server Status Groups -->
    <section aria-label="Server status groups" class="space-y-8">
        @foreach(['online', 'warning', 'offline'] as $status)
            @if(count($serverGroups[$status]) > 0)
                <article class="server-group">
                    <header class="flex items-center mb-4">
                        @if($status === 'online')
                            <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Online Servers ({{ count($serverGroups[$status]) }})</h2>
                        @elseif($status === 'warning')
                            <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Warning Servers ({{ count($serverGroups[$status]) }})</h2>
                        @else
                            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Offline Servers ({{ count($serverGroups[$status]) }})</h2>
                        @endif
                    </header>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($serverGroups[$status] as $serverData)
                            @php
                                $server = $serverData['server'];
                                $health = $serverData['health'];
                                $clients = $serverData['clients'];
                            @endphp
                            <section class="server-card p-4 border border-gray-200 dark:border-gray-600 rounded-xl hover:shadow-lg transition-shadow duration-200 bg-white dark:bg-gray-800 flex flex-col gap-2 {{ $selectedServer === $server->id ? 'ring-2 ring-blue-500' : '' }}" aria-label="Server {{ $server->name }}">
                                <!-- Server header -->
                                <header class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $server->flag }}</span>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $server->name }}</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $server->country }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <!-- Status indicator -->
                                        <span class="w-3 h-3 rounded-full {{ $health['status'] === 'online' ? 'bg-green-500 animate-pulse' : ($health['status'] === 'offline' ? 'bg-red-500' : 'bg-yellow-500') }}"></span>
                                        <!-- Response time -->
                                        @if($health['response_time'])
                                            <span class="text-xs text-gray-500">{{ $health['response_time'] }}ms</span>
                                        @endif
                                    </div>
                                </header>
                                <!-- Server metrics -->
                                <div class="space-y-2 mb-2">
                                    <!-- CPU Usage -->
                                    <div class="flex items-center justify-between text-xs md:text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">CPU</span>
                                        <span class="font-medium {{ $health['cpu_usage'] > 80 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                            {{ $health['cpu_usage'] }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            class="h-2 rounded-full {{ $health['cpu_usage'] > 80 ? 'bg-red-500' : 'bg-blue-500' }}"
                                            style="width: {{ $health['cpu_usage'] }}%"
                                        ></div>
                                    </div>
                                    <!-- Memory Usage -->
                                    <div class="flex items-center justify-between text-xs md:text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Memory</span>
                                        <span class="font-medium {{ $health['memory_usage'] > 90 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                            {{ $health['memory_usage'] }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            class="h-2 rounded-full {{ $health['memory_usage'] > 90 ? 'bg-red-500' : 'bg-green-500' }}"
                                            style="width: {{ $health['memory_usage'] }}%"
                                        ></div>
                                    </div>
                                </div>
                                <!-- Client stats -->
                                <div class="grid grid-cols-2 gap-2 mb-2 text-xs">
                                    <div class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $clients['active_clients'] ?? 0 }}</div>
                                        <div class="text-gray-500 dark:text-gray-400">Active</div>
                                    </div>
                                    <div class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $health['active_connections'] }}</div>
                                        <div class="text-gray-500 dark:text-gray-400">Connections</div>
                                    </div>
                                </div>
                                <!-- Action buttons -->
                                <footer class="flex items-center justify-between mt-auto pt-2">
                                    <button
                                        wire:click="selectServer({{ $server->id }})"
                                        class="text-xs md:text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
                                    >
                                        View Details
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="testServerConnection({{ $server->id }})"
                                            class="p-1 text-gray-500 hover:text-green-600 transition-colors duration-200"
                                            title="Test Connection"
                                            wire:loading.attr="disabled"
                                            wire:target="testServerConnection({{ $server->id }})"
                                        >
                                            <svg class="w-4 h-4" wire:loading.class="animate-spin" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                        @if($health['status'] === 'offline')
                                            <button
                                                wire:click="restartServer({{ $server->id }})"
                                                class="p-1 text-gray-500 hover:text-orange-600 transition-colors duration-200"
                                                title="Restart Server"
                                                onclick="return confirm('Are you sure you want to restart this server?')"
                                            >
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </footer>
                            </section>
                        @endforeach
                    </div>
                </article>
            @endif
        @endforeach
    </section>

    <!-- Detailed server view modal -->
    @if($selectedServer && array_key_exists($selectedServer, $systemMetrics))
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
             wire:click.self="$set('selectedServer', null)">
            <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    @php
                        $server = $servers->find($selectedServer);
                        $metrics = $systemMetrics[$selectedServer] ?? [];
                    @endphp
                    <!-- Modal header -->
                    <header class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $server->flag }} {{ $server->name }} <span class="font-normal">- System Details</span>
                        </h3>
                        <button
                            wire:click="$set('selectedServer', null)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            aria-label="Close details modal"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </header>
                    <!-- System metrics grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- System Load -->
                        <article class="metric-card p-4 border border-gray-200 dark:border-gray-600 rounded-xl">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">System Load</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>1 minute:</span>
                                    <span class="font-medium">{{ $metrics['system_load']['load_1m'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>5 minutes:</span>
                                    <span class="font-medium">{{ $metrics['system_load']['load_5m'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>15 minutes:</span>
                                    <span class="font-medium">{{ $metrics['system_load']['load_15m'] }}</span>
                                </div>
                            </div>
                        </article>
                        <!-- Memory Details -->
                        <article class="metric-card p-4 border border-gray-200 dark:border-gray-600 rounded-xl">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Memory Usage</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Total:</span>
                                    <span class="font-medium">{{ $metrics['memory']['total'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Used:</span>
                                    <span class="font-medium">{{ $metrics['memory']['used'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Free:</span>
                                    <span class="font-medium">{{ $metrics['memory']['free'] }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-3">
                                    <div
                                        class="h-2 rounded-full bg-blue-500"
                                        style="width: {{ $metrics['memory']['usage_percent'] }}%"
                                    ></div>
                                </div>
                            </div>
                        </article>
                        <!-- Disk Usage -->
                        <article class="metric-card p-4 border border-gray-200 dark:border-gray-600 rounded-xl">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Disk Usage</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Total:</span>
                                    <span class="font-medium">{{ $metrics['disk']['total'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Used:</span>
                                    <span class="font-medium">{{ $metrics['disk']['used'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Free:</span>
                                    <span class="font-medium">{{ $metrics['disk']['free'] }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-3">
                                    <div
                                        class="h-2 rounded-full {{ $metrics['disk']['usage_percent'] > 85 ? 'bg-red-500' : 'bg-green-500' }}"
                                        style="width: {{ $metrics['disk']['usage_percent'] }}%"
                                    ></div>
                                </div>
                            </div>
                        </article>
                        <!-- Network Stats -->
                        <article class="metric-card p-4 border border-gray-200 dark:border-gray-600 rounded-xl">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Network Interface (eth0)</h4>
                            <div class="space-y-2">
                                @php $eth0 = $metrics['network_interfaces']['eth0']; @endphp
                                <div class="flex justify-between text-sm">
                                    <span>RX Bytes:</span>
                                    <span class="font-medium">{{ number_format($eth0['rx_bytes']) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>TX Bytes:</span>
                                    <span class="font-medium">{{ number_format($eth0['tx_bytes']) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>RX Packets:</span>
                                    <span class="font-medium">{{ number_format($eth0['rx_packets']) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>TX Packets:</span>
                                    <span class="font-medium">{{ number_format($eth0['tx_packets']) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </div>
    @endif

    <!-- Auto-refresh script -->
    <script>
        document.addEventListener('livewire:init', () => {
            let refreshInterval = null;
            Livewire.on('startAutoRefresh', (event) => {
                if (refreshInterval) clearInterval(refreshInterval);
                refreshInterval = setInterval(() => {
                    @this.pollHealth();
                }, event.interval * 1000);
            });
            Livewire.on('stopAutoRefresh', () => {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
            });
            Livewire.on('updateRefreshInterval', (event) => {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = setInterval(() => {
                        @this.pollHealth();
                    }, event.interval * 1000);
                }
            });
            Livewire.on('simulateRestart', (event) => {
                setTimeout(() => {
                    @this.handleServerStatusChange(event.serverId, 'online');
                }, 3000);
            });
            // Initialize auto-refresh if enabled
            @if($autoRefresh)
                refreshInterval = setInterval(() => {
                    @this.pollHealth();
                }, {{ $refreshInterval }} * 1000);
            @endif
        });
    </script>
</main>
</section>
