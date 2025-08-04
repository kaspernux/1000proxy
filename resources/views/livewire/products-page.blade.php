@php
use Illuminate\Support\Str;
@endphp

<div class="min-h-screen bg-gradient-to-br from-green-900 via-green-800 to-green-600 font-sans"
     x-data="{ 
        showFilters: false,
        activeFilter: null,
        isLoading: false
     }">

    <!-- Enhanced Loading Overlay -->
    <div wire:loading.delay class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-gradient-to-r from-green-900 to-green-700 border-2 border-yellow-400 rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-yellow-400 border-t-transparent"></div>
                    <div class="absolute inset-0 rounded-full h-12 w-12 border-4 border-green-300 border-t-transparent animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
                </div>
                <div class="text-white">
                    <div class="text-xl font-bold">Loading Products</div>
                    <div class="text-sm text-green-200 animate-pulse">Finding the best proxies for you...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Hero Section -->
    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto mb-12">
            <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-8 md:p-12 border border-white/10 shadow-2xl overflow-hidden">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-5">
                    <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                        <defs>
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#grid)" />
                    </svg>
                </div>
                
                <!-- Floating Gradient Orbs -->
                <div class="absolute top-0 left-0 w-32 h-32 bg-gradient-to-br from-yellow-400/30 to-orange-500/30 rounded-full blur-2xl animate-float"></div>
                <div class="absolute bottom-0 right-0 w-40 h-40 bg-gradient-to-br from-green-400/30 to-blue-500/30 rounded-full blur-2xl animate-float-reverse"></div>
                
                <div class="relative z-10 text-center">
                    <!-- Breadcrumb -->
                    <div class="mb-6">
                        <nav class="flex justify-center items-center space-x-2 text-sm">
                            <a href="/" class="text-green-300 hover:text-yellow-400 transition-colors">Home</a>
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <span class="text-yellow-400 font-medium">Premium Proxies</span>
                        </nav>
                    </div>

                    <!-- Main Title -->
                    <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                        <span class="bg-gradient-to-r from-yellow-400 via-yellow-300 to-yellow-200 bg-clip-text text-transparent">
                            Premium Proxies
                        </span>
                    </h1>
                    
                    <!-- Description -->
                    <p class="text-lg md:text-xl text-green-100 max-w-2xl mx-auto leading-relaxed mb-8">
                        Discover high-performance proxy solutions tailored to your specific needs and requirements.
                    </p>

                    <!-- Live Stats Row -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                        <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                            <div class="text-2xl font-bold text-yellow-400">{{ $serverPlans->total() }}</div>
                            <div class="text-sm text-green-200">Available Plans</div>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                            <div class="text-2xl font-bold text-yellow-400">{{ $countries ? count($countries) : 0 }}</div>
                            <div class="text-sm text-green-200">Locations</div>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                            <div class="text-2xl font-bold text-yellow-400">{{ $categories ? $categories->count() : 0 }}</div>
                            <div class="text-sm text-green-200">Categories</div>
                        </div>
                        <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                            <div class="text-2xl font-bold text-yellow-400">99.9%</div>
                            <div class="text-sm text-green-200">Uptime</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Enhanced Filter Bar -->
        <div class="bg-white/5 backdrop-blur-lg rounded-3xl p-8 mb-8 shadow-2xl border border-white/10 relative overflow-hidden">
            <!-- Background decoration -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-yellow-400/10 to-green-400/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-green-400/10 to-blue-400/10 rounded-full blur-2xl"></div>
            
            <div class="relative z-10">
                <!-- Filter Header -->
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-white mb-2">Find Your Perfect Proxy</h2>
                    <p class="text-green-200">Use our advanced filters to discover the ideal proxy solution</p>
                </div>

                <div class="flex flex-col lg:flex-row gap-6 items-center">
                    <!-- Quick Filters Grid -->
                    <div class="flex-1 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 w-full">
                        <!-- Location Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-green-700 to-green-600 hover:from-green-600 hover:to-green-500 text-white rounded-xl transition-all duration-300 border border-green-500/50 shadow-lg hover:shadow-xl group">
                                <span class="flex items-center text-sm font-medium">
                                    <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-3a9 9 0 00-9-9"></path>
                                    </svg>
                                    Location
                                </span>
                                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 class="absolute top-full left-0 right-0 mt-2 bg-green-900/95 backdrop-blur-md rounded-xl shadow-2xl border border-green-600/50 z-30 max-h-48 overflow-y-auto">
                                @foreach($countries as $country)
                                    <label class="flex items-center px-4 py-3 hover:bg-green-700/50 cursor-pointer text-white transition-colors group">
                                        <input type="checkbox" wire:model.live="selected_countries" value="{{ $country }}"
                                               class="mr-3 rounded border-green-500 text-yellow-500 focus:ring-yellow-500 focus:ring-2">
                                        <span class="text-sm group-hover:text-yellow-200 transition-colors">{{ strtoupper($country) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Category Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-blue-700 to-blue-600 hover:from-blue-600 hover:to-blue-500 text-white rounded-xl transition-all duration-300 border border-blue-500/50 shadow-lg hover:shadow-xl group">
                                <span class="flex items-center text-sm font-medium">
                                    <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Category
                                </span>
                                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 class="absolute top-full left-0 right-0 mt-2 bg-blue-900/95 backdrop-blur-md rounded-xl shadow-2xl border border-blue-600/50 z-30">
                                @foreach($categories as $category)
                                    <label class="flex items-center px-4 py-3 hover:bg-blue-700/50 cursor-pointer text-white transition-colors group">
                                        <input type="checkbox" wire:model.live="selected_categories" value="{{ $category->id }}"
                                               class="mr-3 rounded border-blue-500 text-yellow-500 focus:ring-yellow-500 focus:ring-2">
                                        <span class="text-sm group-hover:text-yellow-200 transition-colors">{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Brand Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-700 to-purple-600 hover:from-purple-600 hover:to-purple-500 text-white rounded-xl transition-all duration-300 border border-purple-500/50 shadow-lg hover:shadow-xl group">
                                <span class="flex items-center text-sm font-medium">
                                    <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    Brand
                                </span>
                                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 class="absolute top-full left-0 right-0 mt-2 bg-purple-900/95 backdrop-blur-md rounded-xl shadow-2xl border border-purple-600/50 z-30">
                                @foreach($brands as $brand)
                                    <label class="flex items-center px-4 py-3 hover:bg-purple-700/50 cursor-pointer text-white transition-colors group">
                                        <input type="checkbox" wire:model.live="selected_brands" value="{{ $brand->id }}"
                                               class="mr-3 rounded border-purple-500 text-yellow-500 focus:ring-yellow-500 focus:ring-2">
                                        <span class="text-sm group-hover:text-yellow-200 transition-colors">{{ $brand->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-orange-700 to-orange-600 hover:from-orange-600 hover:to-orange-500 text-white rounded-xl transition-all duration-300 border border-orange-500/50 shadow-lg hover:shadow-xl group">
                                <span class="flex items-center text-sm font-medium">
                                    <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    Price
                                </span>
                                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 class="absolute top-full left-0 right-0 mt-2 bg-orange-900/95 backdrop-blur-md rounded-xl shadow-2xl border border-orange-600/50 z-30 p-4">
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-3">
                                        <label class="text-white text-sm font-medium min-w-0">Min:</label>
                                        <input type="number" wire:model.live="price_min" min="0" max="1000"
                                               class="flex-1 px-3 py-2 bg-orange-800/50 text-white rounded-lg border border-orange-600 focus:ring-2 focus:ring-yellow-500 text-sm backdrop-blur-sm">
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <label class="text-white text-sm font-medium min-w-0">Max:</label>
                                        <input type="number" wire:model.live="price_max" min="0" max="1000"
                                               class="flex-1 px-3 py-2 bg-orange-800/50 text-white rounded-lg border border-orange-600 focus:ring-2 focus:ring-yellow-500 text-sm backdrop-blur-sm">
                                    </div>
                                    <div class="text-center text-yellow-400 text-sm font-bold bg-orange-800/30 py-2 rounded-lg">
                                        ${{ $price_min }} - ${{ $price_max }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Protocol Select -->
                        <select wire:model.live="selected_protocols" 
                                class="px-4 py-3 bg-gradient-to-r from-teal-700 to-teal-600 hover:from-teal-600 hover:to-teal-500 text-white rounded-xl border border-teal-500/50 focus:ring-2 focus:ring-yellow-500 text-sm shadow-lg hover:shadow-xl transition-all duration-300">
                            <option value="">All Protocols</option>
                            @foreach($protocols as $protocol)
                                <option value="{{ $protocol }}">{{ $protocol }}</option>
                            @endforeach
                        </select>

                        <!-- Status Filter -->
                        <select wire:model.live="server_status" 
                                class="px-4 py-3 bg-gradient-to-r from-indigo-700 to-indigo-600 hover:from-indigo-600 hover:to-indigo-500 text-white rounded-xl border border-indigo-500/50 focus:ring-2 focus:ring-yellow-500 text-sm shadow-lg hover:shadow-xl transition-all duration-300">
                            <option value="all">All Status</option>
                            <option value="online">Online Only</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>

                    <!-- Sort & Results -->
                    <div class="flex items-center gap-4">
                        <select wire:model.live="sortOrder"
                                class="px-6 py-3 bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-white font-bold rounded-xl focus:ring-2 focus:ring-yellow-400 text-sm shadow-lg hover:shadow-xl transition-all duration-300 border border-yellow-400/50">
                            <option value="location_first">📍 Location First</option>
                            <option value="price_low">💰 Price: Low to High</option>
                            <option value="price_high">💰 Price: High to Low</option>
                            <option value="speed">⚡ Speed First</option>
                            <option value="popularity">🔥 Most Popular</option>
                            <option value="latest">🆕 Latest</option>
                        </select>
                    </div>
                </div>

            <!-- Active Filters Display -->
            @if($selected_countries || $selected_categories || $selected_brands || $price_min > 0 || $price_max < 1000)
                <div class="mt-4 pt-4 border-t border-white/20">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-white text-sm font-medium">Active filters:</span>
                        
                        @foreach($selected_countries as $country)
                            <span class="inline-flex items-center px-3 py-1 bg-blue-600/80 text-white text-xs rounded-full">
                                {{ strtoupper($country) }}
                                <button wire:click="removeCountryFilter('{{ $country }}')" class="ml-2 hover:text-red-300">
                                    <x-custom-icon name="x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endforeach

                        @foreach($selected_categories as $categoryId)
                            @php $category = $categories->firstWhere('id', $categoryId) @endphp
                            @if($category)
                                <span class="inline-flex items-center px-3 py-1 bg-green-600/80 text-white text-xs rounded-full">
                                    {{ $category->name }}
                                    <button wire:click="removeCategoryFilter({{ $categoryId }})" class="ml-2 hover:text-red-300">
                                        <x-custom-icon name="x-mark" class="w-3 h-3" />
                                    </button>
                                </span>
                            @endif
                        @endforeach

                        <button wire:click="resetFilters" 
                                class="px-3 py-1 bg-red-600/80 hover:bg-red-600 text-white text-xs rounded-full transition-colors">
                            Clear All
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Results Summary -->
        <div class="flex justify-between items-center mb-6">
            <div class="text-white">
                <span class="text-2xl font-bold">{{ $serverPlans->total() }}</span>
                <span class="text-green-200">proxy plans found</span>
            </div>
        </div>

        <!-- Enhanced Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            @forelse($serverPlans as $plan)
                <div class="group bg-white/5 backdrop-blur-xl rounded-3xl overflow-hidden shadow-2xl hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.25),0_25px_50px_-12px_rgba(34,197,94,0.2)] transition-all duration-500 hover:scale-105 border border-white/20 hover:border-green-400/30 relative" 
                     wire:key="plan-{{ $plan->id }}">
                     
                    <!-- Floating gradient orbs -->
                    <div class="absolute -top-4 -right-4 w-20 h-20 bg-gradient-to-br from-yellow-400/20 to-green-400/20 rounded-full blur-xl animate-pulse"></div>
                    <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-gradient-to-tr from-green-400/20 to-blue-400/20 rounded-full blur-lg animate-bounce delay-75"></div>
                    
                    <!-- Product Image -->
                    <div class="relative aspect-video bg-gradient-to-br from-green-800/90 to-green-700/90 overflow-hidden backdrop-blur-sm border-b border-white/10">
                        <img src="{{ url('storage/' . $plan->product_image) }}"
                             class="w-full h-full object-contain p-6 transition-all duration-500 group-hover:scale-110 group-hover:rotate-1"
                             alt="{{ $plan->name }}"
                             loading="lazy">
                        
                        <!-- Enhanced Status Badge -->
                        @if($plan->server && $plan->server->status)
                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-2 rounded-xl text-xs font-bold shadow-2xl backdrop-blur-md border transition-all duration-300 hover:scale-110
                                    {{ $plan->server->status === 'online' 
                                        ? 'bg-green-500/90 text-white border-green-400/50 shadow-green-500/25' 
                                        : 'bg-red-500/90 text-white border-red-400/50 shadow-red-500/25' }}">
                                    <span class="flex items-center">
                                        <span class="w-2 h-2 rounded-full mr-2 animate-pulse
                                            {{ $plan->server->status === 'online' ? 'bg-green-300' : 'bg-red-300' }}"></span>
                                        {{ ucfirst($plan->server->status) }}
                                    </span>
                                </span>
                            </div>
                        @endif

                        <!-- Enhanced Featured Badge -->
                        @if($plan->featured)
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-2 bg-gradient-to-r from-yellow-500 to-yellow-400 text-black rounded-xl text-xs font-bold shadow-2xl backdrop-blur-md border border-yellow-300/50 flex items-center group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-3 h-3 mr-1 animate-spin" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    Featured
                                </span>
                            </div>
                        @endif

                        <!-- Enhanced Sale Badge -->
                        @if($plan->on_sale)
                            <div class="absolute bottom-4 left-4">
                                <span class="px-3 py-2 bg-gradient-to-r from-red-500 to-red-400 text-white rounded-xl text-xs font-bold shadow-2xl backdrop-blur-md border border-red-300/50 animate-pulse">
                                    🔥 On Sale
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Enhanced Product Info -->
                    <div class="p-8 relative z-10">
                        <!-- Title & Category -->
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-yellow-300 transition-colors duration-300 line-clamp-2">
                                {{ $plan->name }}
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                @if($plan->category)
                                    <span class="px-3 py-1 bg-gradient-to-r from-green-600/40 to-green-500/40 text-green-200 rounded-xl text-xs font-medium backdrop-blur-sm border border-green-500/30">
                                        {{ $plan->category->name }}
                                    </span>
                                @endif
                                @if($plan->brand)
                                    <span class="px-3 py-1 bg-gradient-to-r from-blue-600/40 to-blue-500/40 text-blue-200 rounded-xl text-xs font-medium backdrop-blur-sm border border-blue-500/30">
                                        {{ $plan->brand->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Enhanced Server Details -->
                        @if($plan->server)
                            <div class="mb-6 p-4 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10 hover:border-green-400/30 transition-all duration-300">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="text-center">
                                        <div class="text-white/60 text-xs uppercase tracking-wider mb-1">Location</div>
                                        <div class="font-bold text-green-300 flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ $plan->server->location ?? 'Global' }}
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-white/60 text-xs uppercase tracking-wider mb-1">Speed</div>
                                        <div class="font-bold text-green-300 flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            {{ $plan->bandwidth ?? '1000' }} Mbps
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Enhanced Pricing -->
                        <div class="mb-8 text-center">
                            <div class="flex items-baseline justify-center mb-2">
                                <span class="text-4xl font-bold bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">
                                    {{ Number::currency($plan->price) }}
                                </span>
                                <span class="text-green-200 ml-2 text-lg">/month</span>
                            </div>
                            @if($plan->original_price && $plan->original_price > $plan->price)
                                <div class="flex items-center justify-center text-sm bg-red-500/20 rounded-lg px-3 py-1 border border-red-500/30">
                                    <span class="line-through text-white/60 mr-2">
                                        {{ Number::currency($plan->original_price) }}
                                    </span>
                                    <span class="text-red-300 font-bold">
                                        Save {{ number_format((($plan->original_price - $plan->price) / $plan->original_price) * 100, 0) }}%
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Enhanced Action Buttons -->
                        <div class="space-y-3">
                            <a href="/servers/{{ $plan->slug }}" 
                               class="block w-full bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-center text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 shadow-2xl hover:shadow-yellow-500/25 hover:scale-105 transform border border-yellow-400/50 backdrop-blur-sm">
                                <span class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Details
                                </span>
                            </a>
                            <button wire:click="addToCart({{ $plan->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="addToCart({{ $plan->id }})"
                                    class="w-full bg-gradient-to-r from-green-700 to-green-600 hover:from-green-600 hover:to-green-500 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 disabled:opacity-50 flex items-center justify-center shadow-2xl hover:shadow-green-500/25 hover:scale-105 transform border border-green-400/50 backdrop-blur-sm group">
                                <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="addToCart({{ $plan->id }})">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 2.5M7 13h10m0 0l1.5-1.5"></path>
                                </svg>
                                <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2" wire:loading wire:target="addToCart({{ $plan->id }})"></div>
                                <span wire:loading.remove wire:target="addToCart({{ $plan->id }})">Add to Cart</span>
                                <span wire:loading wire:target="addToCart({{ $plan->id }})">Adding...</span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="text-center py-20">
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-16 border border-white/10 relative overflow-hidden max-w-md mx-auto">
                            <!-- Background decoration -->
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-yellow-400/10 to-green-400/10 rounded-full blur-2xl"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-green-400/10 to-blue-400/10 rounded-full blur-xl"></div>
                            
                            <div class="relative z-10">
                                <div class="mb-8">
                                    <svg class="w-20 h-20 text-white/40 mx-auto animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-3xl font-bold text-white mb-4">No Products Found</h3>
                                <p class="text-green-200 mb-8 text-lg">Try adjusting your filters to discover more proxy solutions.</p>
                                <button wire:click="resetFilters"
                                        class="px-8 py-4 bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-white font-bold rounded-2xl transition-all duration-300 shadow-2xl hover:shadow-yellow-500/25 hover:scale-105 transform border border-yellow-400/50">
                                    Reset All Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Enhanced Pagination -->
        @if($serverPlans->hasPages())
            <div class="mt-16 flex justify-center">
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-8 border border-white/10 shadow-2xl relative overflow-hidden">
                    <!-- Background decoration -->
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-yellow-400/10 to-green-400/10 rounded-full blur-xl"></div>
                    <div class="absolute bottom-0 left-0 w-20 h-20 bg-gradient-to-tr from-green-400/10 to-blue-400/10 rounded-full blur-lg"></div>
                    
                    <div class="relative z-10">
                        {{ $serverPlans->links() }}
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-10px) rotate(1deg); }
    66% { transform: translateY(5px) rotate(-1deg); }
}

@keyframes float-reverse {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(10px) rotate(-1deg); }
    66% { transform: translateY(-5px) rotate(1deg); }
}

.animate-fade-in {
    animation: fadeIn 0.6s ease-out forwards;
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-reverse {
    animation: float-reverse 8s ease-in-out infinite;
}
</style>

