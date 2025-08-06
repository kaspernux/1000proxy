<x-filament-panels::page>
    <div class="fi-section-content-ctn">
        <!-- Mobile-First Header Section -->
        <div class="fi-section-header mb-12 pb-6">
            <div class="fi-section-header-wrapper">
                <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                    <div class="flex-1 min-w-0">
                        <h1 class="fi-section-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20 mr-3 flex-shrink-0">
                                    <x-heroicon-s-clipboard-document-list class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                </div>
                                <span class="truncate">Advanced Order Management</span>
                            </div>
                        </h1>
                        <p class="fi-section-header-description mt-2 text-sm text-gray-500 dark:text-gray-400 leading-6">
                            Comprehensive control over your proxy service orders with advanced analytics and management tools
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Statistics Dashboard -->
        <div class="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 mb-12">
            <!-- Total Orders Stat -->
            <x-filament::section class="bg-gradient-to-br from-primary-500 to-blue-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-shopping-bag class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-primary-100">Total Orders</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">
                                    {{ auth()->guard('customer')->user()->orders()->count() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center text-primary-200 text-xs">
                            <x-heroicon-o-arrow-trending-up class="h-3 w-3 mr-1" />
                            All time orders
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Active Orders Stat -->
            <x-filament::section class="bg-gradient-to-br from-success-500 to-emerald-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-check-circle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-success-100">Active Services</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">
                                    {{ auth()->guard('customer')->user()->orders()->where('status', 'completed')->count() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center text-success-200 text-xs">
                            <div class="w-2 h-2 bg-success-200 rounded-full mr-1 animate-pulse"></div>
                            Currently running
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Processing Orders Stat -->
            <x-filament::section class="bg-gradient-to-br from-warning-500 to-orange-500 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-clock class="h-6 w-6 text-white animate-pulse" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-warning-100">Processing</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">
                                    {{ auth()->guard('customer')->user()->orders()->whereIn('status', ['pending', 'processing'])->count() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center text-warning-200 text-xs">
                            <x-heroicon-o-cog-6-tooth class="h-3 w-3 mr-1 animate-spin" />
                            Being processed
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Total Investment Stat -->
            <x-filament::section class="bg-gradient-to-br from-purple-500 to-indigo-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-currency-dollar class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-purple-100">Total Investment</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">
                                    ${{ number_format(auth()->guard('customer')->user()->orders()->sum('total_amount'), 2) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center text-purple-200 text-xs">
                            <x-heroicon-o-banknotes class="h-3 w-3 mr-1" />
                            Lifetime spending
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Performance Metrics Row -->
        <div class="my-16">
            <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-3">
                <!-- Monthly Growth -->
                <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar-square class="w-5 h-5 text-primary-600" />
                        Monthly Activity
                    </div>
                </x-slot>
                
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">This Month</span>
                        <x-filament::badge color="success" size="xs">+{{ auth()->guard('customer')->user()->orders()->whereMonth('created_at', now()->month)->count() }}</x-filament::badge>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-success-500 h-2 rounded-full" style="width: {{ min(100, (auth()->guard('customer')->user()->orders()->whereMonth('created_at', now()->month)->count() / max(1, auth()->guard('customer')->user()->orders()->count())) * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ auth()->guard('customer')->user()->orders()->whereMonth('created_at', now()->month)->count() }} orders this month</p>
                </div>
            </x-filament::section>

            <!-- Success Rate -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-check-badge class="w-5 h-5 text-success-600" />
                        Success Rate
                    </div>
                </x-slot>
                
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Completion</span>
                        <x-filament::badge color="success" size="xs">{{ auth()->guard('customer')->user()->orders()->count() > 0 ? round((auth()->guard('customer')->user()->orders()->where('status', 'completed')->count() / auth()->guard('customer')->user()->orders()->count()) * 100, 1) : 0 }}%</x-filament::badge>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-success-500 h-2 rounded-full" style="width: {{ auth()->guard('customer')->user()->orders()->count() > 0 ? round((auth()->guard('customer')->user()->orders()->where('status', 'completed')->count() / auth()->guard('customer')->user()->orders()->count()) * 100, 1) : 0 }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Excellent service delivery</p>
                </div>
            </x-filament::section>

            <!-- Average Order Value -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-calculator class="w-5 h-5 text-info-600" />
                        Avg Order Value
                    </div>
                </x-slot>
                
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Per Order</span>
                        <x-filament::badge color="info" size="xs">${{ auth()->guard('customer')->user()->orders()->count() > 0 ? number_format(auth()->guard('customer')->user()->orders()->sum('total_amount') / auth()->guard('customer')->user()->orders()->count(), 2) : '0.00' }}</x-filament::badge>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">${{ auth()->guard('customer')->user()->orders()->count() > 0 ? number_format(auth()->guard('customer')->user()->orders()->sum('total_amount') / auth()->guard('customer')->user()->orders()->count(), 2) : '0.00' }}</p>
                        <p class="text-xs text-gray-500">Average investment</p>
                    </div>
                </div>
            </x-filament::section>
            </div>
        </div>

        <!-- Enhanced Table Section with Live Features -->
        <div class="my-16">
            <x-filament::section class="shadow-xl">
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                                <x-heroicon-s-table-cells class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Advanced Order Management</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Comprehensive management with real-time updates</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-filament::badge color="success" size="sm">
                                <div class="flex items-center gap-1">
                                    <div class="h-1.5 w-1.5 rounded-full bg-success-600 animate-pulse"></div>
                                    Live Updates
                                </div>
                            </x-filament::badge>
                            <x-filament::badge color="info" size="sm">
                                {{ auth()->guard('customer')->user()->orders()->count() }} Total Orders
                            </x-filament::badge>
                        </div>
                    </div>
                </x-slot>

                <div class="p-6">
                    <!-- Enhanced Filter Bar -->
                    <div class="mb-8 p-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <div class="p-2 bg-gray-200 dark:bg-gray-600 rounded-lg">
                                    <x-heroicon-o-funnel class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Smart Filters</span>
                            </div>
                            <div class="flex flex-wrap gap-3 flex-1">
                                <x-filament::button size="sm" color="primary" outlined class="flex-shrink-0">
                                    <x-heroicon-o-check-circle class="w-4 h-4 mr-2" />
                                    Active Orders
                                </x-filament::button>
                                <x-filament::button size="sm" color="warning" outlined class="flex-shrink-0">
                                    <x-heroicon-o-clock class="w-4 h-4 mr-2" />
                                    Pending
                                </x-filament::button>
                                <x-filament::button size="sm" color="info" outlined class="flex-shrink-0">
                                    <x-heroicon-o-calendar class="w-4 h-4 mr-2" />
                                    This Month
                                </x-filament::button>
                                <x-filament::button size="sm" color="success" outlined class="flex-shrink-0">
                                    <x-heroicon-o-currency-dollar class="w-4 h-4 mr-2" />
                                    High Value
                                </x-filament::button>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-path" class="flex-shrink-0">
                                    Refresh
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-adjustments-horizontal" class="flex-shrink-0">
                                    Advanced
                                </x-filament::button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="min-w-full">
                            {{ $this->table }}
                        </div>
                    </div>

                    <!-- Enhanced Footer Stats -->
                    <div class="mt-8 p-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl shadow-sm">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">This Month</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ auth()->guard('customer')->user()->orders()->whereMonth('created_at', now()->month)->count() }}</p>
                            </div>
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Last 7 Days</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ auth()->guard('customer')->user()->orders()->where('created_at', '>=', now()->subDays(7))->count() }}</p>
                            </div>
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Avg Per Month</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ auth()->guard('customer')->user()->orders()->count() > 0 ? round(auth()->guard('customer')->user()->orders()->count() / max(1, now()->diffInMonths(auth()->guard('customer')->user()->created_at) ?: 1), 1) : 0 }}</p>
                            </div>
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Success Rate</p>
                                <p class="text-2xl font-bold text-success-600">{{ auth()->guard('customer')->user()->orders()->count() > 0 ? round((auth()->guard('customer')->user()->orders()->where('status', 'completed')->count() / auth()->guard('customer')->user()->orders()->count()) * 100, 1) : 0 }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Enhanced Quick Actions Dashboard -->
        <div class="my-16">
            <x-filament::section class="shadow-xl bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-0">
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                                <x-heroicon-s-bolt class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Quick Actions & Tools</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Streamlined operations for efficient management</p>
                            </div>
                        </div>
                        <x-filament::badge color="primary" size="sm">
                            <div class="flex items-center gap-1">
                                <x-heroicon-o-sparkles class="h-3 w-3" />
                                Smart Tools
                            </div>
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="p-8">
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Enhanced Download All Action -->
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <x-filament::button
                                color="gray"
                                size="xl"
                                outlined
                                icon="heroicon-o-arrow-down-tray"
                                x-on:click="$wire.call('bulkDownloadConfigurations', [])"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-300 rounded-2xl"
                            >
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full mx-auto w-fit group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 transition-colors duration-300">
                                        <x-heroicon-o-folder-arrow-down class="w-8 h-8 text-gray-600 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">Bulk Download</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">All configurations</div>
                                        <x-filament::badge color="gray" size="sm" class="px-3 py-1">
                                            <x-heroicon-o-archive-box class="w-3 h-3 mr-1" />
                                            ZIP Archive
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>

                        <!-- Enhanced New Order Action -->
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-success-200 to-emerald-300 dark:from-success-600 dark:to-emerald-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <x-filament::button
                                color="success"
                                size="xl"
                                outlined
                                icon="heroicon-o-plus-circle"
                                x-on:click="window.location.href='{{ route('filament.customer.pages.server-browsing') }}'"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-success-200 dark:border-success-600 hover:border-success-400 dark:hover:border-success-500 transition-all duration-300 rounded-2xl"
                            >
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-success-100 dark:bg-success-900/30 rounded-full mx-auto w-fit group-hover:bg-success-200 dark:group-hover:bg-success-800/40 transition-colors duration-300">
                                        <x-heroicon-o-server class="w-8 h-8 text-success-600 dark:text-success-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">New Service</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Browse & order</div>
                                        <x-filament::badge color="success" size="sm" class="px-3 py-1">
                                            <x-heroicon-o-rocket-launch class="w-3 h-3 mr-1" />
                                            Browse Now
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>

                        <!-- Enhanced Export Action -->
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-info-200 to-blue-300 dark:from-info-600 dark:to-blue-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <x-filament::button
                                color="info"
                                size="xl"
                                outlined
                                icon="heroicon-o-document-arrow-down"
                                x-on:click="$wire.call('exportOrders', [])"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-info-200 dark:border-info-600 hover:border-info-400 dark:hover:border-info-500 transition-all duration-300 rounded-2xl"
                            >
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-info-100 dark:bg-info-900/30 rounded-full mx-auto w-fit group-hover:bg-info-200 dark:group-hover:bg-info-800/40 transition-colors duration-300">
                                        <x-heroicon-o-table-cells class="w-8 h-8 text-info-600 dark:text-info-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">Export Data</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Order history</div>
                                        <x-filament::badge color="info" size="sm" class="px-3 py-1">
                                            <x-heroicon-o-document-text class="w-3 h-3 mr-1" />
                                            CSV Format
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>

                        <!-- Enhanced Analytics Action -->
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-warning-200 to-orange-300 dark:from-warning-600 dark:to-orange-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <x-filament::button
                                color="warning"
                                size="xl"
                                outlined
                                icon="heroicon-o-chart-bar-square"
                                x-on:click="$wire.call('showFullHistory')"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-warning-200 dark:border-warning-600 hover:border-warning-400 dark:hover:border-warning-500 transition-all duration-300 rounded-2xl"
                            >
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-warning-100 dark:bg-warning-900/30 rounded-full mx-auto w-fit group-hover:bg-warning-200 dark:group-hover:bg-warning-800/40 transition-colors duration-300">
                                        <x-heroicon-o-presentation-chart-line class="w-8 h-8 text-warning-600 dark:text-warning-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">Analytics</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Full insights</div>
                                        <x-filament::badge color="warning" size="sm" class="px-3 py-1">
                                            <x-heroicon-o-chart-pie class="w-3 h-3 mr-1" />
                                            Detailed View
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>
                    </div>

                    <!-- Quick Stats Row -->
                    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-600">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ auth()->guard('customer')->user()->orders()->count() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Orders</div>
                            </div>
                            <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ auth()->guard('customer')->user()->orders()->where('status', 'completed')->count() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Active Services</div>
                            </div>
                            <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <div class="text-2xl font-bold text-info-600 dark:text-info-400">{{ auth()->guard('customer')->user()->orders()->whereMonth('created_at', now()->month)->count() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">This Month</div>
                            </div>
                            <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">${{ number_format(auth()->guard('customer')->user()->orders()->whereMonth('created_at', now()->month)->sum('total_amount'), 0) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Monthly Spent</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Enhanced Help & Information Section -->
        <div class="grid gap-6 lg:grid-cols-2 mt-12">
            <!-- Enhanced Order Status Guide -->
            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                            <x-heroicon-o-information-circle class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <span class="text-lg font-semibold">Order Status Guide</span>
                    </div>
                </x-slot>

                <x-slot name="description">
                    Track your order progression through our streamlined process
                </x-slot>

                <div class="space-y-4 p-4">
                    <!-- Pending Status -->
                    <div class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-warning-50 to-orange-50 dark:from-warning-900/10 dark:to-orange-900/10 border border-warning-200 dark:border-warning-800/30 p-4 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning-500 shadow-lg">
                                    <x-heroicon-s-clock class="h-6 w-6 text-white animate-pulse" />
                                </div>
                                <div class="absolute -top-1 -right-1 h-4 w-4 bg-warning-300 rounded-full animate-ping"></div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">Payment Processing</span>
                                    <x-filament::badge color="warning" size="xs">Step 1</x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Secure payment verification in progress</p>
                                <div class="mt-2 w-full bg-warning-200 dark:bg-warning-800 rounded-full h-1.5">
                                    <div class="bg-warning-500 h-1.5 rounded-full animate-pulse" style="width: 25%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Processing Status -->
                    <div class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 border border-blue-200 dark:border-blue-800/30 p-4 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500 shadow-lg">
                                    <x-heroicon-s-cog-6-tooth class="h-6 w-6 text-white animate-spin" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">Service Deployment</span>
                                    <x-filament::badge color="info" size="xs">Step 2</x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Configuring and deploying your proxy service</p>
                                <div class="mt-2 w-full bg-blue-200 dark:bg-blue-800 rounded-full h-1.5">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: 60%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Status -->
                    <div class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-success-50 to-emerald-50 dark:from-success-900/10 dark:to-emerald-900/10 border border-success-200 dark:border-success-800/30 p-4 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success-500 shadow-lg">
                                    <x-heroicon-s-check-circle class="h-6 w-6 text-white" />
                                </div>
                                <div class="absolute -top-1 -right-1 h-3 w-3 bg-success-300 rounded-full"></div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">Service Active</span>
                                    <x-filament::badge color="success" size="xs">Ready</x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Proxy service is live and ready to use</p>
                                <div class="mt-2 w-full bg-success-200 dark:bg-success-800 rounded-full h-1.5">
                                    <div class="bg-success-500 h-1.5 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Status Info -->
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-light-bulb class="w-4 h-4 text-yellow-500" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Pro Tip</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Most orders are processed within 5-15 minutes. You'll receive email notifications at each stage.</p>
                    </div>
                </div>
            </x-filament::section>

            <!-- Enhanced Available Actions -->
            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <x-heroicon-o-cog-6-tooth class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <span class="text-lg font-semibold">Management Tools</span>
                    </div>
                </x-slot>

                <x-slot name="description">
                    Comprehensive tools for managing your proxy services efficiently
                </x-slot>

                <div class="space-y-6 p-4">
                    <!-- Individual Order Management -->
                    <div class="p-6 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 border border-indigo-200 dark:border-indigo-800/30">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                <x-heroicon-o-cursor-arrow-rays class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Single Order Actions</h4>
                        </div>
                        <div class="space-y-3">
                            <x-filament::button
                                color="gray"
                                size="sm"
                                outlined
                                icon="heroicon-o-eye"
                                class="w-full justify-start"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span>View Order Details</span>
                                    <x-filament::badge color="gray" size="xs">Info</x-filament::badge>
                                </div>
                            </x-filament::button>
                            
                            <x-filament::button
                                color="primary"
                                size="sm"
                                outlined
                                icon="heroicon-o-arrow-down-tray"
                                class="w-full justify-start"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span>Download Config Files</span>
                                    <x-filament::badge color="primary" size="xs">Download</x-filament::badge>
                                </div>
                            </x-filament::button>
                            
                            <x-filament::button
                                color="success"
                                size="sm"
                                outlined
                                icon="heroicon-o-arrow-path"
                                class="w-full justify-start"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span>Renew Service</span>
                                    <x-filament::badge color="success" size="xs">Extend</x-filament::badge>
                                </div>
                            </x-filament::button>
                        </div>
                    </div>

                    <!-- Bulk Operations -->
                    <div class="p-6 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/10 dark:to-teal-900/10 border border-emerald-200 dark:border-emerald-800/30">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                <x-heroicon-o-squares-2x2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Bulk Operations</h4>
                        </div>
                        <div class="space-y-3">
                            <x-filament::button
                                color="info"
                                size="sm"
                                outlined
                                icon="heroicon-o-document-arrow-down"
                                class="w-full justify-start"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span>Export Order History</span>
                                    <x-filament::badge color="info" size="xs">CSV</x-filament::badge>
                                </div>
                            </x-filament::button>
                            
                            <x-filament::button
                                color="warning"
                                size="sm"
                                outlined
                                icon="heroicon-o-folder-arrow-down"
                                class="w-full justify-start"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span>Bulk Config Download</span>
                                    <x-filament::badge color="warning" size="xs">ZIP</x-filament::badge>
                                </div>
                            </x-filament::button>
                            
                            <x-filament::button
                                color="purple"
                                size="sm"
                                outlined
                                icon="heroicon-o-funnel"
                                class="w-full justify-start"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span>Advanced Filters</span>
                                    <x-filament::badge color="purple" size="xs">Pro</x-filament::badge>
                                </div>
                            </x-filament::button>
                        </div>
                    </div>

                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
