
<section class="server-browser-container bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
    <!-- Real-time status header -->
    <header class="flex flex-col md:flex-row items-center justify-between gap-4 px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-r from-blue-50/60 to-indigo-50/60 dark:from-gray-800/60 dark:to-gray-700/60">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <span class="inline-flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ $serverPlans->total() }} servers available
                </span>
            </span>
            @if($lastUpdate)
                <span class="text-xs text-gray-500">Last updated: {{ $lastUpdate->diffForHumans() }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <!-- View mode toggle -->
            <nav class="flex bg-white dark:bg-gray-800 rounded-lg p-1 shadow-sm" aria-label="View mode toggle">
                <button
                    wire:click="changeViewMode('grid')"
                    class="p-2 rounded {{ $viewMode === 'grid' ? 'bg-blue-500 text-white' : 'text-gray-500 hover:text-gray-700' }} transition-all duration-200"
                    aria-current="{{ $viewMode === 'grid' ? 'page' : false }}"
                    aria-label="Grid view"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </button>
                <button
                    wire:click="changeViewMode('list')"
                    class="p-2 rounded {{ $viewMode === 'list' ? 'bg-blue-500 text-white' : 'text-gray-500 hover:text-gray-700' }} transition-all duration-200"
                    aria-current="{{ $viewMode === 'list' ? 'page' : false }}"
                    aria-label="List view"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </nav>
            <!-- Refresh button -->
            <button
                wire:click="refreshServerData"
                class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 group"
                wire:loading.attr="disabled"
                aria-label="Refresh server data"
            >
                <svg class="w-4 h-4 text-gray-500 group-hover:text-blue-500 transition-colors duration-200"
                     wire:loading.class="animate-spin"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </header>


    <!-- Advanced Filters Section -->
    <section class="mb-6 px-6 pt-6">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-800" x-data="{ filtersOpen: true }">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Advanced Filters</h2>
                <div class="flex items-center gap-3">
                    <button
                        wire:click="clearFilters"
                        class="text-sm text-gray-500 hover:text-red-500 font-semibold transition-colors duration-200"
                    >Clear All</button>
                    <button
                        @click="filtersOpen = !filtersOpen"
                        class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
                        {{-- In server-side test context Alpine isn't executed; avoid PHP interpreting filtersOpen as constant --}}
                        aria-expanded="true"
                        aria-controls="filters-content"
                    >
                        <svg class="w-5 h-5 transition-transform duration-200"
                             :class="{ 'rotate-180': !filtersOpen }"
                             fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div x-show="filtersOpen" x-transition class="space-y-4" id="filters-content">
                <!-- Search and Quick Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search input -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Search Servers</label>
                        <div class="relative">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="searchTerm"
                                placeholder="Search by name, location, or features..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- Sort by -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                        <select
                            wire:model.live="sortBy"
                            class="w-full py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                        >
                            <option value="location_first">Location First</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="speed_high">Fastest First</option>
                            <option value="popularity">Most Popular</option>
                            <option value="newest">Newest First</option>
                        </select>
                    </div>
                    <!-- Items per page -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Items Per Page</label>
                        <select
                            wire:model.live="itemsPerPage"
                            class="w-full py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                        >
                            <option value="6">6</option>
                            <option value="12">12</option>
                            <option value="24">24</option>
                            <option value="48">48</option>
                        </select>
                    </div>
                </div>
                <!-- Category Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Country filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Country</label>
                        <select
                            wire:model.live="selectedCountry"
                            class="w-full py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">All Countries</option>
                            @foreach($countries as $country)
                                <option value="{{ $country['code'] }}">{{ $country['flag'] }} {{ $country['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Category filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Category</label>
                        <select
                            wire:model.live="selectedCategory"
                            class="w-full py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Brand filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Brand</label>
                        <select
                            wire:model.live="selectedBrand"
                            class="w-full py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->slug }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Protocol filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Protocol</label>
                        <select
                            wire:model.live="selectedProtocol"
                            class="w-full py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">All Protocols</option>
                            @foreach($protocols as $protocol)
                                <option value="{{ $protocol }}">{{ $protocol }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- Range Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Price range -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Price Range (${{ $priceRange[0] }} - ${{ $priceRange[1] }})</label>
                        <div class="flex items-center gap-4">
                            <input
                                type="number"
                                wire:model.live.debounce.500ms="priceRange.0"
                                placeholder="Min"
                                class="w-20 py-1 px-2 border border-gray-300 dark:border-gray-700 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white text-sm"
                            >
                            <span class="text-gray-500">to</span>
                            <input
                                type="number"
                                wire:model.live.debounce.500ms="priceRange.1"
                                placeholder="Max"
                                class="w-20 py-1 px-2 border border-gray-300 dark:border-gray-700 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white text-sm"
                            >
                        </div>
                    </div>
                    <!-- Speed range -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Speed Range ({{ $speedRange[0] }} - {{ $speedRange[1] }} Mbps)</label>
                        <div class="flex items-center gap-4">
                            <input
                                type="number"
                                wire:model.live.debounce.500ms="speedRange.0"
                                placeholder="Min"
                                class="w-20 py-1 px-2 border border-gray-300 dark:border-gray-700 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white text-sm"
                            >
                            <span class="text-gray-500">to</span>
                            <input
                                type="number"
                                wire:model.live.debounce.500ms="speedRange.1"
                                placeholder="Max"
                                class="w-20 py-1 px-2 border border-gray-300 dark:border-gray-700 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white text-sm"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Loading Overlay -->
    <div wire:loading.flex class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 flex items-center gap-4 shadow-xl border border-gray-100 dark:border-gray-800">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <span class="text-lg font-bold text-gray-900 dark:text-white">Loading servers...</span>
        </div>
    </div>


    <!-- Server Results -->
    <section class="transition-all duration-300 px-6 pb-8" wire:loading.class="opacity-50">
        @if($serverPlans->count() > 0)
            <!-- Results count -->
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $serverPlans->firstItem() }}-{{ $serverPlans->lastItem() }} of {{ $serverPlans->total() }} servers
                </span>
            </div>
            <!-- Server Grid/List -->
            <div class="server-grid {{ $viewMode === 'list' ? 'space-y-4' : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' }}">
                @foreach($serverPlans as $plan)
                    @php
                        $serverHealth = $this->getServerHealthStatus($plan->server_id);
                    @endphp
                    <article class="proxy-card group hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 {{ $viewMode === 'list' ? 'flex items-center p-4' : 'p-6' }} bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 relative">
                        <!-- Server status indicator -->
                        <div class="absolute top-3 right-3 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full {{ $serverHealth['status'] === 'online' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
                            @if($serverHealth['response_time'])
                                <span class="text-xs text-gray-500">{{ $serverHealth['response_time'] }}ms</span>
                            @endif
                        </div>
                        <!-- Server info -->
                        <div class="{{ $viewMode === 'list' ? 'flex-1 flex items-center gap-4' : '' }}">
                            <!-- Flag and country -->
                            <div class="{{ $viewMode === 'list' ? 'flex items-center gap-2' : 'flex items-center justify-between mb-4' }}">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $plan->server->flag }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $plan->server->country }}</span>
                                </div>
                                @if($viewMode !== 'list')
                                    <button
                                        wire:click="toggleServerFavorite({{ $plan->id }})"
                                        class="text-gray-400 hover:text-red-500 transition-colors duration-200"
                                        aria-label="Toggle favorite"
                                    >
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            <!-- Plan details -->
                            <div class="{{ $viewMode === 'list' ? 'flex-1' : '' }}">
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">{{ $plan->name }}</h3>
                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $plan->protocol }}
                                    </span>
                                    @if($plan->bandwidth_mbps)
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $plan->bandwidth_mbps }} Mbps
                                        </span>
                                    @endif
                                </div>
                                @if($plan->description && $viewMode !== 'list')
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ Str::limit($plan->description, 100) }}</p>
                                @endif
                            </div>
                            <!-- Price and actions -->
                            <div class="{{ $viewMode === 'list' ? 'flex items-center gap-4' : 'flex items-center justify-between' }}">
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">${{ number_format($plan->price, 2) }}</div>
                                    @if($plan->original_price && $plan->original_price > $plan->price)
                                        <div class="text-sm text-gray-500 line-through">${{ number_format($plan->original_price, 2) }}</div>
                                    @endif
                                </div>
                                <button
                                    wire:click="quickAddToCart({{ $plan->id }})"
                                    class="btn-primary touch-target"
                                    wire:loading.attr="disabled"
                                    wire:target="quickAddToCart({{ $plan->id }})"
                                >
                                    <span wire:loading.remove wire:target="quickAddToCart({{ $plan->id }})">Add to Cart</span>
                                    <span wire:loading wire:target="quickAddToCart({{ $plan->id }})">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Adding...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <!-- Pagination -->
            <div class="mt-8">
                {{ $serverPlans->links() }}
            </div>
        @else
            <!-- No results state -->
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0121 12c0-4.411-3.589-8-8-8s-8 3.589-8 8c0 2.152.851 4.103 2.233 5.535z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-bold text-gray-900 dark:text-white">No servers found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or search terms.</p>
                <div class="mt-6">
                    <button
                        wire:click="clearFilters"
                        class="btn-primary"
                    >Clear all filters</button>
                </div>
            </div>
        @endif
    </section>

    <!-- Auto-refresh script -->
    <script>
        document.addEventListener('livewire:init', () => {
            setInterval(() => {
                @this.pollForUpdates();
            }, 300000); // Poll every 5 minutes
        });
    </script>
</section>
