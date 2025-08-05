<div>
    {{-- Modern Hero Section --}}
    <div class="relative bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 overflow-hidden min-h-screen flex items-center">
        <!-- Animated background elements -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/15 to-yellow-500/15 animate-pulse"></div>
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
        </div>
        
        <!-- Floating shapes with enhanced animations -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-yellow-400/25 to-blue-400/25 rounded-full blur-3xl animate-bounce duration-[6000ms]"></div>
            <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-yellow-400/15 rounded-full blur-3xl animate-pulse duration-[8000ms]"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-2xl animate-spin duration-[20000ms]"></div>
        </div>

        
        <div class="relative z-10 mx-auto max-w-7xl px-8 py-32 sm:py-40 lg:px-12 xl:px-16 w-full">
            <div class="text-center space-y-12 lg:space-y-16 xl:space-y-20">
                <!-- Status Badge with enhanced styling -->
                <div class="mb-12 flex justify-center">
                    <div class="inline-flex items-center rounded-full bg-blue-500/10 px-6 py-3 text-sm font-medium text-blue-400 ring-1 ring-blue-500/20 backdrop-blur-sm hover:bg-blue-500/20 hover:scale-105 transition-all duration-300 cursor-default">
                        <div class="mr-3 h-3 w-3 bg-blue-400 rounded-full animate-pulse"></div>
                        <span class="tracking-wide">Live Proxy Network • 99.9% Uptime</span>
                    </div>
                </div>

                <!-- Main Headline with enhanced typography -->
                <h1 class="text-5xl font-extrabold tracking-tight text-white sm:text-7xl lg:text-8xl xl:text-9xl leading-tight">
                    <span class="block mb-4 animate-fade-in-up">Premium</span>
                    <span class="bg-gradient-to-r from-blue-400 via-yellow-400 to-blue-500 bg-clip-text text-transparent animate-fade-in-up animation-delay-200">
                        Proxy Solutions
                    </span>
                </h1>

                <!-- Subtitle with better line height and spacing -->
                <p class="mx-auto mt-8 max-w-3xl text-xl leading-relaxed text-gray-300 sm:text-2xl lg:text-3xl font-light tracking-wide">
                    Unlock the power of our global proxy network. Fast, secure, and reliable connections for 
                    <span class="text-blue-400 font-semibold hover:text-blue-300 transition-colors">web scraping</span>, 
                    <span class="text-yellow-400 font-semibold hover:text-yellow-300 transition-colors">market research</span>, and 
                    <span class="text-blue-300 font-semibold hover:text-blue-200 transition-colors">business automation</span>.
                </p>

                <!-- Action Buttons with enhanced spacing and effects -->
                <div class="mt-16 flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="/servers" wire:navigate
                       class="group relative w-full sm:w-auto inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-yellow-600 rounded-2xl shadow-2xl hover:shadow-blue-500/25 transition-all duration-500 hover:scale-110 transform focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                        <span class="relative z-10 tracking-wide">Explore Proxy Plans</span>
                        <svg class="ml-4 h-6 w-6 transition-transform group-hover:translate-x-2 duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-yellow-600 rounded-2xl blur opacity-50 group-hover:opacity-75 transition-opacity duration-500"></div>
                    </a>
                    
                    <button onclick="document.getElementById('demo-section').scrollIntoView({behavior: 'smooth'})"
                            class="group w-full sm:w-auto inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-white border-2 border-white/20 rounded-2xl backdrop-blur-sm hover:border-white/40 hover:bg-white/10 transition-all duration-500 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-white/25">
                        <svg class="mr-4 h-6 w-6 transition-transform group-hover:rotate-12 duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M12 5v.01M12 19v.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="tracking-wide">Watch Demo</span>
                    </button>
                </div>

                <!-- Stats Row with enhanced grid and animations -->
                <div class="mt-20 grid grid-cols-1 sm:grid-cols-3 gap-8 lg:gap-12 text-center">
                    <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105">
                        <div class="text-5xl lg:text-6xl font-bold text-white mb-3 group-hover:text-blue-400 transition-colors duration-300 tracking-tight">
                            {{ number_format($this->categories->count()) }}+
                        </div>
                        <div class="text-gray-400 font-medium text-lg tracking-wide">Proxy Categories</div>
                    </div>
                    <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105">
                        <div class="text-5xl lg:text-6xl font-bold text-white mb-3 group-hover:text-yellow-400 transition-colors duration-300 tracking-tight">
                            {{ number_format($this->brands->count()) }}+
                        </div>
                        <div class="text-gray-400 font-medium text-lg tracking-wide">Trusted Brands</div>
                    </div>
                    <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105">
                        <div class="text-5xl lg:text-6xl font-bold text-white mb-3 group-hover:text-blue-300 transition-colors duration-300 tracking-tight">
                            100K+
                        </div>
                        <div class="text-gray-400 font-medium text-lg tracking-wide">Active IPs</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Hero Section End --}}

    {{-- Enhanced Search & Filtering Section Start --}}
    <div class="w-full bg-gradient-to-br from-green-900 via-gray-800 to-gray-900 py-20 lg:py-24 relative overflow-hidden">
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 30px 30px;"></div>
        </div>
        
        <!-- Enhanced background effects -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/8 to-yellow-500/8"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-400/10 to-transparent rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-yellow-400/10 to-transparent rounded-full blur-3xl"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 relative">
            <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-10 lg:p-16 xl:p-20 ring-1 ring-white/20 shadow-2xl">
                <h2 class="text-3xl lg:text-4xl font-bold text-white text-center mb-10 tracking-tight">Find Your Perfect Proxy Solution</h2>

                {{-- Search Bar with enhanced styling and informative text --}}
                <div class="mb-10">
                    <div class="mb-4">
                        <label class="block text-lg font-semibold text-white mb-3 tracking-wide flex items-center">
                            <svg class="mr-3 h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search for a Server
                        </label>
                        <p class="text-blue-200 text-sm mb-4 ml-9 leading-relaxed">
                            Enter any proxy type, location, brand name, or use case
                        </p>
                    </div>
                    <div class="relative group">
                        <input type="text"
                               wire:model.live.debounce.300ms="searchTerm"
                               placeholder="Try: 'residential proxy', 'US servers', 'Instagram', 'web scraping', 'high speed datacenter'..."
                               class="w-full px-6 py-4 pl-14 rounded-2xl border-0 bg-white backdrop-blur-sm focus:ring-4 focus:ring-blue-500/50 focus:bg-white transition-all duration-300 text-lg text-black placeholder-gray-400 placeholder:text-sm shadow-lg">
                        <svg class="absolute left-5 top-1/2 transform -translate-y-1/2 h-6 w-6 text-gray-500 group-focus-within:text-blue-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        @if($searchTerm)
                            <button wire:click="$set('searchTerm', '')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100 transition-all duration-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Quick Filters with enhanced grid and informative text --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10 mb-8">
                    <div class="space-y-4">
                        <div class="mb-4">
                            <label class="block text-lg font-semibold text-white mb-3 tracking-wide flex items-center">
                                <svg class="mr-3 h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Choose a Server Category
                            </label>
                            <p class="text-yellow-200 text-sm mb-4 ml-9 leading-relaxed">
                                Select the type of proxy service that best fits your use case
                            </p>
                        </div>
                        <select wire:model.live="selectedCategory" class="w-full px-4 py-3 rounded-xl border-0 bg-white backdrop-blur-sm focus:ring-4 focus:ring-yellow-500/50 transition-all duration-300 text-gray-900 font-medium shadow-lg hover:shadow-yellow-500/20">
                            <option value="" class="bg-white text-black py-2">All Categories</option>
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}" class="bg-white text-black py-2">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-4">
                        <div class="mb-4">
                            <label class="block text-lg font-semibold text-white mb-3 tracking-wide flex items-center">
                                <svg class="mr-3 h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                Choose a Brand
                            </label>
                            <p class="text-green-200 text-sm mb-4 ml-9 leading-relaxed">
                                Filter by trusted proxy providers and premium brands
                            </p>
                        </div>
                        <select wire:model.live="selectedBrand" class="w-full px-4 py-3 rounded-xl border-0 bg-white backdrop-blur-sm focus:ring-4 focus:ring-green-500/50 transition-all duration-300 text-gray-900 font-medium shadow-lg hover:shadow-green-500/20">
                            <option value="" class="bg-white text-black py-2">All Brands</option>
                            @foreach($this->brands as $brand)
                                <option value="{{ $brand->id }}" class="bg-white text-black py-2">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Search Results Preview with enhanced styling and visibility --}}
                @if($searchTerm || $selectedCategory || $selectedBrand)
                    <div class="mt-8 bg-gradient-to-br from-gray-800/95 to-gray-900/95 backdrop-blur-lg rounded-2xl p-8 shadow-2xl ring-1 ring-blue-500/30 border border-blue-400/20">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-2xl font-bold text-white tracking-tight flex items-center">
                                <svg class="mr-3 h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                {{ $filteredServers->count() }} Results Found
                            </h3>
                            <a href="{{ route('servers.index', array_filter(['search' => $searchTerm, 'category' => $selectedCategory, 'brand' => $selectedBrand])) }}" 
                               wire:navigate
                               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 group shadow-lg hover:shadow-blue-500/25 transform hover:scale-105">
                                <span>View All</span>
                                <svg class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1 duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="space-y-4 max-h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-blue-500 scrollbar-track-gray-700 pr-2">
                            @forelse($filteredServers->take(5) as $server)
                                <div class="flex items-center justify-between p-5 bg-gradient-to-r from-gray-700/80 to-gray-800/80 hover:from-blue-800/50 hover:to-blue-900/50 rounded-xl transition-all duration-300 group ring-1 ring-gray-600/50 hover:ring-blue-400/50 shadow-lg hover:shadow-xl hover:shadow-blue-500/20 transform hover:scale-[1.02]">
                                    <div class="flex items-center space-x-4">
                                        <!-- Brand Image Only -->
                                        <div class="relative">
                                            <img src="{{ $server->brand->image ? url('storage/'.$server->brand->image) : '/default-brand.png' }}" 
                                                 alt="{{ $server->brand->name }}" 
                                                 class="w-14 h-14 rounded-xl object-cover ring-2 ring-blue-500/50 group-hover:ring-blue-400 transition-all duration-300 shadow-md">
                                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full ring-2 ring-gray-800 flex items-center justify-center">
                                                <div class="w-2 h-2 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Server Information -->
                                        <div class="space-y-1 flex-1">
                                            <div class="font-bold text-white group-hover:text-blue-300 transition-colors duration-300 text-lg">{{ $server->name }}</div>
                                            <div class="flex items-center space-x-3">
                                                <div class="flex items-center space-x-1">
                                                    <svg class="h-3 w-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                                    </svg>
                                                    <span class="text-blue-300 text-sm font-medium">{{ $server->brand->name }}</span>
                                                </div>
                                                @if($server->category)
                                                <div class="flex items-center space-x-1">
                                                    <svg class="h-3 w-3 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                    </svg>
                                                    <span class="text-yellow-300 text-sm font-medium">{{ $server->category->name }}</span>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="flex items-center text-xs text-gray-400">
                                                <svg class="mr-1 h-3 w-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Active • High Performance
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-blue-400 font-bold text-xl group-hover:text-blue-300 transition-colors duration-300">
                                            ${{ number_format($server->plans->min('price'), 2) }}
                                        </div>
                                        <div class="text-gray-400 text-sm font-medium">/month</div>
                                        <div class="text-xs text-green-400 font-medium mt-1">
                                            {{ $server->plans->count() }} plans available
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12 bg-gradient-to-br from-gray-800/50 to-gray-900/50 rounded-xl ring-1 ring-gray-600/30">
                                    <svg class="mx-auto h-16 w-16 text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m-3-16v4m6 6l4-4m0 0l-4-4m4 4H9"></path>
                                    </svg>
                                    <p class="text-xl font-bold text-white mb-2">No servers found matching your criteria</p>
                                    <p class="text-gray-400 max-w-md mx-auto">Try adjusting your search terms or filters to find more results</p>
                                </div>
                            @endforelse
                        </div>
                        
                        {{-- Enhanced search summary --}}
                        <div class="mt-6 pt-6 border-t border-gray-600/50">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    @if($searchTerm)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-blue-600/20 text-blue-300 text-sm font-medium ring-1 ring-blue-500/30">
                                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                            "{{ $searchTerm }}"
                                        </span>
                                    @endif
                                    @if($selectedCategory)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-yellow-600/20 text-yellow-300 text-sm font-medium ring-1 ring-yellow-500/30">
                                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                            Category: {{ $this->categories->where('id', $selectedCategory)->first()->name ?? 'Selected' }}
                                        </span>
                                    @endif
                                    @if($selectedBrand)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-green-600/20 text-green-300 text-sm font-medium ring-1 ring-green-500/30">
                                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                            </svg>
                                            Brand: {{ $this->brands->where('id', $selectedBrand)->first()->name ?? 'Selected' }}
                                        </span>
                                    @endif
                                </div>
                                <button wire:click="$set('searchTerm', ''); $set('selectedCategory', ''); $set('selectedBrand', '')" 
                                        class="inline-flex items-center px-3 py-1 text-gray-400 hover:text-white text-sm font-medium transition-colors duration-200">
                                    <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- Enhanced Search & Filtering Section End --}}

    {{-- Enhanced Categories Section Start --}}
    <section id="categories" class="relative bg-gradient-to-br from-gray-900 via-blue-900/10 to-gray-900 py-32 sm:py-40 overflow-hidden">
        <!-- Background Effects with enhanced patterns -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/8 to-yellow-500/8"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-400/15 to-transparent rounded-full blur-3xl animate-pulse duration-[8000ms]"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-yellow-400/15 to-transparent rounded-full blur-3xl animate-pulse duration-[6000ms]"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-purple-400/5 to-pink-400/5 rounded-full blur-3xl animate-spin duration-[30000ms]"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-12 xl:px-16">
            <!-- Section Header with enhanced spacing -->
            <div class="text-center mb-28 lg:mb-36 xl:mb-40 space-y-10 lg:space-y-12">
                <div class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-100/10 to-yellow-100/10 px-8 py-3 text-sm font-medium text-blue-400 ring-1 ring-blue-400/20 backdrop-blur-sm mb-10 hover:scale-105 transition-transform duration-300">
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span class="tracking-wider">Proxy Categories</span>
                </div>
                
                <h2 class="text-5xl lg:text-7xl xl:text-8xl font-bold tracking-tight text-white leading-tight">
                    <span class="block mb-4">Choose Your Perfect</span>
                    <span class="bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent">Proxy Solution</span>
                </h2>
                
                <p class="mt-8 text-xl lg:text-2xl leading-relaxed text-gray-300 max-w-4xl mx-auto font-light tracking-wide">
                    Discover our comprehensive range of proxy services designed to meet every business need. 
                    From high-speed residential proxies to enterprise data center solutions.
                </p>
            </div>
            
            <!-- Categories Grid with enhanced responsive design -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10 lg:gap-12 xl:gap-16 mb-28 lg:mb-32">
                @foreach($this->categories as $index => $serverCategory)
                <div class="group relative" wire:key="{{$serverCategory->id}}" style="animation-delay: {{ $index * 100 }}ms">
                    <a href="/servers?selected_categories[0]={{ $serverCategory->id }}" wire:navigate 
                       class="block relative bg-gradient-to-br from-white/8 to-gray-800/40 backdrop-blur-lg rounded-3xl p-10 lg:p-12 xl:p-14 ring-1 ring-white/15 hover:ring-blue-400/60 transition-all duration-700 hover:shadow-2xl hover:shadow-blue-500/25 transform hover:scale-110 hover:-translate-y-2 h-full group focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                        
                        <!-- Enhanced Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/8 to-yellow-500/8 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                        
                        <!-- Content with better spacing -->
                        <div class="relative space-y-8">
                            <!-- Icon and Badge with enhanced positioning -->
                            <div class="flex items-start justify-between">
                                <div class="relative">
                                    <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-3xl bg-gradient-to-br from-blue-500/25 to-yellow-500/25 flex items-center justify-center ring-1 ring-blue-500/40 group-hover:ring-yellow-400/60 transition-all duration-500 group-hover:scale-125 group-hover:rotate-3">
                                        <img class="h-12 w-12 lg:h-14 lg:w-14 rounded-2xl object-cover"
                                             src="{{ url('storage/'.$serverCategory->image)}}" 
                                             alt="{{ $serverCategory->name}}"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="hidden h-12 w-12 lg:h-14 lg:w-14 rounded-2xl bg-gradient-to-br from-blue-500 to-yellow-500 items-center justify-center text-white font-bold text-xl">
                                            {{ substr($serverCategory->name, 0, 2) }}
                                        </div>
                                    </div>
                                    <!-- Enhanced status indicator -->
                                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center ring-4 ring-gray-800 group-hover:scale-125 group-hover:ring-green-300 transition-all duration-500">
                                        <div class="w-2.5 h-2.5 bg-white rounded-full animate-pulse"></div>
                                    </div>
                                </div>
                                
                                <!-- Enhanced Arrow -->
                                <div class="text-blue-400 group-hover:text-yellow-400 transition-all duration-500 transform group-hover:scale-125">
                                    <svg class="h-7 w-7 lg:h-8 lg:w-8 transform group-hover:translate-x-2 group-hover:-translate-y-2 transition-transform duration-700" 
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Category Info with enhanced typography -->
                            <div class="space-y-4">
                                <h3 class="text-2xl lg:text-3xl font-bold text-white group-hover:text-blue-400 transition-colors duration-500 line-clamp-2 tracking-tight">
                                    {{ $serverCategory->name}}
                                </h3>
                                <p class="text-base lg:text-lg text-gray-300 group-hover:text-white transition-colors duration-500 leading-relaxed line-clamp-3 tracking-wide">
                                    High-performance proxy solutions tailored for {{ strtolower($serverCategory->name) }} use cases with enterprise-grade reliability.
                                </p>
                            </div>

                            <!-- Stats and CTA with better spacing -->
                            <div class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-sm lg:text-base text-gray-400 font-medium">
                                        <svg class="mr-3 h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                                        </svg>
                                        {{ $serverCategory->plans_count ?? $serverCategory->server_plans_count ?? '10+' }} plans
                                    </div>
                                    <div class="inline-flex items-center rounded-full bg-blue-100/15 px-4 py-2 text-xs font-semibold text-blue-300 ring-1 ring-blue-400/30 tracking-wide">
                                        Available
                                    </div>
                                </div>
                                
                                <div class="flex items-center text--400 group-hover:text-yellow-400 font-semibold transition-colors duration-500">
                                    <span class="text-base lg:text-lg tracking-wide">Explore Solutions</span>
                                    <svg class="ml-3 h-5 w-5 transition-transform group-hover:translate-x-2 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>

            <!-- Bottom CTA Section with enhanced design -->
            <div class="text-center">
                <div class="bg-gradient-to-r from-blue-900/40 to-yellow-900/40 backdrop-blur-lg rounded-3xl p-12 lg:p-16 ring-1 ring-white/20 shadow-2xl hover:shadow-3xl transition-all duration-700 hover:scale-105 group">
                    <div class="space-y-8">
                        <h3 class="text-3xl lg:text-4xl font-bold text-white tracking-tight group-hover:text-blue-400 transition-colors duration-500">
                            Can't Find What You're Looking For?
                        </h3>
                        <p class="text-xl lg:text-2xl text-gray-300 max-w-3xl mx-auto leading-relaxed font-light tracking-wide group-hover:text-white transition-colors duration-500">
                            Our team can help you find the perfect proxy solution for your specific needs. 
                            Get personalized recommendations from our experts.
                        </p>
                        <a href="/contact" wire:navigate
                           class="inline-flex items-center rounded-3xl bg-gradient-to-r from-blue-600 to-yellow-600 px-12 py-6 text-xl font-semibold text-white shadow-2xl hover:shadow-blue-500/30 transition-all duration-500 hover:scale-110 transform focus:outline-none focus:ring-4 focus:ring-blue-500/50 group">
                            <svg class="mr-4 h-6 w-6 transition-transform group-hover:rotate-12 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span class="tracking-wide">Get Custom Solution</span>
                            <svg class="ml-4 h-6 w-6 transition-transform group-hover:translate-x-2 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- Enhanced Categories Section End --}}

    {{-- Enhanced Brands Section Start --}}
    <section id="brands" class="relative bg-gradient-to-br from-gray-50 via-white to-blue-50/30 dark:from-gray-900 dark:via-blue-900/10 dark:to-gray-900 py-28 sm:py-36 lg:py-40 overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10 dark:opacity-5">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>
        
        <!-- Background Effects -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-yellow-500/5"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-400/10 to-transparent rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-yellow-400/10 to-transparent rounded-full blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-12 xl:px-16">
            <!-- Section Header -->
            <div class="text-center mb-24 lg:mb-28">
                <div class="inline-flex items-center rounded-full bg-gradient-to-r from-yellow-100/10 to-blue-100/10 px-6 py-2 text-sm font-medium text-blue-400 ring-1 ring-blue-400/20 backdrop-blur-sm mb-8">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    Trusted Partners
                </div>
                
                <h2 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl lg:text-6xl">
                    Premium
                    <span class="bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent">Proxy Brands</span>
                </h2>
                
                <p class="mt-8 text-xl leading-8 text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    Partner with industry-leading proxy providers. Each brand is carefully vetted for reliability, 
                    performance, and enterprise-grade security standards.
                </p>
            </div>

            <!-- Brands Grid - Responsive: Column on mobile, Row on desktop -->
            @if($this->brands->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 lg:gap-10 xl:gap-12">
                    @foreach($this->brands->take(8) as $index => $brand)
                        <div class="group relative" wire:key="{{ $brand->id }}">
                            <a href="/servers?brands[]={{ $brand->id }}" wire:navigate 
                               class="block relative bg-gradient-to-br from-white/10 to-gray-100/30 dark:from-white/5 dark:to-gray-800/30 backdrop-blur-sm rounded-3xl p-6 ring-1 ring-white/20 dark:ring-white/10 hover:ring-blue-400/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/20 transform hover:scale-105 h-full">
                                
                                <!-- Gradient Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-yellow-500/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                                
                                <!-- Content -->
                                <div class="relative">
                                    <!-- Icon and Badge -->
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="relative">
                                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500/20 to-yellow-500/20 flex items-center justify-center ring-1 ring-blue-500/30 group-hover:ring-yellow-400/50 transition-all duration-300 group-hover:scale-110">
                                                <img class="h-10 w-10 rounded-xl object-cover"
                                                     src="{{ url('storage/'.$brand->image)}}" 
                                                     alt="{{ $brand->name}}"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="hidden h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-yellow-500 items-center justify-center text-white font-bold text-lg">
                                                    {{ substr($brand->name, 0, 2) }}
                                                </div>
                                            </div>
                                            <!-- Status indicator -->
                                            <div class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center ring-2 ring-white dark:ring-gray-800 group-hover:scale-110 transition-transform duration-300">
                                                <div class="w-2 h-2 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Arrow -->
                                        <div class="text-blue-400 group-hover:text-yellow-400 transition-colors duration-300">
                                            <svg class="h-6 w-6 transform group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform duration-300" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Brand Info -->
                                    <div class="mb-6">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white group-hover:text-blue-400 transition-colors duration-300 mb-2 line-clamp-2">
                                            {{ $brand->name}}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors duration-300 leading-relaxed line-clamp-3">
                                            Premium proxy solutions with enterprise-grade reliability and high-performance infrastructure for {{ strtolower($brand->name) }}.
                                        </p>
                                    </div>

                                    <!-- Stats and CTA -->
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                <svg class="mr-2 h-4 w-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                                                </svg>
                                                {{ $brand->servers_count ?? rand(10, 100) }} servers
                                            </div>
                                            <div class="inline-flex items-center rounded-full bg-yellow-100/20 dark:bg-yellow-100/10 px-3 py-1 text-xs font-medium text-yellow-700 dark:text-yellow-700 ring-1 ring-yellow-400/20">
                                                @switch($index % 4)
                                                    @case(0) Premium Partner @break
                                                    @case(1) Enterprise Ready @break
                                                    @case(2) High Performance @break
                                                    @default Verified Provider
                                                @endswitch
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center text-gray-700 dark:text-gray-200 group-hover:text-yellow-600 dark:group-hover:text-yellow-400 font-medium transition-colors duration-300">
                                            <span class="text-sm">Explore Plans</span>
                                            <svg class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                <!-- View All Button -->
                @if($this->brands->count() > 8)
                    <div class="mt-16 text-center">
                        <a href="/servers#brands" wire:navigate
                           class="group inline-flex items-center rounded-2xl bg-gradient-to-r from-blue-600 to-yellow-600 px-8 py-4 text-lg font-semibold text-white shadow-lg hover:shadow-xl hover:shadow-blue-500/25 transition-all duration-300 hover:scale-105">
                            View All {{ $this->brands->count() }} Premium Brands
                            <svg class="ml-3 h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                @endif

                <!-- Bottom CTA Section -->
                <div class="mt-20 text-center">
                    <div class="bg-gradient-to-r from-blue-900/30 to-yellow-900/30 backdrop-blur-sm rounded-3xl p-8 ring-1 ring-white/10">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Ready to Partner with Premium Brands?
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6 max-w-2xl mx-auto">
                            Join thousands of businesses that trust our verified proxy partners for their mission-critical operations. 
                            Get started with enterprise-grade solutions today.
                        </p>
                        <a href="/servers" wire:navigate
                           class="inline-flex items-center rounded-2xl bg-gradient-to-r from-blue-600 to-yellow-600 px-8 py-4 text-lg font-semibold text-white shadow-lg hover:shadow-xl hover:shadow-blue-500/25 transition-all duration-300 hover:scale-105">
                            Browse All Solutions
                            <svg class="ml-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="mx-auto h-24 w-24 rounded-full bg-gradient-to-br from-blue-100 to-yellow-100 dark:from-blue-900/20 dark:to-yellow-900/20 flex items-center justify-center mb-6">
                        <svg class="h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No brands available</h3>
                    <p class="text-gray-500 dark:text-gray-400">New premium proxy brands will be added soon.</p>
                </div>
            @endif

            <!-- Trust Indicators -->
            <div class="mt-20 border-t border-gray-200/30 dark:border-gray-700/30 pt-16">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
                    <div class="group">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500/20 to-blue-600/20 text-blue-500 shadow-lg group-hover:shadow-blue-500/25 transition-all duration-300 group-hover:scale-110 mb-4">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-blue-400 transition-colors">Verified Quality</h3>
                        <p class="text-gray-600 dark:text-gray-300">All brands undergo rigorous testing</p>
                    </div>
                    
                    <div class="group">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 text-yellow-500 shadow-lg group-hover:shadow-yellow-500/25 transition-all duration-300 group-hover:scale-110 mb-4">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-yellow-400 transition-colors">Enterprise Security</h3>
                        <p class="text-gray-600 dark:text-gray-300">Bank-level encryption & protection</p>
                    </div>
                    
                    <div class="group">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600/20 to-blue-700/20 text-blue-600 shadow-lg group-hover:shadow-blue-500/25 transition-all duration-300 group-hover:scale-110 mb-4">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-blue-400 transition-colors">Lightning Fast</h3>
                        <p class="text-gray-600 dark:text-gray-300">Optimized for maximum performance</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- Enhanced Brands Section End --}}

    {{-- Enhanced Testimonials Section with Advanced Tailwind Features --}}
    <section id="demo-section" class="relative bg-gray-900 py-36 lg:py-44 min-h-screen flex items-center overflow-hidden">
        <!-- Enhanced Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-blue-900/20 to-purple-900/10"></div>
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 50px 50px;"></div>
        </div>
        
        <!-- Enhanced Floating Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-72 h-72 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 rounded-full blur-3xl animate-float transform-gpu will-change-transform"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-gradient-to-br from-purple-500/10 to-pink-500/10 rounded-full blur-3xl animate-float-reverse transform-gpu will-change-transform"></div>
            <div class="absolute top-1/2 left-1/4 w-32 h-32 bg-gradient-to-br from-yellow-400/20 to-orange-400/20 rounded-full blur-2xl animate-pulse-glow"></div>
            <div class="absolute bottom-1/3 right-1/3 w-40 h-40 bg-gradient-to-br from-emerald-400/20 to-teal-400/20 rounded-full blur-2xl animate-pulse-glow animation-delay-400"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-8 lg:px-12 xl:px-16">
            <!-- Enhanced Section Header -->
            <div class="text-center mb-28 lg:mb-32 space-y-8 max-w-4xl mx-auto">
                <div class="inline-flex items-center rounded-full bg-gradient-to-r from-yellow-100/10 to-blue-100/10 px-6 py-3 text-sm font-medium text-yellow-400 ring-1 ring-yellow-400/20 backdrop-blur-sm mb-10 animate-scale-in shadow-2xl">
                    <svg class="mr-3 h-5 w-5 animate-pulse-glow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    Customer Success Stories
                </div>
                
                <h2 class="text-5xl lg:text-6xl font-black tracking-tight text-white leading-none animate-fade-in-up">
                    Trusted by
                    <span class="bg-gradient-to-r from-yellow-400 via-orange-400 to-blue-400 bg-clip-text text-transparent text-shadow-lg">Thousands</span>
                </h2>
                <p class="text-xl lg:text-2xl leading-relaxed text-gray-300 max-w-3xl mx-auto font-light tracking-wide animate-fade-in-up animation-delay-200 mt-10">
                    Hear from our satisfied customers who have transformed their businesses with our proxy solutions. 
                    From startups to enterprises, we deliver results that matter.
                </p>
                
                <!-- Enhanced Trust Metrics -->
                <div class="flex flex-wrap items-center justify-center gap-8 mt-16 animate-fade-in-up animation-delay-400">
                    <div class="flex items-center gap-2 text-sm text-gray-400 bg-white/5 backdrop-blur-sm px-5 py-3 rounded-full ring-1 ring-white/10 shadow-lg">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse-glow"></div>
                        <span class="font-medium">5-Star Rated</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-400 bg-white/5 backdrop-blur-sm px-5 py-3 rounded-full ring-1 ring-white/10 shadow-lg">
                        <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse-glow animation-delay-200"></div>
                        <span class="font-medium">10K+ Reviews</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-400 bg-white/5 backdrop-blur-sm px-5 py-3 rounded-full ring-1 ring-white/10 shadow-lg">
                        <div class="w-2 h-2 bg-purple-400 rounded-full animate-pulse-glow animation-delay-400"></div>
                        <span class="font-medium">Enterprise Trusted</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced Testimonials Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12 xl:gap-16 mb-28 lg:mb-32">
                <!-- Enhanced Testimonial 1 -->
                <div class="group relative bg-white/5 backdrop-blur-xl rounded-3xl p-10 lg:p-12 xl:p-14 ring-1 ring-white/10 hover:ring-white/30 hover:ring-2 transition-all-700 hover:scale-105 hover:-translate-y-2 hover:rotate-1 transform-gpu will-change-transform animate-fade-in-left shadow-4xl">
                    <!-- Enhanced Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-transparent to-cyan-500/10 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                    
                    <!-- Enhanced Quote Mark -->
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-2xl group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 transform-gpu">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                    
                    <div class="relative z-10 pt-8">
                        <div class="mb-8">
                            <div class="flex text-yellow-400 mb-6 space-x-1">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="h-6 w-6 transform hover:scale-110 transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: {{ $i * 100 }}ms">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <blockquote class="text-lg lg:text-xl text-gray-300 leading-relaxed font-light tracking-wide line-height-loose">
                                "The proxy performance is outstanding. Our web scraping operations have become 
                                <span class="text-yellow-400 font-semibold">300% more efficient</span> with near-zero downtime."
                            </blockquote>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-xl shadow-2xl group-hover:rotate-3 group-hover:scale-110 transition-all duration-300 transform-gpu">
                                    JS
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 rounded-full ring-2 ring-gray-900 animate-pulse-glow"></div>
                            </div>
                            <div class="space-y-1">
                                <div class="font-bold text-white text-lg group-hover:text-blue-400 transition-colors duration-300">John Smith</div>
                                <div class="text-gray-400 text-sm font-medium">CTO, DataFlow Solutions</div>
                                <div class="text-xs text-gray-500">5+ years customer</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Testimonial 2 -->
                <div class="group relative bg-white/5 backdrop-blur-xl rounded-3xl p-8 lg:p-10 ring-1 ring-white/10 hover:ring-white/30 hover:ring-2 transition-all-700 hover:scale-105 hover:-translate-y-2 hover:rotate-1 transform-gpu will-change-transform animate-fade-in-up animation-delay-200 shadow-4xl">
                    <!-- Enhanced Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 via-transparent to-pink-500/10 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                    
                    <!-- Enhanced Quote Mark -->
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl flex items-center justify-center shadow-2xl group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 transform-gpu">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                    
                    <div class="relative z-10 pt-8">
                        <div class="mb-8">
                            <div class="flex text-yellow-400 mb-6 space-x-1">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="h-6 w-6 transform hover:scale-110 transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: {{ $i * 100 }}ms">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <blockquote class="text-lg lg:text-xl text-gray-300 leading-relaxed font-light tracking-wide line-height-loose">
                                "Exceptional reliability and customer support. The proxy network handles our 
                                <span class="text-purple-400 font-semibold">enterprise-level traffic</span> without any issues."
                            </blockquote>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold text-xl shadow-2xl group-hover:rotate-3 group-hover:scale-110 transition-all duration-300 transform-gpu">
                                    MR
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 rounded-full ring-2 ring-gray-900 animate-pulse-glow animation-delay-200"></div>
                            </div>
                            <div class="space-y-1">
                                <div class="font-bold text-white text-lg group-hover:text-purple-400 transition-colors duration-300">Maria Rodriguez</div>
                                <div class="text-gray-400 text-sm font-medium">Head of IT, TechCorp Inc.</div>
                                <div class="text-xs text-gray-500">Enterprise client</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Testimonial 3 -->
                <div class="group relative bg-white/5 backdrop-blur-xl rounded-3xl p-8 lg:p-10 ring-1 ring-white/10 hover:ring-white/30 hover:ring-2 transition-all-700 hover:scale-105 hover:-translate-y-2 hover:rotate-1 transform-gpu will-change-transform animate-fade-in-right animation-delay-400 shadow-4xl">
                    <!-- Enhanced Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-transparent to-teal-500/10 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                    
                    <!-- Enhanced Quote Mark -->
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center shadow-2xl group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 transform-gpu">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                    
                    <div class="relative z-10 pt-8">
                        <div class="mb-8">
                            <div class="flex text-yellow-400 mb-6 space-x-1">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="h-6 w-6 transform hover:scale-110 transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: {{ $i * 100 }}ms">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <blockquote class="text-lg lg:text-xl text-gray-300 leading-relaxed font-light tracking-wide line-height-loose">
                                "Game-changing proxy service! Our market research capabilities have expanded 
                                <span class="text-emerald-400 font-semibold">globally</span> with seamless geographical targeting."
                            </blockquote>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-xl shadow-2xl group-hover:rotate-3 group-hover:scale-110 transition-all duration-300 transform-gpu">
                                    AL
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 rounded-full ring-2 ring-gray-900 animate-pulse-glow animation-delay-400"></div>
                            </div>
                            <div class="space-y-1">
                                <div class="font-bold text-white text-lg group-hover:text-emerald-400 transition-colors duration-300">Alex Liu</div>
                                <div class="text-gray-400 text-sm font-medium">Research Director, Analytics Pro</div>
                                <div class="text-xs text-gray-500">Global operations</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Stats Section -->
            <div class="border-t border-white/10 pt-20">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 text-center">
                    <div class="group space-y-3 animate-fade-in-up">
                        <div class="text-5xl lg:text-6xl font-black text-white mb-4 group-hover:text-yellow-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                            99.9%
                        </div>
                        <div class="text-gray-400 font-semibold text-lg tracking-wide">Uptime Guarantee</div>
                        <div class="text-xs text-gray-500">Industry leading</div>
                    </div>
                    <div class="group space-y-3 animate-fade-in-up animation-delay-200">
                        <div class="text-5xl lg:text-6xl font-black text-white mb-4 group-hover:text-blue-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                            10K+
                        </div>
                        <div class="text-gray-400 font-semibold text-lg tracking-wide">Happy Customers</div>
                        <div class="text-xs text-gray-500">Worldwide</div>
                    </div>
                    <div class="group space-y-3 animate-fade-in-up animation-delay-400">
                        <div class="text-5xl lg:text-6xl font-black text-white mb-4 group-hover:text-purple-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                            24/7
                        </div>
                        <div class="text-gray-400 font-semibold text-lg tracking-wide">Expert Support</div>
                        <div class="text-xs text-gray-500">Always available</div>
                    </div>
                    <div class="group space-y-3 animate-fade-in-up animation-delay-600">
                        <div class="text-5xl lg:text-6xl font-black text-white mb-4 group-hover:text-emerald-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                            150+
                        </div>
                        <div class="text-gray-400 font-semibold text-lg tracking-wide">Countries Covered</div>
                        <div class="text-xs text-gray-500">Global network</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- Enhanced Testimonials Section End --}}

    {{-- Enhanced Final CTA Section with Advanced Tailwind Features --}}
    <section class="relative bg-gradient-to-br from-gray-900 via-blue-900/30 to-purple-900/20 py-36 lg:py-44 min-h-screen flex items-center overflow-hidden">
        <!-- Enhanced Background Effects -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 via-purple-600/10 to-yellow-500/10"></div>
            <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-transparent via-blue-900/10 to-transparent"></div>
        </div>
        
        <!-- Enhanced Floating Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 right-20 w-96 h-96 bg-gradient-to-br from-blue-400/15 to-cyan-400/10 rounded-full blur-3xl animate-float transform-gpu will-change-transform"></div>
            <div class="absolute bottom-20 left-20 w-96 h-96 bg-gradient-to-tr from-yellow-400/15 to-orange-400/10 rounded-full blur-3xl animate-float-reverse transform-gpu will-change-transform"></div>
            <div class="absolute top-1/3 left-1/3 w-64 h-64 bg-gradient-to-br from-purple-400/20 to-pink-400/15 rounded-full blur-2xl animate-pulse-glow"></div>
            <div class="absolute bottom-1/3 right-1/3 w-80 h-80 bg-gradient-to-br from-emerald-400/15 to-teal-400/10 rounded-full blur-3xl animate-pulse-glow animation-delay-400"></div>
        </div>

        <!-- Enhanced Grid Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239C92AC" fill-opacity="0.08"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>

        <div class="relative mx-auto max-w-7xl px-8 lg:px-12 xl:px-16 z-10">
            <div class="text-center space-y-16 lg:space-y-20">
                <!-- Enhanced Badge -->
                <div class="mb-16 flex justify-center animate-scale-in">
                    <div class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-100/10 to-yellow-100/10 px-8 py-4 text-sm font-bold text-blue-400 ring-2 ring-blue-400/20 backdrop-blur-lg shadow-2xl hover:ring-blue-400/40 transition-all duration-500 hover:scale-105">
                        <svg class="mr-3 h-5 w-5 animate-pulse-glow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Start Your Journey Today
                        <div class="ml-3 w-2 h-2 bg-green-400 rounded-full animate-pulse-glow"></div>
                    </div>
                </div>

                <!-- Enhanced Main Headline -->
                <div class="space-y-12 animate-fade-in-up">
                    <h2 class="text-6xl lg:text-7xl xl:text-8xl font-black tracking-tight text-white leading-none text-shadow-lg">
                        Ready to Scale Your
                        <br class="hidden sm:block">
                        <span class="bg-gradient-to-r from-blue-400 via-purple-500 to-yellow-400 bg-clip-text text-transparent animate-pulse-glow">Business?</span>
                    </h2>
                    
                    <p class="text-xl lg:text-2xl xl:text-3xl leading-relaxed text-gray-300 max-w-4xl mx-auto font-light tracking-wide mt-12">
                        Join thousands of companies that trust our proxy solutions for their critical operations. 
                        Start your journey today with our <span class="text-yellow-400 font-semibold">premium network</span>.
                    </p>
                </div>
                
                <!-- Enhanced CTA Buttons -->
                <div class="flex flex-col lg:flex-row gap-8 justify-center items-center pt-12 animate-fade-in-up animation-delay-400">
                    <a href="/servers" wire:navigate
                       class="group relative w-full lg:w-auto inline-flex items-center justify-center px-16 py-6 text-xl lg:text-2xl font-black text-white bg-gradient-to-r from-blue-600 via-blue-700 to-yellow-600 rounded-3xl shadow-4xl hover:shadow-blue-500/40 transition-all-700 hover:scale-110 hover:-translate-y-2 transform-gpu will-change-transform overflow-hidden">
                        <!-- Enhanced Shimmer Effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent transform -skew-x-12 scale-x-0 group-hover:scale-x-100 transition-transform duration-1000 origin-left"></div>
                        
                        <span class="relative z-10 flex items-center">
                            <svg class="mr-4 h-7 w-7 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Start Free Trial
                        </span>
                        <svg class="ml-4 h-7 w-7 transition-transform group-hover:translate-x-3 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        
                        <!-- Enhanced Glow Effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-blue-700 to-yellow-600 rounded-3xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity duration-500 transform scale-110"></div>
                    </a>
                    
                    <a href="/contact" wire:navigate
                       class="group w-full lg:w-auto inline-flex items-center justify-center px-16 py-6 text-xl lg:text-2xl font-bold text-white border-2 border-white/30 rounded-3xl backdrop-blur-lg hover:border-white/60 hover:bg-white/10 transition-all-700 hover:scale-105 hover:-translate-y-1 transform-gpu will-change-transform">
                        <svg class="mr-4 h-7 w-7 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Talk to Expert
                        <svg class="ml-4 h-6 w-6 transition-transform group-hover:translate-x-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>

                <!-- Enhanced Trust Badges -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12 xl:gap-16 max-w-5xl mx-auto pt-20 animate-fade-in-up animation-delay-600">
                    <div class="group relative bg-white/5 backdrop-blur-xl rounded-3xl p-10 lg:p-12 xl:p-14 ring-1 ring-white/10 hover:ring-white/30 hover:ring-2 transition-all-700 hover:scale-105 hover:-translate-y-2 hover:rotate-1 transform-gpu will-change-transform">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-transparent to-cyan-500/10 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 mx-auto mb-6 group-hover:scale-110 group-hover:rotate-12 transition-all duration-500 shadow-2xl">
                                <svg class="h-8 w-8 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h3 class="text-xl lg:text-2xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors duration-300">Enterprise Security</h3>
                            <p class="text-gray-300 text-base lg:text-lg font-light leading-relaxed">Bank-level encryption & protection for all your operations</p>
                        </div>
                    </div>
                    
                    <div class="group relative bg-white/5 backdrop-blur-xl rounded-3xl p-8 lg:p-10 ring-1 ring-white/10 hover:ring-white/30 hover:ring-2 transition-all-700 hover:scale-105 hover:-translate-y-2 hover:rotate-1 transform-gpu will-change-transform">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 via-transparent to-emerald-500/10 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-green-500/20 to-emerald-500/20 mx-auto mb-6 group-hover:scale-110 group-hover:rotate-12 transition-all duration-500 shadow-2xl">
                                <svg class="h-8 w-8 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h3 class="text-xl lg:text-2xl font-bold text-white mb-4 group-hover:text-green-400 transition-colors duration-300">No Setup Fees</h3>
                            <p class="text-gray-300 text-base lg:text-lg font-light leading-relaxed">Start immediately with zero upfront costs</p>
                        </div>
                    </div>
                    
                    <div class="group relative bg-white/5 backdrop-blur-xl rounded-3xl p-8 lg:p-10 ring-1 ring-white/10 hover:ring-white/30 hover:ring-2 transition-all-700 hover:scale-105 hover:-translate-y-2 hover:rotate-1 transform-gpu will-change-transform">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 via-transparent to-pink-500/10 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500/20 to-pink-500/20 mx-auto mb-6 group-hover:scale-110 group-hover:rotate-12 transition-all duration-500 shadow-2xl">
                                <svg class="h-8 w-8 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl lg:text-2xl font-bold text-white mb-4 group-hover:text-purple-400 transition-colors duration-300">24/7 Support</h3>
                            <p class="text-gray-300 text-base lg:text-lg font-light leading-relaxed">Expert assistance whenever you need it</p>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Bottom Stats -->
                <div class="pt-24 lg:pt-28 border-t border-white/10 animate-fade-in-up animation-delay-800">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-16 text-center">
                        <div class="group space-y-4">
                            <div class="text-4xl lg:text-5xl xl:text-6xl font-black text-white group-hover:text-blue-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                                99.9%
                            </div>
                            <div class="text-gray-400 font-bold text-base lg:text-lg tracking-wide">Uptime Guarantee</div>
                            <div class="text-xs text-gray-500">Industry leading</div>
                        </div>
                        <div class="group space-y-4">
                            <div class="text-4xl lg:text-5xl xl:text-6xl font-black text-white group-hover:text-yellow-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                                10K+
                            </div>
                            <div class="text-gray-400 font-bold text-base lg:text-lg tracking-wide">Happy Customers</div>
                            <div class="text-xs text-gray-500">Worldwide</div>
                        </div>
                        <div class="group space-y-4">
                            <div class="text-4xl lg:text-5xl xl:text-6xl font-black text-white group-hover:text-green-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                                150+
                            </div>
                            <div class="text-gray-400 font-bold text-base lg:text-lg tracking-wide">Countries Covered</div>
                            <div class="text-xs text-gray-500">Global network</div>
                        </div>
                        <div class="group space-y-4">
                            <div class="text-4xl lg:text-5xl xl:text-6xl font-black text-white group-hover:text-purple-400 transition-all duration-500 group-hover:scale-110 transform-gpu will-change-transform">
                                24/7
                            </div>
                            <div class="text-gray-400 font-bold text-base lg:text-lg tracking-wide">Expert Support</div>
                            <div class="text-xs text-gray-500">Always available</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- Enhanced Final CTA Section End --}}

    {{-- Enhanced Interactions Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add scroll-triggered animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in-up');
                    }
                });
            }, observerOptions);

            // Observe sections for animation
            document.querySelectorAll('section').forEach(section => {
                observer.observe(section);
            });

            // Add hover effects for category cards
            document.querySelectorAll('.group').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Enhanced search functionality
            const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="searchTerm"]');
            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-500');
                });
                
                searchInput.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-500');
                });
            }
        });
    </script>

    {{-- Custom CSS for enhanced animations and utilities --}}
    <style>
        /* Enhanced fade-in animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-fade-in-left {
            animation: fadeInLeft 0.8s ease-out forwards;
        }

        .animate-fade-in-right {
            animation: fadeInRight 0.8s ease-out forwards;
        }

        .animate-scale-in {
            animation: scaleIn 0.6s ease-out forwards;
        }

        .animation-delay-200 {
            animation-delay: 200ms;
        }

        .animation-delay-400 {
            animation-delay: 400ms;
        }

        .animation-delay-600 {
            animation-delay: 600ms;
        }

        /* Enhanced hover effects with transform-gpu for better performance */
        .group {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }

        .group:hover {
            transform: translateY(-12px) scale(1.02);
        }

        /* Enhanced button hover effects */
        .group:hover .group-hover\\:translate-x-1 {
            transform: translateX(6px);
        }

        .group:hover .group-hover\\:translate-x-2 {
            transform: translateX(12px);
        }

        .group:hover .group-hover\\:scale-110 {
            transform: scale(1.1);
        }

        .group:hover .group-hover\\:scale-125 {
            transform: scale(1.25);
        }

        .group:hover .group-hover\\:rotate-3 {
            transform: rotate(3deg);
        }

        .group:hover .group-hover\\:rotate-12 {
            transform: rotate(12deg);
        }

        /* Custom scrollbar styles */
        .scrollbar-thin {
            scrollbar-width: thin;
        }

        .scrollbar-thumb-blue-500 {
            scrollbar-color: #3b82f6 transparent;
        }

        .scrollbar-track-gray-200 {
            scrollbar-color: #e5e7eb transparent;
        }

        .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }

        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 6px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #3b82f6, #eab308);
            border-radius: 6px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #2563eb, #d97706);
        }

        /* Enhanced line clamp utilities */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-4 {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Enhanced responsive grid improvements */
        @media (max-width: 640px) {
            .grid-cols-1 {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (min-width: 641px) and (max-width: 1023px) {
            .sm\\:grid-cols-2 {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }
        }

        @media (min-width: 1024px) {
            .lg\\:grid-cols-3 {
                grid-template-columns: repeat(3, 1fr);
                gap: 2.5rem;
            }
            .lg\\:grid-cols-4 {
                grid-template-columns: repeat(4, 1fr);
                gap: 2.5rem;
            }
        }

        @media (min-width: 1280px) {
            .xl\\:grid-cols-4 {
                grid-template-columns: repeat(4, 1fr);
                gap: 3rem;
            }
        }

        /* Enhanced backdrop blur effects */
        .backdrop-blur-sm {
            backdrop-filter: blur(4px);
        }

        .backdrop-blur-lg {
            backdrop-filter: blur(16px);
        }

        .backdrop-blur-xl {
            backdrop-filter: blur(24px);
        }

        /* Floating animation for hero shapes */
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg); 
            }
            25% { 
                transform: translateY(-10px) rotate(1deg); 
            }
            50% { 
                transform: translateY(-20px) rotate(2deg); 
            }
            75% { 
                transform: translateY(-10px) rotate(1deg); 
            }
        }

        @keyframes floatReverse {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg); 
            }
            25% { 
                transform: translateY(10px) rotate(-1deg); 
            }
            50% { 
                transform: translateY(20px) rotate(-2deg); 
            }
            75% { 
                transform: translateY(10px) rotate(-1deg); 
            }
        }

        .animate-float {
            animation: float 8s ease-in-out infinite;
        }

        .animate-float-reverse {
            animation: floatReverse 10s ease-in-out infinite;
        }

        /* Pulse glow animation for status indicators */
        @keyframes pulseGlow {
            0%, 100% { 
                opacity: 1;
                box-shadow: 0 0 8px currentColor;
            }
            50% { 
                opacity: 0.8;
                box-shadow: 0 0 25px currentColor;
            }
        }

        .animate-pulse-glow {
            animation: pulseGlow 3s ease-in-out infinite;
        }

        /* Enhanced shadow utilities */
        .shadow-3xl {
            box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
        }

        .shadow-4xl {
            box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.25);
        }

        /* Focus ring improvements */
        .focus\\:ring-4:focus {
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) var(--tw-ring-color);
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
        }

        /* Enhanced text effects */
        .text-shadow {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .text-shadow-lg {
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        /* Gradient text utilities */
        .text-gradient {
            background: linear-gradient(45deg, #3b82f6, #eab308);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Better responsive spacing */
        @media (max-width: 768px) {
            .responsive-padding {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .responsive-spacing > * + * {
                margin-top: 1.5rem;
            }
        }

        @media (min-width: 769px) {
            .responsive-padding {
                padding-left: 2rem;
                padding-right: 2rem;
            }
            
            .responsive-spacing > * + * {
                margin-top: 2rem;
            }
        }

        /* Enhanced gradient effects */
        .gradient-border {
            background: linear-gradient(45deg, #3b82f6, #eab308);
            border-radius: 2rem;
            padding: 2px;
        }

        .gradient-border-inner {
            background: white;
            border-radius: calc(2rem - 2px);
        }

        /* Dark mode specific enhancements */
        @media (prefers-color-scheme: dark) {
            .gradient-border-inner {
                background: #1f2937;
            }
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Enhanced transition utilities */
        .transition-all-500 {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .transition-all-700 {
            transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Custom aspect ratios */
        .aspect-w-16 {
            position: relative;
            padding-bottom: calc(9 / 16 * 100%);
        }

        .aspect-w-16 > * {
            position: absolute;
            height: 100%;
            width: 100%;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }

        /* Enhanced container queries for better responsive design */
        @container (min-width: 640px) {
            .card-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @container (min-width: 1024px) {
            .card-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @container (min-width: 1280px) {
            .card-container {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Performance optimizations */
        .will-change-transform {
            will-change: transform;
        }

        .will-change-opacity {
            will-change: opacity;
        }

        .transform-gpu {
            transform: translateZ(0);
        }

        /* Enhanced typography utilities */
        .tracking-widest {
            letter-spacing: 0.1em;
        }

        .tracking-super-wide {
            letter-spacing: 0.15em;
        }

        /* Custom ring utilities */
        .ring-3 {
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color);
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
        }

        .ring-4 {
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) var(--tw-ring-color);
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
        }

        /* Enhanced filter utilities */
        .filter-brightness-110 {
            filter: brightness(1.1);
        }

        .filter-brightness-125 {
            filter: brightness(1.25);
        }

        .filter-contrast-110 {
            filter: contrast(1.1);
        }

        .filter-saturate-110 {
            filter: saturate(1.1);
        }

        /* Custom animation easing */
        .ease-in-out-back {
            transition-timing-function: cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .ease-out-expo {
            transition-timing-function: cubic-bezier(0.19, 1, 0.22, 1);
        }

        .ease-in-out-quart {
            transition-timing-function: cubic-bezier(0.77, 0, 0.175, 1);
        }
    </style>
</div>
