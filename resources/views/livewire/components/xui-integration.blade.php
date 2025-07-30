{{-- XUI Integration Interface Components --}}
{{-- Professional Blade components for XUI panel management --}}

{{-- 1. Live XUI Server Browser Component --}}
<div x-data="liveXUIServerBrowser" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">XUI Server Browser</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Monitor and manage your XUI panel servers in real-time</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            {{-- Auto-refresh toggle --}}
            <button
                @click="toggleAutoRefresh()"
                :class="autoRefresh ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-colors duration-200"
            >
                <span x-show="autoRefresh">🔄 Auto-refresh ON</span>
                <span x-show="!autoRefresh">⏸️ Auto-refresh OFF</span>
            </button>
            {{-- Manual refresh --}}
            <button
                @click="loadServers()"
                :disabled="loading"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                <span x-show="!loading">🔄 Refresh</span>
                <span x-show="loading">⏳ Loading...</span>
            </button>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Search --}}
        <div class="relative">
            <input
                x-model="searchQuery"
                type="text"
                placeholder="Search servers..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>

        {{-- Status Filter --}}
        <select
            x-model="filterStatus"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
        >
            <option value="all">All Status</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="healthy">Healthy (80%+)</option>
            <option value="warning">Warning (40-80%)</option>
            <option value="critical">Critical (<40%)</option>
        </select>

        {{-- Sort Options --}}
        <select
            x-model="sortBy"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
        >
            <option value="health">Health Score</option>
            <option value="name">Server Name</option>
            <option value="location">Location</option>
            <option value="cpu_usage">CPU Usage</option>
            <option value="memory_usage">Memory Usage</option>
            <option value="response_time">Response Time</option>
        </select>
    </div>

    {{-- Bulk Actions --}}
    <div x-show="selectedServers.size > 0" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                <span x-text="selectedServers.size"></span> server(s) selected
            </p>
            <div class="flex space-x-2">
                <button
                    @click="performBulkAction('restart')"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm font-medium"
                >
                    🔄 Restart
                </button>
                <button
                    @click="performBulkAction('test')"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium"
                >
                    🧪 Test All
                </button>
                <button
                    @click="clearSelection()"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm font-medium"
                >
                    ❌ Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Error Message --}}
    <div x-show="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm font-medium text-red-800 dark:text-red-200" x-text="error"></p>
        </div>
    </div>

    {{-- Servers Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="server in filteredServers" :key="server.id">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow duration-200">
                {{-- Server Header --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            :checked="selectedServers.has(server.id)"
                            @change="toggleServerSelection(server.id)"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <h3 class="font-semibold text-gray-900 dark:text-white" x-text="server.name"></h3>
                    </div>
                    <div class="flex items-center space-x-1">
                        <span x-text="getHealthIcon(server.health)"></span>
                        <span
                            :class="getHealthColor(server.health)"
                            class="text-sm font-medium"
                            x-text="Math.round(server.health) + '%'"
                        ></span>
                    </div>
                </div>

                {{-- Server Info --}}
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Location:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="server.location"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">IP:</span>
                        <span class="font-mono text-gray-900 dark:text-white" x-text="server.ip"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Status:</span>
                        <span
                            :class="server.online ? 'text-green-600' : 'text-red-600'"
                            class="font-medium"
                            x-text="server.online ? 'Online' : 'Offline'"
                        ></span>
                    </div>
                </div>

                {{-- Performance Metrics --}}
                <div class="space-y-2 mb-4">
                    {{-- CPU Usage --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>CPU</span>
                            <span x-text="server.cpu_usage + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="server.cpu_usage > 80 ? 'bg-red-500' : server.cpu_usage > 60 ? 'bg-yellow-500' : 'bg-green-500'"
                                :style="`width: ${server.cpu_usage}%`"
                            ></div>
                        </div>
                    </div>

                    {{-- Memory Usage --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Memory</span>
                            <span x-text="server.memory_usage + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="server.memory_usage > 90 ? 'bg-red-500' : server.memory_usage > 70 ? 'bg-yellow-500' : 'bg-green-500'"
                                :style="`width: ${server.memory_usage}%`"
                            ></div>
                        </div>
                    </div>

                    {{-- Connections --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Connections</span>
                            <span x-text="`${server.active_connections}/${server.max_connections}`"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div
                                class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                :style="`width: ${(server.active_connections / server.max_connections) * 100}%`"
                            ></div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex space-x-2">
                    <button
                        @click="testConnection(server.id)"
                        :disabled="server.testing"
                        class="flex-1 bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        <span x-show="!server.testing">🧪 Test</span>
                        <span x-show="server.testing">⏳ Testing...</span>
                    </button>
                    <button
                        @click="$dispatch('server-manage', { server })"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
                    >
                        ⚙️ Manage
                    </button>
                </div>

                {{-- Last Checked --}}
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 text-center">
                    Last checked: <span x-text="new Date(server.lastChecked).toLocaleTimeString()"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Loading State --}}
    <div x-show="loading && servers.length === 0" class="text-center py-12">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-blue-500 bg-blue-100 dark:bg-blue-900 dark:text-blue-200 transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading servers...
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!loading && filteredServers.length === 0 && servers.length > 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0120 12a8 8 0 10-16 0 7.962 7.962 0 002 5.291z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No servers found</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No servers match your current filters.</p>
        <div class="mt-6">
            <button
                @click="searchQuery = ''; filterStatus = 'all'"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Clear filters
            </button>
        </div>
    </div>

    {{-- No servers at all --}}
    <div x-show="!loading && servers.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No servers configured</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first XUI panel server.</p>
        <div class="mt-6">
            <button
                @click="$dispatch('add-server')"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Add Server
            </button>
        </div>
    </div>
</div>

{{-- 2. XUI Inbound Manager Component --}}
<div x-data="xuiInboundManager" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Inbound Manager</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Drag and drop to manage inbound configurations</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <button
                @click="showCreateModal = true"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                ➕ Add Inbound
            </button>
        </div>
    </div>

    {{-- Drag and Drop Zones --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        {{-- Active Inbounds --}}
        <div
            class="drop-zone bg-green-50 dark:bg-green-900/20 border-2 border-dashed border-green-300 dark:border-green-700 rounded-lg p-4 min-h-48"
            data-action="active"
        >
            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-4 text-center">
                🟢 Active Inbounds
            </h3>
            <div class="space-y-3">
                <template x-for="inbound in getInboundsByStatus('active')" :key="inbound.id">
                    <div
                        class="draggable-inbound bg-white dark:bg-gray-700 p-3 rounded-md shadow-sm border border-green-200 dark:border-green-700 cursor-move"
                        :data-inbound-id="inbound.id"
                        draggable="true"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="inbound.remark || `Port ${inbound.port}`"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="`${inbound.protocol} • Port ${inbound.port}`"></p>
                            </div>
                            <div class="text-sm">
                                <span class="text-green-600 dark:text-green-400">Active</span>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="formatTraffic(inbound.up + inbound.down)"></span> total traffic
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Inactive Inbounds --}}
        <div
            class="drop-zone bg-yellow-50 dark:bg-yellow-900/20 border-2 border-dashed border-yellow-300 dark:border-yellow-700 rounded-lg p-4 min-h-48"
            data-action="inactive"
        >
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-4 text-center">
                🟡 Inactive Inbounds
            </h3>
            <div class="space-y-3">
                <template x-for="inbound in getInboundsByStatus('inactive')" :key="inbound.id">
                    <div
                        class="draggable-inbound bg-white dark:bg-gray-700 p-3 rounded-md shadow-sm border border-yellow-200 dark:border-yellow-700 cursor-move"
                        :data-inbound-id="inbound.id"
                        draggable="true"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="inbound.remark || `Port ${inbound.port}`"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="`${inbound.protocol} • Port ${inbound.port}`"></p>
                            </div>
                            <div class="text-sm">
                                <span class="text-yellow-600 dark:text-yellow-400">Inactive</span>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="formatTraffic(inbound.up + inbound.down)"></span> total traffic
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Delete Zone --}}
        <div
            class="drop-zone bg-red-50 dark:bg-red-900/20 border-2 border-dashed border-red-300 dark:border-red-700 rounded-lg p-4 min-h-48"
            data-action="delete"
        >
            <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-4 text-center">
                🗑️ Delete Zone
            </h3>
            <div class="text-center text-gray-500 dark:text-gray-400 mt-8">
                <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <p class="mt-2 text-sm">Drop inbounds here to delete them</p>
            </div>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="flex-shrink-0 h-5 w-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">How to use:</h3>
                <ul class="mt-1 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                    <li>Drag inbounds between Active and Inactive to enable/disable them</li>
                    <li>Drop inbounds in the Delete Zone to remove them permanently</li>
                    <li>Click "Add Inbound" to create new inbound configurations</li>
                    <li>Use the edit button on each inbound to modify settings</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="loading" class="text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-blue-500 bg-blue-100 dark:bg-blue-900 dark:text-blue-200 transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading inbounds...
        </div>
    </div>

    {{-- Error Message --}}
    <div x-show="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm font-medium text-red-800 dark:text-red-200" x-text="error"></p>
        </div>
    </div>
</div>

{{-- 3. Client Configuration Builder Component --}}
<div x-data="clientConfigurationBuilder" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Configuration Builder</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Build and preview client configurations with real-time updates</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            {{-- Preset Buttons --}}
            <select
                @change="loadPreset($event.target.value); $event.target.value = ''"
                class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white px-3 py-2 rounded-md text-sm"
            >
                <option value="">Load Preset...</option>
                <option value="vless-reality">VLESS + Reality</option>
                <option value="vmess-ws-tls">VMess + WebSocket + TLS</option>
                <option value="trojan-tcp">Trojan + TCP</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Configuration Form --}}
        <div class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Configuration Settings</h3>

            {{-- Basic Settings --}}
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Protocol</label>
                        <select
                            x-model="config.protocol"
                            @change="updateConfiguration()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                            <template x-for="protocol in protocols" :key="protocol">
                                <option :value="protocol" x-text="protocol.toUpperCase()"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Security</label>
                        <select
                            x-model="config.security"
                            @change="updateConfiguration()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                            <template x-for="security in securities" :key="security">
                                <option :value="security" x-text="security === 'none' ? 'None' : security.toUpperCase()"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Network</label>
                        <select
                            x-model="config.network"
                            @change="updateConfiguration()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                            <template x-for="network in networks" :key="network">
                                <option :value="network" x-text="network.toUpperCase()"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Port</label>
                        <input
                            x-model.number="config.port"
                            @input="updateConfiguration()"
                            type="number"
                            min="1"
                            max="65535"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            :class="validationErrors.port ? 'border-red-300 dark:border-red-600' : ''"
                        >
                        <p x-show="validationErrors.port" class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="validationErrors.port"></p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">UUID</label>
                    <div class="flex">
                        <input
                            x-model="config.uuid"
                            @input="updateConfiguration()"
                            type="text"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                            :class="validationErrors.uuid ? 'border-red-300 dark:border-red-600' : ''"
                        >
                        <button
                            @click="generateUUID(); updateConfiguration()"
                            class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white border border-blue-500 rounded-r-md transition-colors duration-200"
                        >
                            🔄
                        </button>
                    </div>
                    <p x-show="validationErrors.uuid" class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="validationErrors.uuid"></p>
                </div>
            </div>

            {{-- Conditional Settings --}}
            <div x-show="config.network === 'ws'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">WebSocket Path</label>
                    <input
                        x-model="config.path"
                        @input="updateConfiguration()"
                        type="text"
                        placeholder="/websocket"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        :class="validationErrors.path ? 'border-red-300 dark:border-red-600' : ''"
                    >
                    <p x-show="validationErrors.path" class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="validationErrors.path"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Host (Optional)</label>
                    <input
                        x-model="config.host"
                        @input="updateConfiguration()"
                        type="text"
                        placeholder="example.com"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                </div>
            </div>

            <div x-show="config.network === 'grpc'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">gRPC Service Name</label>
                    <input
                        x-model="config.serviceName"
                        @input="updateConfiguration()"
                        type="text"
                        placeholder="TunService"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        :class="validationErrors.serviceName ? 'border-red-300 dark:border-red-600' : ''"
                    >
                    <p x-show="validationErrors.serviceName" class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="validationErrors.serviceName"></p>
                </div>
            </div>

            <div x-show="config.security === 'reality'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Public Key</label>
                    <div class="flex">
                        <input
                            x-model="config.publicKey"
                            @input="updateConfiguration()"
                            type="text"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                            :class="validationErrors.publicKey ? 'border-red-300 dark:border-red-600' : ''"
                        >
                        <button
                            @click="generateKeys(); updateConfiguration()"
                            class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white border border-green-500 rounded-r-md transition-colors duration-200"
                        >
                            🔑
                        </button>
                    </div>
                    <p x-show="validationErrors.publicKey" class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="validationErrors.publicKey"></p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Short ID</label>
                        <input
                            x-model="config.shortId"
                            @input="updateConfiguration()"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-sm"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fingerprint</label>
                        <select
                            x-model="config.fingerprint"
                            @change="updateConfiguration()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                            <template x-for="fp in fingerprints" :key="fp">
                                <option :value="fp" x-text="fp"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex space-x-3 pt-4">
                <button
                    @click="updateConfiguration()"
                    :disabled="loading"
                    class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    <span x-show="!loading">🔄 Update Config</span>
                    <span x-show="loading">⏳ Generating...</span>
                </button>
                <button
                    @click="exportConfig()"
                    :disabled="!generatedLink"
                    class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    💾 Export
                </button>
            </div>
        </div>

        {{-- Live Preview --}}
        <div class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Live Preview</h3>

            {{-- Generated Link --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Generated Configuration Link</label>
                <div class="relative">
                    <textarea
                        :value="generatedLink"
                        readonly
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm resize-none"
                        placeholder="Configuration will appear here..."
                    ></textarea>
                    <button
                        x-show="generatedLink"
                        @click="copyToClipboard(generatedLink)"
                        class="absolute top-2 right-2 bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs transition-colors duration-200"
                    >
                        📋 Copy
                    </button>
                </div>
            </div>

            {{-- QR Code --}}
            <div x-show="qrCode">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">QR Code</label>
                <div class="bg-white p-4 rounded-lg border border-gray-300 dark:border-gray-600 text-center">
                    <img :src="qrCode" alt="Configuration QR Code" class="mx-auto max-w-full h-auto">
                    <div class="mt-3 flex justify-center space-x-2">
                        <button
                            @click="copyToClipboard(generatedLink)"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors duration-200"
                        >
                            📋 Copy Link
                        </button>
                        <button
                            @click="downloadQR()"
                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition-colors duration-200"
                        >
                            💾 Download QR
                        </button>
                    </div>
                </div>
            </div>

            {{-- Configuration Summary --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Configuration Summary</label>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Protocol:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="config.protocol.toUpperCase()"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Security:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="config.security === 'none' ? 'None' : config.security.toUpperCase()"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Network:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="config.network.toUpperCase()"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Port:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="config.port"></span>
                    </div>
                    <div x-show="config.network === 'ws' && config.path" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Path:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="config.path"></span>
                    </div>
                    <div x-show="config.network === 'grpc' && config.serviceName" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Service:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="config.serviceName"></span>
                    </div>
                    <div x-show="config.security === 'reality' && config.fingerprint" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Fingerprint:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="config.fingerprint"></span>
                    </div>
                </div>
            </div>

            {{-- Validation Errors --}}
            <div x-show="Object.keys(validationErrors).length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Validation Errors:</h4>
                <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                    <template x-for="[field, error] in Object.entries(validationErrors)" :key="field">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Include Chart.js for traffic monitoring if not already included --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
