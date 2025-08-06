<x-filament-panels::page>
    <div class="space-y-6">
        <!-                    <div class="flex space-x-2">
                        <a href="{{ \App\Filament\Customer\Pages\ServerBrowsing::getUrl() }}" 
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                            Add New Server
                        </a>er Section -->
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
            <div class="flex space-x-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-full">
                            <x-heroicon-o-server-stack class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Servers</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ auth()->guard('customer')->user()->clients()->where('status', 'active')->count() }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-full">
                            <x-heroicon-o-globe-alt class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Traffic</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format(auth()->guard('customer')->user()->clients()->sum('traffic_used_mb') / 1024, 1) }}GB
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-full">
                            <x-heroicon-o-map-pin class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Locations</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ auth()->guard('customer')->user()->clients()->join('server_inbounds', 'server_clients.server_inbound_id', '=', 'server_inbounds.id')->join('servers', 'server_inbounds.server_id', '=', 'servers.id')->distinct('servers.country')->count('servers.country') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                    
                    <div class="flex space-x-2">
                        <a href="{{ route('filament.customer.pages.server-browsing') }}" 
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                            Add New Server
                        </a>
                        
                        <button type="button" 
                                onclick="window.location.reload()"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                            Refresh All
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span>Active</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                        <span>Suspended</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
                        <span>Inactive</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-gray-500 rounded-full mr-2"></div>
                        <span>Pending</span>
                    </div>
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

        <!-- Main Table Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            {{ $this->table }}
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
