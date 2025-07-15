{{-- XUI Integration Interface Main View --}}
<div x-data="xuiIntegrationManager()" class="xui-integration-interface">
    {{-- Header Navigation --}}
    <div class="interface-header bg-white dark:bg-gray-800 shadow-md mb-6">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center space-x-3">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span>XUI Integration Interface</span>
                </h2>

                <div class="flex items-center space-x-3">
                    <div class="status-indicator flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">System Online</span>
                    </div>

                    <button
                        @click="refreshAllComponents()"
                        class="btn-secondary btn-sm flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Refresh All</span>
                    </button>

                    <button
                        @click="exportAllData()"
                        class="btn-primary btn-sm flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Export Data</span>
                    </button>
                </div>
            </div>

            {{-- Navigation Tabs --}}
            <nav class="flex space-x-1">
                <button
                    @click="setView('overview')"
                    :class="currentView === 'overview' ? 'nav-tab-active' : 'nav-tab'"
                    class="nav-tab"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                    Overview
                </button>

                <button
                    @click="setView('servers')"
                    :class="currentView === 'servers' ? 'nav-tab-active' : 'nav-tab'"
                    class="nav-tab"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h6a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2m8-8a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V4z"></path>
                    </svg>
                    Servers
                </button>

                <button
                    @click="setView('inbounds')"
                    :class="currentView === 'inbounds' ? 'nav-tab-active' : 'nav-tab'"
                    class="nav-tab"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Inbounds
                </button>

                <button
                    @click="setView('traffic')"
                    :class="currentView === 'traffic' ? 'nav-tab-active' : 'nav-tab'"
                    class="nav-tab"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Traffic
                </button>

                <button
                    @click="setView('clients')"
                    :class="currentView === 'clients' ? 'nav-tab-active' : 'nav-tab'"
                    class="nav-tab"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Clients
                </button>

                <button
                    @click="setView('testing')"
                    :class="currentView === 'testing' ? 'nav-tab-active' : 'nav-tab'"
                    class="nav-tab"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Testing
                </button>
            </nav>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="interface-content">
        {{-- Overview Tab --}}
        <div x-show="currentView === 'overview'" class="overview-section">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Server Selector Component --}}
                <div class="overview-card">
                    <x-xui-server-selector />
                </div>

                {{-- Quick Stats --}}
                <div class="overview-card bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Stats</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Active Servers</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Total Inbounds</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Active Clients</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Total Traffic</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Servers Tab --}}
        <div x-show="currentView === 'servers'" class="servers-section">
            <x-xui-server-browser />
        </div>

        {{-- Inbounds Tab --}}
        <div x-show="currentView === 'inbounds'" class="inbounds-section">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2">
                    <x-xui-inbound-manager />
                </div>
                <div>
                    <x-client-configuration-builder />
                </div>
            </div>
        </div>

        {{-- Traffic Tab --}}
        <div x-show="currentView === 'traffic'" class="traffic-section">
            <x-inbound-traffic-monitor />
        </div>

        {{-- Clients Tab --}}
        <div x-show="currentView === 'clients'" class="clients-section">
            <x-client-usage-analyzer />
        </div>

        {{-- Testing Tab --}}
        <div x-show="currentView === 'testing'" class="testing-section">
            <x-xui-connection-tester />
        </div>
    </div>

    {{-- Global Notifications --}}
    <div x-data="{ notifications: [] }"
         @notification.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), 5000)"
         class="fixed bottom-4 right-4 z-50">
        <template x-for="notification in notifications" :key="notification">
            <div
                class="notification-toast mb-2 p-4 rounded-lg shadow-lg transform transition-all duration-300"
                :class="{
                    'bg-green-500 text-white': notification.type === 'success',
                    'bg-red-500 text-white': notification.type === 'error',
                    'bg-yellow-500 text-white': notification.type === 'warning',
                    'bg-blue-500 text-white': notification.type === 'info'
                }"
                x-transition:enter="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="translate-x-full"
            >
                <div class="flex items-center space-x-2">
                    <svg x-show="notification.type === 'success'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <svg x-show="notification.type === 'error'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <span x-text="notification.message"></span>
                </div>
            </div>
        </template>
    </div>
</div>

<style>
.nav-tab {
    @apply px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg flex items-center space-x-2 transition-colors duration-200;
}

.nav-tab-active {
    @apply px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center space-x-2;
}

.overview-card {
    @apply transition-transform duration-200;
}

.overview-card:hover {
    @apply transform scale-105;
}

.notification-toast {
    min-width: 300px;
    max-width: 400px;
}

.interface-content > div {
    @apply transition-opacity duration-300;
}

.status-indicator {
    @apply bg-gray-100 dark:bg-gray-700 rounded-full px-3 py-1;
}
</style>
