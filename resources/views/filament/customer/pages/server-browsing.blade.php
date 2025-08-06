<x-filament-panels::page>
    <div class="space-y-4 md:space-y-6">
        {{-- Row 1: Server Statistics and Server Filters --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            {{-- Server Statistics --}}
            <x-filament::section class="lg:col-span-2">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar-square class="w-5 h-5 text-primary-600" />
                        Server Statistics
                    </div>
                </x-slot>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-3">
                    <div class="text-center p-2 md:p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-100 dark:border-primary-800">
                        <div class="flex flex-col items-center gap-1 md:gap-2">
                            <div class="p-2 bg-primary-100 dark:bg-primary-800 rounded-full">
                                <x-heroicon-o-server class="w-4 h-4 md:w-5 md:h-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Total Servers</p>
                                <p class="text-sm md:text-lg font-bold text-gray-900 dark:text-white">
                                    {{ \App\Models\Server::where('is_active', true)->count() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center p-2 md:p-3 bg-success-50 dark:bg-success-900/20 rounded-lg border border-success-100 dark:border-success-800">
                        <div class="flex flex-col items-center gap-1 md:gap-2">
                            <div class="p-2 bg-success-100 dark:bg-success-800 rounded-full">
                                <x-heroicon-o-check-circle class="w-4 h-4 md:w-5 md:h-5 text-success-600 dark:text-success-400" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Active Now</p>
                                <p class="text-sm md:text-lg font-bold text-gray-900 dark:text-white">
                                    {{ \App\Models\Server::where('is_active', true)->where('status', 'active')->count() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center p-2 md:p-3 bg-info-50 dark:bg-info-900/20 rounded-lg border border-info-100 dark:border-info-800">
                        <div class="flex flex-col items-center gap-1 md:gap-2">
                            <div class="p-2 bg-info-100 dark:bg-info-800 rounded-full">
                                <x-heroicon-o-map-pin class="w-4 h-4 md:w-5 md:h-5 text-info-600 dark:text-info-400" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Countries</p>
                                <p class="text-sm md:text-lg font-bold text-gray-900 dark:text-white">
                                    {{ \App\Models\Server::where('is_active', true)->distinct('country')->count('country') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center p-2 md:p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-100 dark:border-warning-800">
                        <div class="flex flex-col items-center gap-1 md:gap-2">
                            <div class="p-2 bg-warning-100 dark:bg-warning-800 rounded-full">
                                <x-heroicon-o-currency-dollar class="w-4 h-4 md:w-5 md:h-5 text-warning-600 dark:text-warning-400" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">From</p>
                                <p class="text-sm md:text-lg font-bold text-gray-900 dark:text-white">
                                    ${{ \App\Models\ServerPlan::whereHas('server', function($query) { $query->where('is_active', true); })->where('is_active', true)->min('price') ?? '0' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Enhanced Server Filters --}}
            <x-filament::section class="lg:col-span-1">
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-funnel class="w-5 h-5 text-primary-600" />
                            Server Filters
                        </div>
                        <x-filament::button
                            color="gray"
                            icon="heroicon-o-arrow-path"
                            size="xs"
                            wire:click="resetFilters()"
                            class="text-xs"
                        >
                            Reset
                        </x-filament::button>
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    {{-- Enhanced Search Form --}}
                    <div class="p-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-lg border border-primary-100 dark:border-primary-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-1 bg-primary-100 dark:bg-primary-800 rounded-full">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4 text-primary-600" />
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Smart Search</span>
                        </div>
                        
                        {{-- Main Form --}}
                        <div class="space-y-3">
                            {{ $this->form }}
                        </div>
                    </div>

                    {{-- Quick Location Filter --}}
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-globe-alt class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Popular Locations</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <x-filament::button
                                color="primary"
                                size="xs"
                                class="text-xs justify-center"
                                wire:click="$set('filters.country', 'us')"
                            >
                                🇺🇸 US
                            </x-filament::button>
                            <x-filament::button
                                color="success"
                                size="xs"
                                class="text-xs justify-center"
                                wire:click="$set('filters.country', 'eu')"
                            >
                                🇪🇺 EU
                            </x-filament::button>
                            <x-filament::button
                                color="info"
                                size="xs"
                                class="text-xs justify-center"
                                wire:click="$set('filters.country', 'asia')"
                            >
                                🌏 Asia
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Quick Price Filter --}}
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-currency-dollar class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Budget Range</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <x-filament::button
                                color="warning"
                                size="xs"
                                class="text-xs justify-center"
                                wire:click="$set('filters.max_price', 10)"
                            >
                                Under $10
                            </x-filament::button>
                            <x-filament::button
                                color="danger"
                                size="xs"
                                class="text-xs justify-center"
                                wire:click="$set('filters.max_price', 25)"
                            >
                                Under $25
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Filter Status Display --}}
                    @if(array_filter($this->filters))
                    <div class="p-3 bg-info-50 dark:bg-info-900/20 rounded-lg border border-info-200 dark:border-info-800">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-check-circle class="w-4 h-4 text-info-600" />
                            <span class="text-sm font-medium text-info-900 dark:text-info-100">Active Filters</span>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @foreach($this->filters as $key => $value)
                                @if($value && $key !== 'sort')
                                    <x-filament::badge color="info" size="xs">
                                        {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}
                                    </x-filament::badge>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Quick Actions --}}
                    <div class="grid grid-cols-2 gap-2">
                        <x-filament::button
                            color="primary"
                            icon="heroicon-o-sparkles"
                            size="sm"
                            class="justify-center"
                            wire:click="getServerRecommendations"
                        >
                            Recommend
                        </x-filament::button>
                        
                        <x-filament::button
                            color="{{ $this->filters['favorites_only'] ? 'danger' : 'gray' }}"
                            icon="heroicon-o-heart"
                            size="sm"
                            class="justify-center"
                            wire:click="showOnlyFavorites"
                        >
                            Favorites
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Row 2: Quick Filters, Protocol Comparison, and Featured Banner --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
            {{-- Quick Filters --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-adjustments-horizontal class="w-5 h-5 text-primary-600" />
                        Quick Filters
                    </div>
                </x-slot>
                
                <div class="grid grid-cols-2 md:grid-cols-1 gap-2">
                    <x-filament::button
                        color="primary"
                        icon="heroicon-o-flag"
                        size="sm"
                        class="justify-start"
                        wire:click="$set('filters.country', 'us')"
                    >
                        🇺🇸 US
                    </x-filament::button>
                    
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-flag" 
                        size="sm"
                        class="justify-start"
                        wire:click="$set('filters.country', 'eu')"
                    >
                        🇪🇺 EU
                    </x-filament::button>
                    
                    <x-filament::button
                        color="info"
                        icon="heroicon-o-flag"
                        size="sm"
                        class="justify-start"
                        wire:click="$set('filters.country', 'asia')"
                    >
                        🌏 Asia
                    </x-filament::button>
                    
                    <x-filament::button
                        color="warning"
                        icon="heroicon-o-currency-dollar"
                        size="sm"
                        class="justify-start"
                        wire:click="$set('filters.max_price', 10)"
                    >
                        Under $10
                    </x-filament::button>
                    
                    <x-filament::button
                        color="danger"
                        icon="heroicon-o-bolt"
                        size="sm"
                        class="justify-start"
                        wire:click="$set('filters.features', ['high_speed'])"
                    >
                        High Speed
                    </x-filament::button>
                    
                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-arrow-path"
                        size="sm"
                        class="justify-start"
                        wire:click="resetFilters()"
                    >
                        Reset All
                    </x-filament::button>
                </div>
            </x-filament::section>

            {{-- Protocol Comparison --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-600" />
                        Protocols
                    </div>
                </x-slot>
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-bolt class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">VLESS</span>
                        </div>
                        <x-filament::badge color="success" size="xs">Fastest</x-filament::badge>
                    </div>
                    
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-shield-check class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">VMess</span>
                        </div>
                        <x-filament::badge color="primary" size="xs">Secure</x-filament::badge>
                    </div>
                    
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-eye-slash class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Trojan</span>
                        </div>
                        <x-filament::badge color="info" size="xs">Stealth</x-filament::badge>
                    </div>
                    
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-bolt-slash class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Shadowsocks</span>
                        </div>
                        <x-filament::badge color="warning" size="xs">Light</x-filament::badge>
                    </div>
                    
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-globe-alt class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">SOCKS5</span>
                        </div>
                        <x-filament::badge color="gray" size="xs">Universal</x-filament::badge>
                    </div>
                </div>
            </x-filament::section>

            {{-- Featured Banner --}}
            <x-filament::section class="md:col-span-2 xl:col-span-1 bg-gradient-to-br from-primary-500 to-purple-600 text-white">
                <div class="text-center">
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <x-heroicon-o-star class="w-5 h-5" />
                        <h3 class="text-lg font-bold">Featured</h3>
                    </div>
                    <p class="text-primary-100 text-sm mb-3">High-performance servers with 99.9% uptime</p>
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <x-heroicon-o-gift class="w-4 h-4" />
                        <span class="text-sm font-bold">Special Offers</span>
                    </div>
                    <div class="text-primary-200 text-xs">Up to 50% off first month</div>
                </div>
            </x-filament::section>
        </div>

        {{-- Row 3: Main Content Area - Servers --}}
        <div class="space-y-4 md:space-y-6">
            {{-- Servers Grid --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-server-stack class="w-5 h-5 text-primary-600" />
                            Available Servers
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ count($servers) }} servers found
                        </div>
                    </div>
                </x-slot>
                
                @if(count($servers) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($servers as $server)
                            <div class="group relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200">
                                {{-- Server Header --}}
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1 truncate">
                                            {{ $server->name }}
                                        </h4>
                                        <x-filament::badge 
                                            :color="$server->status === 'active' ? 'success' : 'danger'"
                                            size="xs"
                                        >
                                            {{ ucfirst($server->status) }}
                                        </x-filament::badge>
                                    </div>
                                    
                                    {{-- Favorite Button --}}
                                    <x-filament::icon-button
                                        icon="heroicon-o-heart"
                                        :color="$this->isFavorite($server->id) ? 'danger' : 'gray'"
                                        size="xs"
                                        wire:click="toggleFavorite({{ $server->id }})"
                                    />
                                </div>
                                
                                {{-- Server Details --}}
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-map-pin class="w-3 h-3" />
                                            <span>Location</span>
                                        </div>
                                        <span class="text-gray-900 dark:text-white font-medium">{{ $server->country }}</span>
                                    </div>
                                    
                                    @if($server->type)
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-cog-6-tooth class="w-3 h-3" />
                                            <span>Type</span>
                                        </div>
                                        <span class="text-gray-900 dark:text-white font-medium">{{ ucfirst($server->type) }}</span>
                                    </div>
                                    @endif
                                    
                                    @if($server->plans && $server->plans->count() > 0)
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-currency-dollar class="w-3 h-3" />
                                            <span>Price</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                                ${{ $server->plans->first()->price }}
                                            </span>
                                            <span class="text-xs text-gray-500">/mo</span>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- Performance Indicator --}}
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-signal class="w-3 h-3" />
                                            <span>Rating</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <x-heroicon-s-star class="w-2.5 h-2.5 {{ $i <= 4 ? 'text-warning-400' : 'text-gray-300' }}" />
                                            @endfor
                                            <span class="text-xs text-gray-500 ml-1">4.0</span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Action Button --}}
                                <x-filament::button
                                    color="primary" 
                                    icon="heroicon-o-arrow-right"
                                    size="xs"
                                    class="w-full"
                                    wire:click="selectServer({{ $server->id }})"
                                >
                                    Select Server
                                </x-filament::button>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($hasMore)
                    <div class="mt-6 text-center">
                        <x-filament::button
                            color="gray"
                            icon="heroicon-o-plus"
                            size="sm"
                            wire:click="loadMore"
                        >
                            Load More Servers
                        </x-filament::button>
                    </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <div class="flex flex-col items-center gap-4">
                            <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full">
                                <x-heroicon-o-server-stack class="w-8 h-8 text-gray-400" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">No servers found</h3>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">Try adjusting your filters to see more results.</p>
                                <x-filament::button
                                    color="primary"
                                    icon="heroicon-o-arrow-path"
                                    size="sm"
                                    wire:click="resetFilters()"
                                >
                                    Reset Filters
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::section>

            {{-- Row 4: Enhanced Help Section and System Status --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                {{-- Enhanced Help Section --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-question-mark-circle class="w-5 h-5 text-primary-600" />
                            Need Help?
                        </div>
                    </x-slot>
                    
                    <div class="space-y-3">
                        {{-- Help Tips --}}
                        <div class="space-y-2">
                            <div class="flex items-start gap-3 p-2 bg-success-50 dark:bg-success-900/20 rounded-lg">
                                <div class="p-1 bg-success-100 dark:bg-success-800 rounded-full flex-shrink-0 mt-0.5">
                                    <x-heroicon-o-map-pin class="w-3 h-3 text-success-600" />
                                </div>
                                <div class="min-w-0">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white block">Choose Closest Location</span>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">Lower latency = better performance</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3 p-2 bg-info-50 dark:bg-info-900/20 rounded-lg">
                                <div class="p-1 bg-info-100 dark:bg-info-800 rounded-full flex-shrink-0 mt-0.5">
                                    <x-heroicon-o-shield-check class="w-3 h-3 text-info-600" />
                                </div>
                                <div class="min-w-0">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white block">99.9% Uptime Guarantee</span>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">Reliable connections you can trust</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3 p-2 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                                <div class="p-1 bg-warning-100 dark:bg-warning-800 rounded-full flex-shrink-0 mt-0.5">
                                    <x-heroicon-o-cog-6-tooth class="w-3 h-3 text-warning-600" />
                                </div>
                                <div class="min-w-0">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white block">Match Your Usage</span>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">Consider speed vs. price needs</p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Action Buttons --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <x-filament::button
                                color="primary"
                                icon="heroicon-o-sparkles"
                                size="sm"
                                wire:click="getServerRecommendations"
                                class="justify-center"
                            >
                                Get Recommendations
                            </x-filament::button>
                            
                            <x-filament::button
                                color="gray"
                                icon="heroicon-o-chat-bubble-left-right"
                                size="sm"
                                class="justify-center"
                            >
                                Live Support
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Enhanced System Status --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-information-circle class="w-5 h-5 text-primary-600" />
                                System Status
                            </div>
                            <x-filament::badge color="success" size="xs">
                                <div class="flex items-center gap-1">
                                    <div class="w-1.5 h-1.5 bg-success-600 rounded-full animate-pulse"></div>
                                    All Systems Operational
                                </div>
                            </x-filament::badge>
                        </div>
                    </x-slot>
                    
                    <div class="space-y-3">
                        {{-- Status Indicators --}}
                        <div class="grid grid-cols-1 gap-2">
                            <div class="flex items-center justify-between p-2 bg-success-50 dark:bg-success-900/20 rounded-lg border border-success-100 dark:border-success-800">
                                <div class="flex items-center gap-2">
                                    <div class="p-1 bg-success-100 dark:bg-success-800 rounded-full">
                                        <x-heroicon-o-check-circle class="w-3 h-3 text-success-600" />
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Server Network</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-2 h-2 bg-success-500 rounded-full"></div>
                                    <span class="text-xs text-success-600 font-medium">Online</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between p-2 bg-info-50 dark:bg-info-900/20 rounded-lg border border-info-100 dark:border-info-800">
                                <div class="flex items-center gap-2">
                                    <div class="p-1 bg-info-100 dark:bg-info-800 rounded-full">
                                        <x-heroicon-o-arrow-path class="w-3 h-3 text-info-600 animate-spin" />
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Auto-refresh</span>
                                </div>
                                <span class="text-xs text-info-600 font-medium">30s</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <div class="p-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                                        <x-heroicon-o-clock class="w-3 h-3 text-gray-600" />
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Last updated</span>
                                </div>
                                <span class="text-xs text-gray-500 font-medium">{{ now()->format('H:i') }}</span>
                            </div>
                        </div>

                        {{-- Performance Metrics --}}
                        <div class="p-3 bg-gradient-to-r from-primary-50 to-purple-50 dark:from-primary-900/20 dark:to-purple-900/20 rounded-lg border border-primary-100 dark:border-primary-800">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Network Performance</span>
                                <x-filament::badge color="primary" size="xs">Excellent</x-filament::badge>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Uptime</p>
                                    <p class="text-sm font-bold text-success-600">99.9%</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Latency</p>
                                    <p class="text-sm font-bold text-info-600">12ms</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Load</p>
                                    <p class="text-sm font-bold text-warning-600">Low</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>

    {{-- Auto-refresh JavaScript --}}
    @script
    <script>
        // Auto-refresh functionality
        setInterval(function() {
            $wire.call('loadServers');
        }, 30000); // 30 seconds
        
        // Add smooth transitions for interactive elements
        Alpine.magic('smoothTransition', () => {
            return (element) => {
                element.style.transition = 'all 0.2s ease-in-out';
            }
        });
    </script>
    @endscript
</x-filament-panels::page>
