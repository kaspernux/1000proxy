{{-- XUI Connection Tester Component --}}
<div x-data="xuiConnectionTester()" class="connection-tester-container">
    <div class="connection-tester-header bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Connection Testing
            </h3>

            <div class="flex items-center space-x-3">
                <button
                    @click="testAllConnections(servers)"
                    :disabled="isTestingAll"
                    class="btn-primary btn-sm flex items-center space-x-2"
                >
                    <svg x-show="isTestingAll" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isTestingAll ? 'Testing...' : 'Test All'"></span>
                </button>

                <button
                    @click="exportTestResults()"
                    class="btn-secondary btn-sm flex items-center space-x-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Export</span>
                </button>

                <button
                    @click="clearTestHistory()"
                    class="btn-secondary btn-sm text-red-600 hover:text-red-700"
                >
                    Clear History
                </button>
            </div>
        </div>

        {{-- Test Results Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="server in servers" :key="server.id">
                <div class="server-test-card bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100" x-text="server.name"></h4>
                        <div
                            class="status-indicator w-3 h-3 rounded-full"
                            :class="getTestStatusClass(server.id)"
                        ></div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span
                                class="font-medium"
                                :class="getTestStatusClass(server.id)"
                                x-text="testResults[server.id]?.status || 'Not tested'"
                            ></span>
                        </div>

                        <div x-show="testResults[server.id]?.latency" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Latency:</span>
                            <span
                                class="font-medium"
                                :class="getLatencyColor(testResults[server.id]?.latency)"
                                x-text="`${testResults[server.id]?.latency}ms`"
                            ></span>
                        </div>

                        <div x-show="testResults[server.id]?.message" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Message:</span>
                            <span class="text-xs text-gray-500" x-text="testResults[server.id]?.message"></span>
                        </div>

                        <div x-show="testResults[server.id]?.timestamp" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Last Test:</span>
                            <span class="text-xs text-gray-500" x-text="new Date(testResults[server.id]?.timestamp).toLocaleString()"></span>
                        </div>
                    </div>

                    <button
                        @click="testConnection(server)"
                        :disabled="testResults[server.id]?.status === 'testing'"
                        class="w-full mt-3 btn-sm btn-outline"
                    >
                        <span x-show="testResults[server.id]?.status !== 'testing'">Test Connection</span>
                        <span x-show="testResults[server.id]?.status === 'testing'" class="flex items-center justify-center space-x-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Testing...</span>
                        </span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Test History --}}
    <div x-show="testHistory.length > 0" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Test History</h4>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Server</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Latency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Message</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                    <template x-for="test in testHistory.slice(0, 10)" :key="test.testId">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100" x-text="test.serverName"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': test.status === 'success',
                                        'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': test.status === 'failed',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100': test.status === 'error'
                                    }"
                                    x-text="test.status"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <span x-show="test.latency" x-text="`${test.latency}ms`"></span>
                                <span x-show="!test.latency">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <span x-show="test.duration" x-text="`${test.duration}ms`"></span>
                                <span x-show="!test.duration">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="new Date(test.timestamp).toLocaleString()"></td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" x-text="test.message || '-'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.status-indicator {
    transition: all 0.3s ease;
}

.status-unknown {
    @apply bg-gray-400;
}

.status-testing {
    @apply bg-yellow-400 animate-pulse;
}

.status-online {
    @apply bg-green-500;
}

.status-offline {
    @apply bg-red-500;
}

.performance-excellent {
    @apply text-green-600 dark:text-green-400;
}

.performance-good {
    @apply text-blue-600 dark:text-blue-400;
}

.performance-fair {
    @apply text-yellow-600 dark:text-yellow-400;
}

.performance-poor {
    @apply text-red-600 dark:text-red-400;
}

.server-test-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.server-test-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
