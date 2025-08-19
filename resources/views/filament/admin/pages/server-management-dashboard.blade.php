<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
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
                                    {{ $summary['total_servers'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
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
                                    {{ $summary['healthy_servers'] }} / {{ $summary['active_servers'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
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
                                    {{ number_format($summary['total_clients']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
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
                                    {{ number_format($summary['average_response_time'], 0) }}ms
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Server Status Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Server Status Chart -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Server Status Distribution
                    </h3>
                    <div class="relative h-64">
                        <canvas id="serverStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Geographic Distribution -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Geographic Distribution
                    </h3>
                    <div class="space-y-3">
                        @foreach($geographicDistribution as $location)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $location['country'] }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                        ({{ collect($location['cities'] ?? [])->filter()->unique()->values()->join(', ') }})
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-green-600 dark:text-green-400">
                                        {{ $location['healthy_count'] }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">/</span>
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $location['server_count'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Server Performance Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Performing Servers -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Top Performing Servers
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Server
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Uptime
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Response
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($topPerformingServers as $server)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $server['name'] }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $server['city'] }}, {{ $server['country'] }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                {{ number_format($server['uptime_percentage'], 1) }}%
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $server['response_time_ms'] }}ms
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <button
                                                wire:click="checkServerHealth({{ $server['id'] }})"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm"
                                            >
                                                Check Health
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Servers Needing Attention -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Servers Needing Attention
                    </h3>
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
                                        Issue
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($serversNeedingAttention as $server)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $server['name'] }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $server['city'] }}, {{ $server['country'] }}
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
                                            @if($server['uptime_percentage'] < 99)
                                                Low uptime: {{ number_format($server['uptime_percentage'], 1) }}%
                                            @elseif($server['response_time_ms'] > 1000)
                                                Slow response: {{ $server['response_time_ms'] }}ms
                                            @else
                                                Status: {{ $server['status'] }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap space-x-2">
                                            <button
                                                wire:click="checkServerHealth({{ $server['id'] }})"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm"
                                            >
                                                Check
                                            </button>
                                            <button
                                                wire:click="monitorServerPerformance({{ $server['id'] }})"
                                                class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 text-sm"
                                            >
                                                Monitor
                                            </button>
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

        <!-- Bulk Health Check Results -->
        @if(!empty($bulkHealthResults))
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
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
        // Server Status Chart
        const ctx = document.getElementById('serverStatusChart').getContext('2d');
        const serverStatusData = @json($serversByStatus);

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Warning', 'Unhealthy', 'Offline', 'Provisioning'],
                datasets: [{
                    data: [
                        serverStatusData.healthy,
                        serverStatusData.warning,
                        serverStatusData.unhealthy,
                        serverStatusData.offline,
                        serverStatusData.provisioning
                    ],
                    backgroundColor: [
                        '#10B981', // Green
                        '#F59E0B', // Yellow
                        '#EF4444', // Red
                        '#6B7280', // Gray
                        '#3B82F6'  // Blue
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#D1D5DB' : '#374151'
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
