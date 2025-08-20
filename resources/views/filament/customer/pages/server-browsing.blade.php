<x-filament-panels::page>
    <div class="fi-section-content-ctn" wire:ignore.self>
        <!-- Hero Header -->
        <div class="fi-section-header mb-12 pb-6">
            <div class="fi-section-header-wrapper">
                <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                    <div class="flex-1 min-w-0">
                        <h1 class="fi-section-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20 mr-3 flex-shrink-0">
                                    <x-heroicon-s-server-stack class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                </div>
                                <span class="truncate">Advanced Server Marketplace</span>
                            </div>
                        </h1>
                        <p class="fi-section-header-description mt-2 text-sm text-gray-500 dark:text-gray-400 leading-6">
                            Browse, filter, and select optimized proxy servers with real-time insights & smart tools.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gradient Statistic Cards -->
        <div class="grid gap-6 md:gap-8 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 mb-12">
            <!-- Total Servers -->
            <div class="group relative p-5 rounded-2xl shadow-lg ring-1 ring-white/10 overflow-hidden text-white bg-gradient-to-br from-blue-600 to-indigo-600">
                <div class="absolute -top-10 -right-10 w-36 h-36 bg-white/10 rounded-full blur-2xl"></div>
                <div class="relative flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-white/80 mb-1">Total Servers</p>
                        <p class="text-3xl font-bold">{{ \App\Models\Server::where('is_active', true)->count() }}</p>
                        <p class="mt-2 flex items-center text-xs text-white/85">
                            <x-heroicon-o-arrow-trending-up class="h-4 w-4 mr-1 text-white/90" /> Active lineup
                        </p>
                    </div>
                </div>
            </div>

            <!-- Active Servers -->
            <div class="group relative p-5 rounded-2xl shadow-lg ring-1 ring-white/10 overflow-hidden text-white bg-gradient-to-br from-emerald-600 to-green-600">
                <div class="absolute -top-10 -right-10 w-36 h-36 bg-white/10 rounded-full blur-2xl"></div>
                <div class="relative flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Active Now</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ \App\Models\Server::where('is_active', true)->where('status', 'active')->count() }}</p>
                        <p class="mt-2 flex items-center text-xs text-success-600 dark:text-success-400">
                            <span class="w-2 h-2 bg-success-500 rounded-full mr-1 animate-pulse"></span> Online capacity
                        </p>
                    </div>
                </div>
            </div>

            <!-- Countries -->
            <x-filament::section class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Countries</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ \App\Models\Server::where('is_active', true)->distinct('country')->count('country') }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Global coverage</p>
                    </div>
                </div>
            </x-filament::section>

            <!-- Starting Price -->
            <x-filament::section class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Starting At</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">${{ \App\Models\ServerPlan::whereHas('server', function($query) { $query->where('is_active', true); })->where('is_active', true)->min('price') ?? '0' }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Entry pricing</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Main area: Left sidebar filters + right content (servers) --}}
        <div class="grid py-4 grid-cols-1 lg:grid-cols-3 gap-6 mb-16 items-stretch">
            {{-- Enhanced Server Filters (matching height on desktop) --}}
            <x-filament::section class="py-6 h-full flex flex-col lg:col-span-1">
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-funnel class="w-5 h-5 text-gray-500" />
                            <span class="font-medium">Server Filters</span>
                        </div>
                        <button type="button" wire:click="resetFilters()" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <x-heroicon-o-arrow-path class="w-4 h-4" /> Reset
                        </button>
                    </div>
                </x-slot>
                <div class="space-y-5">
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-7 w-7 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500" />
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Search & Refine</span>
                        </div>
                        <div class="space-y-3">
                            {{ $this->form }}
                        </div>
                    </div>

                    {{-- Top Locations --}}
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-globe-alt class="w-4 h-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Top Locations</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @forelse($topCountries as $c)
                                @php($active = $filters['country'] === $c)
                                <button type="button" wire:click="$set('filters.country', '{{ $c }}')" class="px-2.5 py-1 rounded-md text-xs font-medium border transition {{ $active ? 'bg-primary-600 text-white border-primary-600' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                                    <span class="inline-flex items-center gap-1"><x-heroicon-o-map-pin class="w-3 h-3" /> {{ strtoupper($c) }}</span>
                                </button>
                            @empty
                                <p class="text-xs text-gray-500">No data</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Budget Shortcuts --}}
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-currency-dollar class="w-4 h-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Budget</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach([[10,'Under $10'],[25,'Under $25'],[50,'Under $50']] as $b)
                                @php($active = (int)($filters['price_max'] ?? 0) === $b[0])
                                <button type="button" wire:click="$set('filters.price_max', {{ $b[0] }})" class="px-3 py-1.5 rounded-md text-xs font-medium border transition {{ $active ? 'bg-primary-600 text-white border-primary-600' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600' }}">{{ $b[1] }}</button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Active Filters Summary with chips --}}
                    @php($activeFilters = collect($filters)->filter(fn($v,$k)=>$v && $k !== 'sort'))
                    @if($activeFilters->isNotEmpty())
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                            <div class="flex items-center gap-2 mb-3">
                                <x-heroicon-o-adjustments-horizontal class="w-4 h-4 text-gray-500" />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Active Filters</span>
                                <button type="button" wire:click="resetFilters()" class="ml-auto text-xs text-primary-600 hover:underline">Clear all</button>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @foreach($activeFilters as $k => $v)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                        <x-heroicon-o-tag class="w-3 h-3" /> {{ ucfirst(str_replace('_',' ',$k)) }}: {{ $v }}
                                        <button type="button" wire:click="$set('filters.{{$k}}', null)" class="ml-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-200">
                                            <x-heroicon-o-x-mark class="w-3 h-3" />
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Quick Actions --}}
                    <div class="flex gap-2">
                        <button type="button" wire:click="getServerRecommendations" class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 rounded-md text-xs font-medium border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <x-heroicon-o-sparkles class="w-4 h-4" /> Recommend
                        </button>
                        @php($fav = $filters['favorites_only'])
                        <button type="button" wire:click="showOnlyFavorites" class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 rounded-md text-xs font-medium border transition {{ $fav ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <x-heroicon-o-heart class="w-4 h-4" /> Favorites
                        </button>
                    </div>
                </div>
            </x-filament::section>

            {{-- Right content: Servers list --}}
            <div class="space-y-6 h-full lg:col-span-2">
                {{-- Servers Grid --}}
                <x-filament::section class="py-6 h-full flex flex-col">
                    <x-slot name="heading">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-server-stack class="w-5 h-5 text-primary-600" />
                                Available Servers
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                @php($activeFilters = collect($filters)->filter(fn($v,$k)=>$v && !in_array($k, ['sort','favorites_only'])))
                                <div class="hidden sm:flex items-center gap-2">
                                    <span class="text-xs">{{ $activeFilters->count() }} filters active</span>
                                    @if($activeFilters->count() > 0)
                                        <button type="button" wire:click="resetFilters" class="text-xs text-primary-600 hover:underline">Clear all</button>
                                    @endif
                                </div>
                                <x-filament::button size="xs" color="gray" wire:click="$toggle('compactView')" icon="{{ $compactView ? 'heroicon-o-rectangle-group' : 'heroicon-o-squares-2x2' }}">
                                    {{ $compactView ? 'Comfort' : 'Compact' }}
                                </x-filament::button>
                                <div>{{ count($servers) }} found</div>
                            </div>
                        </div>
                    </x-slot>

                    @if(count($servers) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-{{ $compactView ? '3' : '4' }}">
                            @foreach($servers as $server)
                                <div class="group relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-{{ $compactView ? '3' : '4' }} hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200">
                                    {{-- Server Header --}}
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-{{ $compactView ? '0.5' : '1' }} truncate">
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
                                    <div class="space-y-{{ $compactView ? '1.5' : '2' }} mb-{{ $compactView ? '3' : '4' }}">
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
                                                <span class="text-{{ $compactView ? 'xs' : 'sm' }} font-bold text-primary-600 dark:text-primary-400">
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
                                                @php($stars = $this->starArray($server))
                                                @foreach($stars as $filled)
                                                    <x-heroicon-s-star class="w-{{ $compactView ? '2' : '2.5' }} h-{{ $compactView ? '2' : '2.5' }} {{ $filled ? 'text-warning-400' : 'text-gray-300 dark:text-gray-600' }}" />
                                                @endforeach
                                                <span class="text-xs text-gray-500 ml-1">{{ $this->formatRating($server) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Action Button --}}
                                    <x-filament::button
                                        color="primary" 
                                        icon="heroicon-o-arrow-right"
                                        size="{{ $compactView ? '2xs' : 'xs' }}"
                                        class="w-full"
                                        wire:click="selectServer({{ $server->id }})"
                                    >
                                        Select Server
                                    </x-filament::button>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex flex-col items-center gap-2">
                            @if($hasMore)
                                <x-filament::button
                                    color="gray"
                                    icon="heroicon-o-plus"
                                    size="sm"
                                    wire:click="loadMore"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove>Load More Servers</span>
                                    <span wire:loading.flex class="items-center gap-1">
                                        <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" /> Loading...
                                    </span>
                                </x-filament::button>
                            @else
                                <p class="text-xs text-gray-500">End of results</p>
                            @endif
                        </div>
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
            </div>
        </div>

        {{-- Retain remaining original content sections below --}}

    {{-- Row 2: Protocol Comparison and Featured Banner --}}
    <div class="grid py-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6 mb-16">

            {{-- Protocols (clean list) --}}
            <x-filament::section class="py-6">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar class="w-5 h-5 text-gray-500" />
                        <span class="font-medium">Protocols</span>
                    </div>
                </x-slot>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    @foreach([
                        ['VLESS','heroicon-o-bolt','Fastest'],
                        ['VMess','heroicon-o-shield-check','Secure'],
                        ['Trojan','heroicon-o-eye-slash','Stealth'],
                        ['Shadowsocks','heroicon-o-bolt-slash','Light'],
                        ['SOCKS5','heroicon-o-globe-alt','Universal'],
                    ] as $p)
                        <li class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                <x-filament::icon :icon="$p[1]" class="w-4 h-4 text-gray-500" />
                                <span class="text-gray-900 dark:text-gray-200 font-medium">{{ $p[0] }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $p[2] }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>

            {{-- Featured (subtle callout) --}}
            <x-filament::section class="py-6">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-star class="w-5 h-5 text-gray-500" />
                        <span class="font-medium">Featured</span>
                    </div>
                </x-slot>
                <div class="space-y-3 text-sm">
                    <p class="text-gray-600 dark:text-gray-400">High-performance servers selected for reliability & uptime.</p>
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-gift class="w-4 h-4 text-gray-500" />
                        <span class="text-gray-700 dark:text-gray-300">Intro discounts available.</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">Updated daily.</div>
                </div>
            </x-filament::section>

            {{-- Plans (quick filters by duration) --}}
            <x-filament::section class="py-6">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-gray-500" />
                        <span class="font-medium">Plans</span>
                    </div>
                </x-slot>
                <div class="space-y-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Quickly filter by subscription length.</p>
                    <div class="flex flex-wrap gap-2">
                        @php($options = [[30,'Monthly'],[90,'Quarterly'],[180,'Semi-Annual'],[365,'Annual']])
                        @foreach($options as [$days,$label])
                            @php($active = (int)($filters['plan_days'] ?? 0) === $days)
                            <button
                                type="button"
                                wire:click="$set('filters.plan_days', {{ $days }})"
                                class="px-3 py-1.5 rounded-md text-xs font-medium border transition {{ $active ? 'bg-primary-600 text-white border-primary-600' : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                        @if($filters['plan_days'])
                            <button type="button" wire:click="$set('filters.plan_days', null)" class="px-3 py-1.5 rounded-md text-xs font-medium border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Clear
                            </button>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Row 3: Enhanced Help Section and System Status --}}
        <div class="py-4 space-y-4 md:space-y-6 mt-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                {{-- Enhanced Help Section --}}
                <x-filament::section class="py-6">
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
                <x-filament::section class="py-6">
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
        {{-- Auto-refresh JavaScript --}}
        @script
            setInterval(function() { $wire.call('loadServers'); }, 30000);
        @endscript
    </div>
</x-filament-panels::page>
