<x-filament-panels::page>
<div class="fi-section-content-ctn">
    <div class="min-h-screen bg-gradient-to-br from-green-900 via-gray-900 to-indigo-900 py-8 px-2 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 bg-white/10 dark:bg-gray-900/80 shadow-2xl rounded-2xl px-6 py-8 mb-8 border border-white/20">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">Advanced Proxy Management</h1>
                <p class="mt-2 text-lg text-white/80">Comprehensive proxy control, monitoring, and analytics</p>
            </div>
            <nav class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 bg-green-400 rounded-full animate-pulse"></span>
                    <span class="text-sm text-white/80">System Healthy</span>
                </div>
                <button
                    wire:click="refreshPerformanceData"
                    class="inline-flex items-center px-5 py-2 border border-white/20 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400 transition"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                    Refresh
                </button>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="py-4">
            <!-- Quick Stats -->
            <section aria-label="Quick Stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white/10 border border-white/20 shadow-2xl rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-check-badge class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <dl>
                            <dt class="text-sm font-medium text-white/80 truncate">Active Proxies</dt>
                            <dd class="text-2xl font-bold text-white">{{ number_format($serverStats['total_proxies'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="bg-white/10 border border-white/20 shadow-2xl rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-bolt class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <dl>
                            <dt class="text-sm font-medium text-white/80 truncate">Avg Response Time</dt>
                            <dd class="text-2xl font-bold text-white">{{ $serverStats['avg_response_time'] ?? 0 }}ms</dd>
                        </dl>
                    </div>
                </div>
                <div class="bg-white/10 border border-white/20 shadow-2xl rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-server class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <dl>
                            <dt class="text-sm font-medium text-white/80 truncate">Active Servers</dt>
                            <dd class="text-2xl font-bold text-white">{{ $serverStats['active_servers'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="bg-white/10 border border-white/20 shadow-2xl rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-chart-bar-square class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <dl>
                            <dt class="text-sm font-medium text-white/80 truncate">Daily Bandwidth</dt>
                            <dd class="text-2xl font-bold text-white">{{ $serverStats['total_bandwidth'] ?? '0 GB' }}</dd>
                        </dl>
                    </div>
                </div>
            </section>

            {{-- User Selection --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">User Selection</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label for="user-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Select User to Manage
                            </label>
                            <select
                                wire:model.live="selectedUserId"
                                id="user-select"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                            >
                                <option value="">Choose a user...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        @if($selectedUserId)
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Active Proxies: {{ $userProxies->count() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($selectedUserId)
                {{-- Tab Navigation --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-8">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            <button
                                wire:click="setActiveTab('overview')"
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                            >
                                Overview
                            </button>
                            <button
                                wire:click="setActiveTab('rotation')"
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'rotation' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                            >
                                IP Rotation
                            </button>
                            <button
                                wire:click="setActiveTab('loadbalancing')"
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'loadbalancing' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                            >
                                Load Balancing
                            </button>
                            <button
                                wire:click="setActiveTab('health')"
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'health' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                            >
                                Health Monitoring
                            </button>
                            <button
                                wire:click="setActiveTab('configurations')"
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'configurations' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                            >
                                Advanced Config
                            </button>
                            <button
                                wire:click="setActiveTab('analytics')"
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'analytics' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                            >
                                Analytics
                            </button>
                        </nav>
                    </div>

                    {{-- Tab Content --}}
                    <div class="p-6">
                        {{-- Overview Tab --}}
                        @if($activeTab === 'overview')
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {{-- User Proxies List --}}
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Active Proxies</h3>
                                        <div class="space-y-3">
                                            @foreach($userProxies as $proxy)
                                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                                    <div class="flex justify-between items-start">
                                                        <div>
                                                            <h4 class="font-medium text-gray-900 dark:text-white">
                                                                {{ $proxy->serverPlan->name }}
                                                            </h4>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                                Server: {{ $proxy->serverPlan->server->location }}
                                                            </p>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                                Status: <span class="text-green-600">{{ ucfirst($proxy->status) }}</span>
                                                            </p>
                                                        </div>
                                                        <div class="flex space-x-2">
                                                            <button
                                                                wire:click="manageProxy('restart_proxy', {{ $proxy->id }})"
                                                                class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"
                                                            >
                                                                Restart
                                                            </button>
                                                            <button
                                                                wire:click="manageProxy('rotate_ip', {{ $proxy->id }})"
                                                                class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded"
                                                            >
                                                                Rotate IP
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Recent Events --}}
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Events</h3>
                                        <div class="space-y-3">
                                            @foreach($recentEvents as $event)
                                                <div class="flex items-start space-x-3">
                                                    <div class="flex-shrink-0">
                                                        @if($event['type'] === 'success')
                                                            <div class="w-2 h-2 bg-green-400 rounded-full mt-2"></div>
                                                        @elseif($event['type'] === 'warning')
                                                            <div class="w-2 h-2 bg-yellow-400 rounded-full mt-2"></div>
                                                        @elseif($event['type'] === 'error')
                                                            <div class="w-2 h-2 bg-red-400 rounded-full mt-2"></div>
                                                        @else
                                                            <div class="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm text-gray-900 dark:text-white">{{ $event['message'] }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $event['time']->diffForHumans() }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- IP Rotation Tab --}}
                        @if($activeTab === 'rotation')
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">IP Rotation Configuration</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Rotation Type
                                                </label>
                                                <select wire:model="rotationType" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                    <option value="time_based">Time Based</option>
                                                    <option value="request_based">Request Based</option>
                                                    <option value="random">Random</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Rotation Interval (seconds)
                                                </label>
                                                <input type="number" wire:model="rotationInterval" min="60" max="3600"
                                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Sticky Session Duration (seconds)
                                                </label>
                                                <input type="number" wire:model="stickyDuration" min="300" max="7200"
                                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                            </div>

                                            <div class="flex space-x-4">
                                                <button
                                                    wire:click="enableAutoIPRotation"
                                                    class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                                                >
                                                    Enable Auto Rotation
                                                </button>
                                                <button
                                                    wire:click="configureCustomSchedule"
                                                    class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                                                >
                                                    Custom Schedule
                                                </button>
                                            </div>

                                            <button
                                                wire:click="enableStickySession"
                                                class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                                            >
                                                Enable Sticky Sessions
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Rotation Status</h3>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            @if(!empty($rotationConfigs))
                                                <div class="space-y-3">
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                                                        <span class="text-sm font-medium text-green-600">Active</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Type:</span>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rotationConfigs['rotation_type'] ?? 'None' }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Interval:</span>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rotationConfigs['rotation_interval'] ?? 0 }}s</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Endpoints:</span>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ count($rotationConfigs['rotation_endpoints'] ?? []) }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-400">No rotation configured</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Load Balancing Tab --}}
                        @if($activeTab === 'loadbalancing')
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Load Balancing Configuration</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Algorithm
                                                </label>
                                                <select wire:model="loadBalancingAlgorithm" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                    <option value="round_robin">Round Robin</option>
                                                    <option value="weighted_round_robin">Weighted Round Robin</option>
                                                    <option value="least_connections">Least Connections</option>
                                                    <option value="ip_hash">IP Hash</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Response Time Threshold (ms)
                                                </label>
                                                <input type="number" wire:model="responseThreshold" min="100" max="10000"
                                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Error Rate Threshold (%)
                                                </label>
                                                <input type="number" wire:model="errorThreshold" min="1" max="50"
                                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                            </div>

                                            <div class="flex items-center space-x-4">
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="enableHealthCheck" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable Health Checks</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="enableFailover" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable Failover</span>
                                                </label>
                                            </div>

                                            <button
                                                wire:click="setupLoadBalancing"
                                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                                            >
                                                Configure Load Balancing
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Load Balancer Status</h3>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            @if(!empty($loadBalancers))
                                                <div class="space-y-3">
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                                                        <span class="text-sm font-medium text-green-600">Active</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Algorithm:</span>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $loadBalancingAlgorithm }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Endpoints:</span>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $userProxies->count() }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-400">No load balancer configured</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Health Monitoring Tab --}}
                        @if($activeTab === 'health')
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Health Monitoring Settings</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Check Interval (seconds)
                                                </label>
                                                <input type="number" wire:model="checkInterval" min="30" max="3600"
                                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                            </div>

                                            <div class="flex items-center space-x-4">
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="monitoringEnabled" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable Monitoring</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="emailAlerts" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Email Alerts</span>
                                                </label>
                                            </div>

                                            <div class="flex items-center">
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="autoRemediation" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Auto Remediation</span>
                                                </label>
                                            </div>

                                            <button
                                                wire:click="setupHealthMonitoring"
                                                class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                                            >
                                                Setup Health Monitoring
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Health Status</h3>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            @if(!empty($healthStatus))
                                                <div class="space-y-3">
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Overall Health:</span>
                                                        <span class="text-sm font-medium text-green-600">{{ $healthStatus['overall_health'] ?? 0 }}%</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Healthy Proxies:</span>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $healthStatus['healthy_proxies'] ?? 0 }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Unhealthy Proxies:</span>
                                                        <span class="text-sm font-medium text-red-600">{{ $healthStatus['unhealthy_proxies'] ?? 0 }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Last Check:</span>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ isset($healthStatus['last_check']) ? $healthStatus['last_check']->diffForHumans() : 'Never' }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-400">No health monitoring data</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Advanced Configuration Tab --}}
                        @if($activeTab === 'configurations')
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Advanced Configuration</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Max Connections
                                                </label>
                                                <input type="number" wire:model="maxConnections" min="10" max="1000"
                                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Bandwidth Limit (MB/s)
                                                </label>
                                                <input type="number" wire:model="bandwidthLimit" min="1" max="1000"
                                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm"
                                                       placeholder="Leave empty for unlimited">
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="connectionPooling" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Connection Pooling</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="trafficShaping" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Traffic Shaping</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="enableCompression" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Compression</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="detailedLogging" class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Detailed Logging</span>
                                                </label>
                                            </div>

                                            <button
                                                wire:click="applyAdvancedConfiguration"
                                                class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700"
                                            >
                                                Apply Configuration
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Performance Metrics</h3>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <div class="space-y-3">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Requests/sec:</span>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performanceMetrics['requests_per_second'] ?? 0 }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Bandwidth Usage:</span>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performanceMetrics['bandwidth_usage'] ?? 0 }}%</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">CPU Usage:</span>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performanceMetrics['cpu_usage'] ?? 0 }}%</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Memory Usage:</span>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performanceMetrics['memory_usage'] ?? 0 }}%</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Network Latency:</span>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performanceMetrics['network_latency'] ?? 0 }}ms</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Analytics Tab --}}
                        @if($activeTab === 'analytics')
                            <div class="space-y-6">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Performance Analytics</h3>
                                    <div class="flex items-center space-x-4">
                                        <select wire:model.live="analyticsTimeRange" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm">
                                            <option value="1h">Last Hour</option>
                                            <option value="24h">Last 24 Hours</option>
                                            <option value="7d">Last 7 Days</option>
                                            <option value="30d">Last 30 Days</option>
                                        </select>
                                        <button wire:click="refreshAnalytics" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            Refresh
                                        </button>
                                    </div>
                                </div>

                                @if(!empty($performanceData))
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Response Time Analytics</h4>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Average:</span>
                                                    <span class="text-sm font-medium">{{ $performanceData['response_times']['avg'] ?? 0 }}ms</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">95th Percentile:</span>
                                                    <span class="text-sm font-medium">{{ $performanceData['response_times']['p95'] ?? 0 }}ms</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Bandwidth Analytics</h4>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Total:</span>
                                                    <span class="text-sm font-medium">{{ $performanceData['bandwidth_usage']['total'] ?? '0 GB' }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Average Speed:</span>
                                                    <span class="text-sm font-medium">{{ $performanceData['bandwidth_usage']['avg'] ?? '0 MB/s' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Success Rate</h4>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Overall:</span>
                                                    <span class="text-sm font-medium text-green-600">{{ $performanceData['success_rates']['overall'] ?? 0 }}%</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Load Distribution</h4>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Balanced:</span>
                                                    <span class="text-sm font-medium text-green-600">{{ $performanceData['load_distribution']['balanced'] ? 'Yes' : 'No' }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Variance:</span>
                                                    <span class="text-sm font-medium">{{ $performanceData['load_distribution']['variance'] ?? 0 }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-gray-500 dark:text-gray-400">No analytics data available</p>
                                        <button wire:click="refreshAnalytics" class="mt-2 text-indigo-600 hover:text-indigo-900">
                                            Load Analytics
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        <!-- Auto Refresh -->
        @push('scripts')
            <script>
                document.addEventListener('livewire:initialized', () => {
                    setInterval(() => {
                        if (typeof @this.selectedUserId !== 'undefined' && @this.selectedUserId) {
                            @this.refreshHealthStatus();
                        }
                    }, @this.refreshInterval * 1000);
                });
            </script>
        @endpush
    </div>
</div> 
</x-filament-panels::page>
