{{-- XUI Server Browser Component --}}
<div x-data="liveXUIServerBrowser()" x-init="init()" class="xui-server-browser">
    {{-- Header with Controls --}}
    <div class="server-browser-header bg-surface border border-border-primary rounded-lg p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="header-info">
                <h2 class="text-xl font-semibold text-primary mb-2">
                    <i class="fas fa-server mr-2"></i>
                    XUI Server Browser
                </h2>
                <p class="text-muted text-sm">
                    Monitor and manage your XUI server instances in real-time
                </p>
            </div>

            <div class="header-controls flex flex-wrap gap-3">
                <button
                    @click="loadServers()"
                    :disabled="isLoading"
                    class="interactive-secondary px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': isLoading }"></i>
                    Refresh
                </button>

                <button
                    @click="toggleAutoRefresh()"
                    :class="autoRefreshEnabled ? 'interactive-primary' : 'interactive-secondary'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-clock mr-2"></i>
                    Auto-refresh <span x-text="autoRefreshEnabled ? 'ON' : 'OFF'"></span>
                </button>
            </div>
        </div>

        {{-- Search and Filters --}}
        <div class="search-filters mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="search-input">
                <label class="block text-sm font-medium text-secondary mb-2">Search Servers</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-muted"></i>
                    <input
                        x-model="searchQuery"
                        type="text"
                        placeholder="Search by name, host, or location..."
                        class="w-full pl-10 pr-4 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                </div>
            </div>

            <div class="status-filter">
                <label class="block text-sm font-medium text-secondary mb-2">Status</label>
                <select x-model="filters.status" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                    <option value="all">All Status</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="error">Error</option>
                </select>
            </div>

            <div class="protocol-filter">
                <label class="block text-sm font-medium text-secondary mb-2">Protocol</label>
                <select x-model="filters.protocol" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                    <option value="all">All Protocols</option>
                    <option value="vless">VLESS</option>
                    <option value="vmess">VMESS</option>
                    <option value="trojan">Trojan</option>
                    <option value="shadowsocks">Shadowsocks</option>
                </select>
            </div>

            <div class="region-filter">
                <label class="block text-sm font-medium text-secondary mb-2">Region</label>
                <select x-model="filters.region" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                    <option value="all">All Regions</option>
                    <option value="us">United States</option>
                    <option value="eu">Europe</option>
                    <option value="asia">Asia</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Server Grid --}}
    <div class="server-grid">
        <template x-if="isLoading && servers.length === 0">
            <div class="loading-state text-center py-12">
                <i class="fas fa-spinner fa-spin text-3xl text-muted mb-4"></i>
                <p class="text-muted">Loading servers...</p>
            </div>
        </template>

        <template x-if="!isLoading && getFilteredServers().length === 0">
            <div class="empty-state text-center py-12">
                <i class="fas fa-server text-3xl text-muted mb-4"></i>
                <p class="text-muted">No servers found matching your criteria</p>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="server in getFilteredServers()" :key="server.id">
                <div
                    @click="selectServer(server)"
                    :class="{
                        'ring-2 ring-primary-500': selectedServer?.id === server.id,
                        'ring-1 ring-border-primary': selectedServer?.id !== server.id
                    }"
                    class="server-card bg-surface border border-border-primary rounded-lg p-6 cursor-pointer transition-all duration-200 hover:shadow-md"
                >
                    {{-- Server Header --}}
                    <div class="server-header flex items-start justify-between mb-4">
                        <div class="server-info flex-1">
                            <h3 class="font-semibold text-primary mb-1" x-text="server.name"></h3>
                            <p class="text-sm text-muted" x-text="server.host + ':' + server.port"></p>
                        </div>

                        <div class="server-status">
                            <span
                                :class="getHealthStatusClass(server.id)"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            >
                                <span
                                    class="w-2 h-2 rounded-full mr-2"
                                    :class="{
                                        'bg-status-online': healthStatus[server.id]?.status === 'online',
                                        'bg-status-offline': healthStatus[server.id]?.status === 'offline',
                                        'bg-status-maintenance': healthStatus[server.id]?.status === 'maintenance',
                                        'bg-status-unknown': !healthStatus[server.id] || healthStatus[server.id]?.status === 'error'
                                    }"
                                ></span>
                                <span x-text="healthStatus[server.id]?.status || 'Unknown'"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Server Details --}}
                    <div class="server-details space-y-3">
                        <div class="detail-row flex items-center justify-between">
                            <span class="text-sm text-secondary">Location</span>
                            <span class="text-sm font-medium" x-text="server.location"></span>
                        </div>

                        <div class="detail-row flex items-center justify-between">
                            <span class="text-sm text-secondary">Latency</span>
                            <span
                                :class="getLatencyClass(server.id)"
                                class="text-sm font-medium"
                                x-text="healthStatus[server.id]?.latency ? healthStatus[server.id].latency + 'ms' : 'N/A'"
                            ></span>
                        </div>

                        <div class="detail-row flex items-center justify-between">
                            <span class="text-sm text-secondary">Inbounds</span>
                            <span class="text-sm font-medium" x-text="healthStatus[server.id]?.inboundCount || '0'"></span>
                        </div>

                        <div class="detail-row flex items-center justify-between">
                            <span class="text-sm text-secondary">Clients</span>
                            <span class="text-sm font-medium" x-text="healthStatus[server.id]?.clientCount || '0'"></span>
                        </div>
                    </div>

                    {{-- Protocols --}}
                    <div class="protocols mt-4">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="protocol in server.protocols" :key="protocol">
                                <span class="inline-flex items-center px-2 py-1 rounded-md bg-accent text-xs font-medium text-primary">
                                    <i :class="`fas fa-${protocol === 'vless' ? 'shield-alt' : protocol === 'vmess' ? 'eye-slash' : protocol === 'trojan' ? 'horse' : 'user-secret'} mr-1`"></i>
                                    <span x-text="protocol.toUpperCase()"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="server-actions mt-4 flex gap-2">
                        <button
                            @click.stop="testConnection(server)"
                            class="flex-1 px-3 py-2 bg-primary-50 text-primary-600 rounded-lg text-sm font-medium hover:bg-primary-100 transition-colors"
                        >
                            <i class="fas fa-plug mr-1"></i>
                            Test
                        </button>

                        <button
                            @click.stop="$dispatch('open-server-settings', { server })"
                            class="flex-1 px-3 py-2 bg-secondary-50 text-secondary-600 rounded-lg text-sm font-medium hover:bg-secondary-100 transition-colors"
                        >
                            <i class="fas fa-cog mr-1"></i>
                            Settings
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Server Summary Stats --}}
    <div class="server-stats mt-6 bg-surface border border-border-primary rounded-lg p-6">
        <h3 class="text-lg font-semibold text-primary mb-4">
            <i class="fas fa-chart-bar mr-2"></i>
            Server Statistics
        </h3>

        <div class="stats-grid grid grid-cols-2 md:grid-cols-5 gap-6">
            <div class="stat-item text-center">
                <div class="stat-value text-2xl font-bold text-primary" x-text="servers.length"></div>
                <div class="stat-label text-sm text-muted">Total Servers</div>
            </div>

            <div class="stat-item text-center">
                <div class="stat-value text-2xl font-bold status-online" x-text="servers.filter(s => healthStatus[s.id]?.status === 'online').length"></div>
                <div class="stat-label text-sm text-muted">Online</div>
            </div>

            <div class="stat-item text-center">
                <div class="stat-value text-2xl font-bold status-offline" x-text="servers.filter(s => healthStatus[s.id]?.status === 'offline').length"></div>
                <div class="stat-label text-sm text-muted">Offline</div>
            </div>

            <div class="stat-item text-center">
                <div class="stat-value text-2xl font-bold text-primary" x-text="Object.values(healthStatus).reduce((sum, h) => sum + (h.inboundCount || 0), 0)"></div>
                <div class="stat-label text-sm text-muted">Total Inbounds</div>
            </div>

            <div class="stat-item text-center">
                <div class="stat-value text-2xl font-bold text-primary" x-text="Object.values(healthStatus).reduce((sum, h) => sum + (h.clientCount || 0), 0)"></div>
                <div class="stat-label text-sm text-muted">Total Clients</div>
            </div>
        </div>
    </div>
</div>

<style>
.xui-server-browser .server-card:hover {
    transform: translateY(-2px);
}

.xui-server-browser .animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.xui-server-browser .status-online {
    @apply text-success-600 bg-success-50;
}

.xui-server-browser .status-offline {
    @apply text-error-600 bg-error-50;
}

.xui-server-browser .status-maintenance {
    @apply text-warning-600 bg-warning-50;
}

.xui-server-browser .status-unknown {
    @apply text-secondary-600 bg-secondary-50;
}

.xui-server-browser .performance-excellent {
    @apply text-success-600;
}

.xui-server-browser .performance-good {
    @apply text-success-500;
}

.xui-server-browser .performance-fair {
    @apply text-warning-500;
}

.xui-server-browser .performance-poor {
    @apply text-error-500;
}
</style>
