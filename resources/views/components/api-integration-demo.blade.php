{{-- API Integration Demo Component --}}
<div x-data="apiIntegration" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">API Integration System</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Comprehensive async API management with error handling, caching & rate limiting</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400">API Manager Active</span>
            </div>
            <button
                @click="resetStats()"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Reset Stats
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Requests</p>
                    <p class="text-xl font-bold" x-text="stats.requests || 0"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-blue-100">Success Rate</p>
                    <p class="text-sm font-semibold" x-text="stats.successRate || '0%'"></p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Avg Response Time</p>
                    <p class="text-xl font-bold" x-text="(stats.avgResponseTime || 0) + 'ms'"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-green-100">Cache Hits</p>
                    <p class="text-sm font-semibold" x-text="stats.cacheHits || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Cache Hit Rate</p>
                    <p class="text-xl font-bold" x-text="stats.cacheHitRate || '0%'"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-purple-100">Errors</p>
                    <p class="text-sm font-semibold" x-text="stats.errors || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Uptime</p>
                    <p class="text-xl font-bold" x-text="Math.round((stats.uptime || 0) / 1000) + 's'"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-orange-100">Cache Size</p>
                    <p class="text-sm font-semibold" x-text="cacheEntries.length || 0"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button
                @click="activeTab = 'testing'"
                :class="activeTab === 'testing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                x-data="{ activeTab: 'testing' }"
                x-init="$parent.activeTab = 'testing'"
            >
                🧪 API Testing
            </button>
            <button
                @click="activeTab = 'monitoring'"
                :class="activeTab === 'monitoring' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                📊 Monitoring
            </button>
            <button
                @click="activeTab = 'caching'"
                :class="activeTab === 'caching' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                💾 Cache Management
            </button>
            <button
                @click="activeTab = 'rate-limiting'"
                :class="activeTab === 'rate-limiting' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                🚦 Rate Limiting
            </button>
            <button
                @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                📝 Logs
            </button>
        </nav>
    </div>

    {{-- API Testing Tab --}}
    <div x-show="activeTab === 'testing'" class="space-y-6">
        {{-- Quick Test Buttons --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick API Tests</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button
                    @click="testGet()"
                    :disabled="isLoading"
                    class="bg-green-500 hover:bg-green-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    <span x-show="!isLoading">GET Request</span>
                    <span x-show="isLoading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading
                    </span>
                </button>
                <button
                    @click="testPost()"
                    :disabled="isLoading"
                    class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    POST Request
                </button>
                <button
                    @click="testError()"
                    :disabled="isLoading"
                    class="bg-red-500 hover:bg-red-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    Error Test
                </button>
                <button
                    @click="testTimeout()"
                    :disabled="isLoading"
                    class="bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    Timeout Test
                </button>
            </div>
        </div>

        {{-- Custom Request Builder --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Custom Request Builder</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">HTTP Method</label>
                    <select
                        x-model="config.method"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white"
                    >
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="PATCH">PATCH</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Endpoint URL</label>
                    <input
                        type="text"
                        x-model="config.endpoint"
                        placeholder="/api/endpoint"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timeout (ms)</label>
                    <input
                        type="number"
                        x-model="config.timeout"
                        placeholder="10000"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white"
                    >
                </div>
                <div class="flex items-end">
                    <button
                        @click="makeRequest()"
                        :disabled="isLoading"
                        class="w-full bg-purple-500 hover:bg-purple-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        🚀 Send Request
                    </button>
                </div>
            </div>

            {{-- Request Data (for POST/PUT/PATCH) --}}
            <div x-show="['POST', 'PUT', 'PATCH'].includes(config.method)" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Request Data (JSON)</label>
                <textarea
                    x-model="JSON.stringify(config.data, null, 2)"
                    @input="try { config.data = JSON.parse($event.target.value) } catch(e) {}"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white font-mono text-sm"
                    placeholder='{"key": "value"}'
                ></textarea>
            </div>

            {{-- Query Parameters (for GET) --}}
            <div x-show="config.method === 'GET'" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Query Parameters</label>
                <textarea
                    x-model="JSON.stringify(config.params, null, 2)"
                    @input="try { config.params = JSON.parse($event.target.value) } catch(e) {}"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white font-mono text-sm"
                    placeholder='{"param": "value"}'
                ></textarea>
            </div>
        </div>

        {{-- Response Display --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Response</h3>
            
            {{-- Success Response --}}
            <div x-show="data && !error" class="space-y-3">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        ✅ Success
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Response received</span>
                </div>
                <pre class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 text-sm overflow-x-auto" x-text="JSON.stringify(data, null, 2)"></pre>
            </div>

            {{-- Error Response --}}
            <div x-show="error" class="space-y-3">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        ❌ Error
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Request failed</span>
                </div>
                <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4 text-sm text-red-800 dark:text-red-200" x-text="error"></div>
            </div>

            {{-- No Response Yet --}}
            <div x-show="!data && !error && !isLoading" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-2.697-.413l-3.154 1.578a.5.5 0 01-.65-.65l1.578-3.154A8.955 8.955 0 014 12C4 7.582 7.582 4 12 4s8 3.582 8 8z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No response yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send a request to see the response here</p>
            </div>
        </div>
    </div>

    {{-- Monitoring Tab --}}
    <div x-show="activeTab === 'monitoring'" class="space-y-6">
        {{-- Authentication Controls --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Authentication</h3>
            <div class="flex space-x-3">
                <button
                    @click="setAuth()"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    🔑 Set Auth Token
                </button>
                <button
                    @click="clearAuth()"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    🚫 Clear Auth
                </button>
            </div>
        </div>

        {{-- Performance Metrics --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance Metrics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                    <div class="text-2xl font-bold text-blue-600" x-text="stats.requests || 0"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Requests</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                    <div class="text-2xl font-bold text-green-600" x-text="stats.responses || 0"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Successful</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                    <div class="text-2xl font-bold text-red-600" x-text="stats.errors || 0"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Errors</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                    <div class="text-2xl font-bold text-purple-600" x-text="(stats.avgResponseTime || 0) + 'ms'"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Avg Response</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cache Management Tab --}}
    <div x-show="activeTab === 'caching'" class="space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cache Entries</h3>
                <button
                    @click="clearCache()"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 mt-2 sm:mt-0"
                >
                    🗑️ Clear Cache
                </button>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                <template x-for="entry in cacheEntries" :key="entry.key">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white font-mono" x-text="entry.key"></h4>
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>Size: <span x-text="formatSize(entry.size)"></span></span>
                                    <span>Expires: <span x-text="formatTimestamp(entry.timestamp)"></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty State --}}
            <div x-show="cacheEntries.length === 0" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No cache entries</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Make some GET requests to see cached responses</p>
            </div>
        </div>
    </div>

    {{-- Rate Limiting Tab --}}
    <div x-show="activeTab === 'rate-limiting'" class="space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rate Limit Status</h3>
            
            <div class="space-y-4">
                <template x-for="(limit, endpoint) in rateLimits" :key="endpoint">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white font-mono" x-text="endpoint"></h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'Resets: ' + formatTimestamp(limit.resetTime)"></span>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <span>Requests</span>
                            <span x-text="(limit.requests - limit.remaining) + '/' + limit.requests"></span>
                        </div>
                        
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="limit.remaining === 0 ? 'bg-red-500' : limit.remaining < limit.requests * 0.2 ? 'bg-yellow-500' : 'bg-green-500'"
                                :style="'width: ' + ((limit.requests - limit.remaining) / limit.requests * 100) + '%'"
                            ></div>
                        </div>
                        
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span x-text="limit.remaining + ' remaining'"></span>
                            <span x-text="Math.round(((limit.requests - limit.remaining) / limit.requests) * 100) + '% used'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Logs Tab --}}
    <div x-show="activeTab === 'logs'" class="space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">API Logs</h3>
                <div class="flex space-x-2 mt-2 sm:mt-0">
                    <button
                        @click="clearLogs()"
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
                    >
                        🗑️ Clear
                    </button>
                    <button
                        @click="exportLogs()"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
                    >
                        📥 Export
                    </button>
                </div>
            </div>

            <div class="space-y-2 max-h-96 overflow-y-auto">
                <template x-for="log in logs" :key="log.timestamp">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                        :class="log.level === 'error' ? 'bg-red-100 text-red-800' : 
                                               log.level === 'warn' ? 'bg-yellow-100 text-yellow-800' : 
                                               log.level === 'info' ? 'bg-blue-100 text-blue-800' : 
                                               'bg-gray-100 text-gray-800'"
                                        x-text="log.level.toUpperCase()"
                                    ></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="new Date(log.timestamp).toLocaleTimeString()"></span>
                                </div>
                                <p class="text-sm text-gray-900 dark:text-white" x-text="log.message"></p>
                                <div x-show="log.data" class="mt-2 text-xs text-gray-600 dark:text-gray-400 font-mono bg-gray-50 dark:bg-gray-700 rounded p-2" x-text="typeof log.data === 'string' ? log.data : JSON.stringify(log.data, null, 2)"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty State --}}
            <div x-show="logs.length === 0" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No logs yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">API activity will be logged here</p>
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div x-show="isLoading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-lg font-medium text-gray-900 dark:text-white">Processing API request...</span>
            </div>
        </div>
    </div>
</div>
