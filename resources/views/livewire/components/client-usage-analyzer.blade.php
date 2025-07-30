{{-- Client Usage Analyzer Component --}}
<div x-data="clientUsageAnalyzer()" class="client-usage-analyzer">
    {{-- Header with Controls --}}
    <div class="analyzer-header bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center space-x-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span>Client Usage Analytics</span>
            </h3>

            <div class="flex items-center space-x-3">
                <button
                    @click="loadClientsData()"
                    :disabled="isLoading"
                    class="btn-secondary btn-sm flex items-center space-x-2"
                >
                    <svg
                        class="w-4 h-4"
                        :class="{ 'animate-spin': isLoading }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Refresh</span>
                </button>

                <button
                    @click="exportClientData()"
                    class="btn-primary btn-sm flex items-center space-x-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Export</span>
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filters-section bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                    <input
                        type="text"
                        x-model="filters.searchTerm"
                        @input.debounce.500ms="updateFilters()"
                        placeholder="Email, UUID, or SubID..."
                        class="input-field"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Range</label>
                    <select
                        x-model="filters.timeRange"
                        @change="updateFilters()"
                        class="select-input"
                    >
                        <option value="1d">Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="90d">Last 90 Days</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select
                        x-model="filters.clientStatus"
                        @change="updateFilters()"
                        class="select-input"
                    >
                        <option value="all">All Clients</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                    <select
                        x-model="filters.sortBy"
                        @change="updateFilters()"
                        class="select-input"
                    >
                        <option value="traffic">Total Traffic</option>
                        <option value="upload">Upload</option>
                        <option value="download">Download</option>
                        <option value="email">Email</option>
                        <option value="lastConnection">Last Connection</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Order</label>
                    <select
                        x-model="filters.sortOrder"
                        @change="updateFilters()"
                        class="select-input"
                    >
                        <option value="desc">Descending</option>
                        <option value="asc">Ascending</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Overview --}}
    <div class="analytics-overview grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-800 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Clients</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" x-text="analytics.totalClients"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-800 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Clients</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" x-text="analytics.activeClients"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-800 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Traffic</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" x-text="formatBytes(analytics.totalTraffic)"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-800 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Avg Usage</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" x-text="formatBytes(analytics.averageUsage)"></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Usage Distribution Chart --}}
    <div class="usage-distribution bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Usage Distribution</h4>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <template x-for="(count, range) in analytics.usageDistribution" :key="range">
                <div class="distribution-item text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="count"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400" x-text="range"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Top Users --}}
    <div class="top-users bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Top 10 Users by Traffic</h4>
        <div class="space-y-3">
            <template x-for="(client, index) in analytics.topUsers" :key="client.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-6 h-6 bg-blue-500 text-white rounded-full text-xs font-bold" x-text="index + 1"></div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100" x-text="client.email"></div>
                            <div class="text-sm text-gray-500" x-text="client.uuid?.substring(0, 8) + '...'"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium text-gray-900 dark:text-gray-100" x-text="formatBytes((client.upload || 0) + (client.download || 0))"></div>
                        <div class="text-sm text-gray-500">
                            ↑<span x-text="formatBytes(client.upload || 0)"></span>
                            ↓<span x-text="formatBytes(client.download || 0)"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Clients Table --}}
    <div class="clients-table bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100">
                Client Details (<span x-text="getFilteredClients().length"></span> clients)
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Traffic</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Expiry</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                    <template x-for="client in getFilteredClients()" :key="client.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="client.email"></div>
                                        <div class="text-sm text-gray-500 font-mono" x-text="client.uuid?.substring(0, 16) + '...'"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': client.status === 'active',
                                        'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': client.status === 'inactive',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100': client.status === 'expired',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100': client.status === 'suspended'
                                    }"
                                    x-text="client.status"
                                ></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mb-1">
                                    <div
                                        class="h-2 rounded-full transition-all duration-500"
                                        :class="getUsageStatusClass(getUsagePercentage(client))"
                                        :style="`width: ${getUsagePercentage(client)}%`"
                                    ></div>
                                </div>
                                <div class="text-xs text-gray-500" x-text="`${getUsagePercentage(client).toFixed(1)}%`"></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <div>Total: <span x-text="formatBytes((client.upload || 0) + (client.download || 0))"></span></div>
                                <div class="text-xs text-gray-500">
                                    ↑<span x-text="formatBytes(client.upload || 0)"></span>
                                    ↓<span x-text="formatBytes(client.download || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <div x-show="client.expiryTime">
                                    <span x-text="new Date(client.expiryTime).toLocaleDateString()"></span>
                                    <div class="text-xs" :class="getDaysUntilExpiry(client) < 7 ? 'text-red-500' : 'text-gray-500'">
                                        <span x-show="getDaysUntilExpiry(client) > 0" x-text="`${getDaysUntilExpiry(client)} days left`"></span>
                                        <span x-show="getDaysUntilExpiry(client) <= 0" class="text-red-500">Expired</span>
                                    </div>
                                </div>
                                <span x-show="!client.expiryTime" class="text-gray-500">No expiry</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="selectClient(client)"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        View
                                    </button>
                                    <button
                                        @click="resetClientTraffic(client.id)"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Selected Client Details Modal --}}
    <div x-show="selectedClient" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @click.away="selectedClient = null">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">Client Details</h3>

                    <div x-show="selectedClient" class="space-y-3">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Email:</span>
                                <div class="text-gray-900 dark:text-gray-100" x-text="selectedClient?.email"></div>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Status:</span>
                                <div class="text-gray-900 dark:text-gray-100" x-text="selectedClient?.status"></div>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">UUID:</span>
                                <div class="text-gray-900 dark:text-gray-100 font-mono text-xs" x-text="selectedClient?.uuid"></div>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">SubID:</span>
                                <div class="text-gray-900 dark:text-gray-100 font-mono text-xs" x-text="selectedClient?.subId"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        @click="selectedClient = null"
                        class="btn-secondary btn-sm"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.usage-normal {
    @apply bg-green-500;
}

.usage-moderate {
    @apply bg-yellow-500;
}

.usage-warning {
    @apply bg-orange-500;
}

.usage-critical {
    @apply bg-red-500;
}

.distribution-item {
    @apply p-4 bg-gray-50 dark:bg-gray-700 rounded-lg transition-transform duration-200;
}

.distribution-item:hover {
    @apply transform scale-105;
}

.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
