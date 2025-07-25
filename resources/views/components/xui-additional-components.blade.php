{{-- XUI Connection Tester Component --}}
<div x-data="xuiConnectionTester" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Connection Tester</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Test XUI server connections and monitor response times</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <button
                @click="testAllConnections()"
                :disabled="isTestingAll"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                <span x-show="!isTestingAll">🧪 Test All Servers</span>
                <span x-show="isTestingAll">⏳ Testing All...</span>
            </button>
            <button
                @click="exportResults()"
                :disabled="testResults.size === 0"
                class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                📊 Export Results
            </button>
        </div>
    </div>

    {{-- Test Results Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="server in servers" :key="server.id">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                {{-- Server Header --}}
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white" x-text="server.name"></h3>
                    <div class="flex items-center space-x-2">
                        <span x-text="getStatusIcon(server.id)" class="text-lg"></span>
                        <span
                            :class="getStatusColor(server.id)"
                            class="text-sm font-medium"
                            x-text="testResults.get(server.id)?.status || 'Not tested'"
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
                        <span class="text-gray-600 dark:text-gray-400">Port:</span>
                        <span class="font-mono text-gray-900 dark:text-white" x-text="server.port"></span>
                    </div>
                </div>

                {{-- Test Results --}}
                <div x-show="testResults.has(server.id)" class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Duration:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="formatDuration(testResults.get(server.id)?.duration || 0)"></span>
                    </div>
                    <div x-show="testResults.get(server.id)?.details?.response_time" class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Response Time:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="testResults.get(server.id)?.details?.response_time + 'ms'"></span>
                    </div>
                    <div x-show="testResults.get(server.id)?.timestamp" class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Tested:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="new Date(testResults.get(server.id)?.timestamp).toLocaleTimeString()"></span>
                    </div>
                    <div x-show="testResults.get(server.id)?.error" class="text-sm text-red-600 dark:text-red-400">
                        Error: <span x-text="testResults.get(server.id)?.error"></span>
                    </div>
                </div>

                {{-- Test Button --}}
                <button
                    @click="testSingleConnection(server.id)"
                    :disabled="testResults.get(server.id)?.status === 'testing'"
                    class="w-full bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-3 py-2 rounded text-sm font-medium transition-colors duration-200"
                >
                    <span x-show="testResults.get(server.id)?.status !== 'testing'">🧪 Test Connection</span>
                    <span x-show="testResults.get(server.id)?.status === 'testing'">⏳ Testing...</span>
                </button>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div x-show="servers.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No servers found</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add servers to start testing connections.</p>
    </div>
</div>

{{-- Inbound Traffic Monitor Component --}}
<div x-data="inboundTrafficMonitor" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Traffic Monitor</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Real-time inbound traffic monitoring and analytics</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            {{-- Time Range Selector --}}
            <select
                x-model="timeRange"
                @change="changeTimeRange($event.target.value)"
                class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white px-3 py-2 rounded-md text-sm"
            >
                <option value="1h">Last Hour</option>
                <option value="6h">Last 6 Hours</option>
                <option value="24h">Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
            </select>

            {{-- Real-time Toggle --}}
            <button
                @click="toggleRealTime()"
                :class="autoUpdate ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-colors duration-200"
            >
                <span x-show="autoUpdate">🔄 Real-time ON</span>
                <span x-show="!autoUpdate">⏸️ Real-time OFF</span>
            </button>

            {{-- Export Button --}}
            <button
                @click="exportChartData()"
                :disabled="!chartData && realTimeData.length === 0"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                📊 Export Data
            </button>
        </div>
    </div>

    {{-- Inbound Selector --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Inbound</label>
        <select
            x-model="selectedInbound"
            @change="selectInbound($event.target.value)"
            class="w-full max-w-md px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
        >
            <option value="">Choose an inbound...</option>
            {{-- This would be populated dynamically --}}
            <option value="1">VLESS - Port 443</option>
            <option value="2">VMess - Port 80</option>
            <option value="3">Trojan - Port 8443</option>
        </select>
    </div>

    {{-- Chart Container --}}
    <div x-show="selectedInbound" class="mb-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <canvas x-ref="trafficChart" width="400" height="200"></canvas>
        </div>
    </div>

    {{-- Real-time Stats --}}
    <div x-show="selectedInbound && realTimeData.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Current Upload</h3>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100" x-text="(realTimeData[realTimeData.length - 1]?.upload || 0).toFixed(2) + ' MB/s'"></p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Current Download</h3>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100" x-text="(realTimeData[realTimeData.length - 1]?.download || 0).toFixed(2) + ' MB/s'"></p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-purple-800 dark:text-purple-200">Total Traffic</h3>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100" x-text="((realTimeData[realTimeData.length - 1]?.upload || 0) + (realTimeData[realTimeData.length - 1]?.download || 0)).toFixed(2) + ' MB/s'"></p>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!selectedInbound" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Select an inbound</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose an inbound configuration to monitor its traffic.</p>
    </div>
</div>

{{-- XUI Server Selector Component --}}
<div x-data="xuiServerSelector" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Smart Server Selector</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">AI-powered server recommendations based on your preferences</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <button
                @click="testRecommendations()"
                :disabled="testingRecommendations"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                <span x-show="!testingRecommendations">🧪 Test Recommendations</span>
                <span x-show="testingRecommendations">⏳ Testing...</span>
            </button>
        </div>
    </div>

    {{-- User Preferences --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Preferences</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preferred Location</label>
                <input
                    x-model="userPreferences.location"
                    @input="saveUserPreferences()"
                    type="text"
                    placeholder="e.g., US, Europe, Asia"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Protocol</label>
                <select
                    x-model="userPreferences.protocol"
                    @change="saveUserPreferences()"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
                    <option value="">Any</option>
                    <option value="vless">VLESS</option>
                    <option value="vmess">VMess</option>
                    <option value="trojan">Trojan</option>
                    <option value="shadowsocks">Shadowsocks</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Latency (ms)</label>
                <input
                    x-model.number="userPreferences.maxLatency"
                    @input="saveUserPreferences()"
                    type="number"
                    min="50"
                    max="1000"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Min Speed (Mbps)</label>
                <input
                    x-model.number="userPreferences.minSpeed"
                    @input="saveUserPreferences()"
                    type="number"
                    min="1"
                    max="1000"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
            </div>
        </div>
        <div class="mt-4">
            <label class="flex items-center">
                <input
                    x-model="userPreferences.loadBalancing"
                    @change="saveUserPreferences()"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Consider load balancing (prefer servers with lower load)</span>
            </label>
        </div>
    </div>

    {{-- Recommendations --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recommended Servers</h3>

        <div class="grid grid-cols-1 gap-4">
            <template x-for="(server, index) in recommendations" :key="server.id">
                <div
                    class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow duration-200"
                    :class="selectedServer?.id === server.id ? 'ring-2 ring-blue-500 border-blue-500' : ''"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="server.name"></h4>
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="getScoreColor(server.score)"
                                    x-text="getScoreBadge(server.score)"
                                ></span>
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400" x-text="`Score: ${Math.round(server.score)}`"></span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
                                <div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Location:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white ml-1" x-text="server.location"></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Latency:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white ml-1" x-text="server.avg_latency + 'ms'"></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Speed:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white ml-1" x-text="server.avg_speed + ' Mbps'"></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Load:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white ml-1" x-text="Math.round((server.active_connections / server.max_connections) * 100) + '%'"></span>
                                </div>
                            </div>

                            {{-- Recommendation Reasons --}}
                            <div class="flex flex-wrap gap-1 mb-3">
                                <template x-for="reason in server.reasons" :key="reason">
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-md" x-text="reason"></span>
                                </template>
                            </div>

                            {{-- Test Results --}}
                            <div x-show="server.testResult" class="mt-3 p-2 bg-gray-50 dark:bg-gray-600 rounded">
                                <div x-show="server.testResult?.success" class="text-sm text-green-600 dark:text-green-400">
                                    ✅ Connection successful
                                    <span x-show="server.testResult?.latency" x-text="`(${server.testResult.latency}ms)`"></span>
                                    <span x-show="server.testResult?.speed" x-text="`• ${server.testResult.speed} Mbps`"></span>
                                </div>
                                <div x-show="server.testResult && !server.testResult?.success" class="text-sm text-red-600 dark:text-red-400">
                                    ❌ Connection failed
                                    <span x-show="server.testResult?.error" x-text="`(${server.testResult.error})`"></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2 ml-4">
                            <div class="text-right">
                                <span class="text-2xl font-bold" x-text="`#${index + 1}`" :class="index === 0 ? 'text-yellow-500' : index === 1 ? 'text-gray-400' : index === 2 ? 'text-yellow-600' : 'text-gray-500'"></span>
                            </div>
                            <button
                                @click="selectServer(server)"
                                :disabled="server.testing"
                                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                                :class="selectedServer?.id === server.id ? 'bg-green-500 hover:bg-green-600' : ''"
                            >
                                <span x-show="!server.testing" x-text="selectedServer?.id === server.id ? '✓ Selected' : 'Select'"></span>
                                <span x-show="server.testing">⏳ Testing...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="recommendations.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No recommendations available</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add servers and set your preferences to get personalized recommendations.</p>
    </div>
</div>

{{-- Client Usage Analyzer Component --}}
<div x-data="clientUsageAnalyzer" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Usage Analyzer</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Analyze client usage patterns and performance metrics</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <select
                x-model="timeRange"
                @change="loadUsageData()"
                class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white px-3 py-2 rounded-md text-sm"
            >
                <option value="24h">Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
                <option value="90d">Last 3 Months</option>
            </select>
            <button
                @click="exportUsageReport()"
                :disabled="filteredClients.length === 0"
                class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                📊 Export Report
            </button>
        </div>
    </div>

    {{-- Analytics Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Total Usage</h3>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100" x-text="formatBytes(analytics.totalUsage)"></p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Active Clients</h3>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100" x-text="analytics.activeClients"></p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Avg Usage/Client</h3>
            <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100" x-text="formatBytes(analytics.avgUsagePerClient)"></p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-purple-800 dark:text-purple-200">Top Protocol</h3>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100" x-text="analytics.topProtocol"></p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Filter</label>
            <select
                x-model="filters.status"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
                <option value="all">All Clients</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="expired">Expired</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Protocol Filter</label>
            <select
                x-model="filters.protocol"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
                <option value="all">All Protocols</option>
                <option value="vless">VLESS</option>
                <option value="vmess">VMess</option>
                <option value="trojan">Trojan</option>
                <option value="shadowsocks">Shadowsocks</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Min Usage (MB)</label>
            <input
                x-model.number="filters.usage_threshold"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
            <select
                x-model="sortBy"
                @change="sortClients($event.target.value)"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
                <option value="total_usage">Total Usage</option>
                <option value="upload">Upload</option>
                <option value="download">Download</option>
                <option value="last_seen">Last Seen</option>
                <option value="email">Email</option>
            </select>
        </div>
    </div>

    {{-- Client List --}}
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Protocol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Seen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="client in filteredClients" :key="client.id">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="client.email"></div>
                            <div x-show="client.total_gb" class="text-sm text-gray-500 dark:text-gray-400">
                                <div class="w-32 bg-gray-200 dark:bg-gray-600 rounded-full h-2 mt-1">
                                    <div
                                        class="bg-blue-600 h-2 rounded-full"
                                        :style="`width: ${getUsagePercentage(client.usage?.total_usage || 0, client.total_gb)}%`"
                                    ></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200" x-text="client.protocol.toUpperCase()"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div>Total: <span x-text="formatBytes(client.usage?.total_usage || 0)"></span></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                ↑ <span x-text="formatBytes(client.usage?.upload || 0)"></span>
                                ↓ <span x-text="formatBytes(client.usage?.download || 0)"></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="getStatusColor(client)"
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                x-text="client.enable ? (client.expiry_time && new Date(client.expiry_time) < new Date() ? 'Expired' : 'Active') : 'Disabled'"
                            ></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(client.usage?.last_seen)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button
                                @click="viewClientDetails(client.id)"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                            >
                                View Details
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Loading State --}}
    <div x-show="loading" class="text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-blue-500 bg-blue-100 dark:bg-blue-900 dark:text-blue-200 transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading usage data...
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!loading && filteredClients.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No usage data</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No clients match your current filters or no usage data is available.</p>
    </div>
</div>
