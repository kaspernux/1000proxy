<x-filament-panels::page>
    <div class="space-y-6">
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    My Active Servers
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Manage your proxy servers, configurations, and monitoring
                </p>
            </div>
            
            <!-- Quick Stats -->
            <div class="flex flex-wrap gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-2 flex-1 min-w-[220px]">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                            <x-heroicon-o-server-stack class="w-7 h-7 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Servers</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ auth()->guard('customer')->user()->clients()->where('status', 'active')->count() }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-2 flex-1 min-w-[220px]">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                            <x-heroicon-o-globe-alt class="w-7 h-7 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Traffic</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format(auth()->guard('customer')->user()->clients()->sum('traffic_used_mb') / 1024, 1) }}GB
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-2 flex-1 min-w-[220px]">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                            <x-heroicon-o-map-pin class="w-7 h-7 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Locations</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ auth()->guard('customer')->user()->clients()->join('server_inbounds', 'server_clients.server_inbound_id', '=', 'server_inbounds.id')->join('servers', 'server_inbounds.server_id', '=', 'servers.id')->distinct('servers.country')->count('servers.country') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Bar (Enhanced Design) -->
        <div class="bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/30 dark:to-blue-900/30 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 mb-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center justify-center p-3 bg-primary-200 dark:bg-primary-800 rounded-2xl shadow">
                <x-heroicon-o-bolt class="w-6 h-6 text-primary-700 dark:text-primary-300" />
                </span>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">Quick Actions</h3>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button"
                onclick="window.location.reload()"
                class="inline-flex items-center px-6 py-3 border border-primary-300 dark:border-primary-700 text-sm font-semibold rounded-xl shadow text-primary-800 dark:text-primary-200 bg-white dark:bg-gray-900 hover:bg-primary-50 dark:hover:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition relative overflow-hidden group">
                <span class="absolute inset-0 bg-gradient-to-r from-primary-100 to-blue-100 dark:from-primary-900/40 dark:to-blue-900/40 opacity-0 group-hover:opacity-100 transition"></span>
                <x-heroicon-o-arrow-path class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400 animate-spin" />
                Refresh All
                </button>
                <button type="button"
                class="inline-flex items-center px-6 py-3 border border-purple-300 dark:border-purple-700 text-sm font-semibold rounded-xl shadow text-purple-800 dark:text-purple-200 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 hover:bg-purple-100 dark:hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition relative overflow-hidden group">
                <span class="absolute inset-0 bg-gradient-to-r from-purple-100 to-purple-200 dark:from-purple-900/40 dark:to-purple-800/40 opacity-0 group-hover:opacity-100 transition"></span>
                <x-heroicon-o-document-arrow-down class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" />
                Export All
                </button>
            </div>
            </div>
            <div class="mt-6 flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 font-medium shadow-sm">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                Active
            </div>
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 font-medium shadow-sm">
                <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                Suspended
            </div>
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 font-medium shadow-sm">
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                Inactive
            </div>
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300 font-medium shadow-sm">
                <span class="w-2 h-2 bg-gray-500 rounded-full"></span>
                Pending
            </div>
            </div>
        </div>

        <!-- Alerts Section -->
        @php
            $customer = auth()->guard('customer')->user();
            $expiringSoon = $customer->clients()
                ->where('expiry_time', '>', 0) // 0 means never expires
                ->where('expiry_time', '<=', (now()->addDays(7)->timestamp * 1000)) // Convert to milliseconds
                ->where('expiry_time', '>', (now()->timestamp * 1000)) // Convert to milliseconds
                ->count();
            $highUsage = $customer->clients()
                ->whereRaw('traffic_used_mb > (traffic_limit_mb * 0.8)')
                ->whereNotNull('traffic_limit_mb')
                ->count();
        @endphp

        @if($expiringSoon > 0 || $highUsage > 0)
            <div class="space-y-3">
                @if($expiringSoon > 0)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    Servers Expiring Soon
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                    <p>You have {{ $expiringSoon }} server(s) expiring within the next 7 days. Consider renewing to avoid service interruption.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($highUsage > 0)
                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-heroicon-o-signal class="h-5 w-5 text-orange-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-orange-800 dark:text-orange-200">
                                    High Traffic Usage
                                </h3>
                                <div class="mt-2 text-sm text-orange-700 dark:text-orange-300">
                                    <p>{{ $highUsage }} server(s) have used over 80% of their traffic limit. Monitor usage to avoid overages.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif


        <!-- Main Table (Full Width) -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto w-full">
                {{ $this->table }}
            </div>
        </div>

        <!-- Server Actions (Reorganized Layout) -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                            <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Server Actions</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Manage, export, and refresh your server configurations</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="inline-flex items-center px-3 py-2 border border-primary-300 dark:border-primary-700 text-xs font-semibold rounded-lg shadow-sm text-primary-800 dark:text-primary-200 bg-white dark:bg-gray-900 hover:bg-primary-50 dark:hover:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <x-heroicon-o-arrow-up class="w-4 h-4 mr-1 text-primary-600 dark:text-primary-400" />
                            Go to Quick Actions
                        </button>
                    </div>
                </div>

            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        {{ $this->form }}
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 p-4">
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Tips</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 list-disc pl-4 space-y-1">
                                <li>Use Quick Actions above to refresh or export.</li>
                                <li>Form changes apply to the selected servers.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips Section -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-light-bulb class="h-6 w-6 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Pro Tips
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Use QR codes for easy mobile configuration</li>
                            <li>Download configurations as backup files</li>
                            <li>Monitor traffic usage to avoid overages</li>
                            <li>Suspend unused servers to save resources</li>
                            <li>Export all configurations for easy management</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>