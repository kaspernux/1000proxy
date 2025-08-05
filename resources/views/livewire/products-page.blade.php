@php
use Illuminate\Support\Str;
@endphp

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 font-sans relative overflow-hidden"
     x-data="{ 
        showFilters: false,
        activeFilter: null,
        isLoading: false
     }">

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

    <!-- Enhanced Loading Overlay with Homepage Design -->
    <div wire:loading.delay class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="relative p-8 rounded-2xl bg-gradient-to-br from-gray-900/90 to-gray-800/90 backdrop-blur-lg shadow-2xl border border-blue-500/20">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-400 border-t-transparent"></div>
                    <div class="absolute inset-0 rounded-full h-12 w-12 border-4 border-yellow-400 border-t-transparent animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
                </div>
                <div class="text-white">
                    <div class="text-xl font-bold bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent">Loading Products</div>
                    <div class="text-sm text-gray-300 animate-pulse">Finding the best proxy solutions for you...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Hero Section with Homepage Style -->
    <div class="relative z-10 overflow-hidden">
        <div class="max-w-7xl mx-auto mb-12 px-4 sm:px-6 lg:px-8 pt-8">
            <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-8 md:p-12 border border-white/10 shadow-2xl overflow-hidden">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 30px 30px;"></div>
                </div>
                
                <!-- Floating Gradient Orbs matching Homepage -->
                <div class="absolute top-0 left-0 w-32 h-32 bg-gradient-to-br from-yellow-400/30 to-blue-400/30 rounded-full blur-2xl animate-pulse duration-[4000ms]"></div>
                <div class="absolute bottom-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/30 to-yellow-400/30 rounded-full blur-2xl animate-bounce duration-[5000ms]"></div>
                
                <div class="relative z-10 text-center">
                    <!-- Breadcrumb -->
                    <div class="mb-6">
                        <nav class="flex justify-center items-center space-x-2 text-sm">
                            <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
                            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-500" />
                            <span class="text-blue-400 font-medium">Premium Proxy Solutions</span>
                        </nav>
                    </div>

                    <!-- Main Title with Homepage Style -->
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-6 leading-tight">
                        <span class="block mb-2">Premium</span>
                        <span class="bg-gradient-to-r from-blue-400 via-yellow-400 to-blue-500 bg-clip-text text-transparent">
                            Proxy Solutions
                        </span>
                    </h1>
                    
                    <!-- Description -->
                    <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed mb-8 font-light">
                        Discover high-performance proxy solutions tailored to your specific needs. Fast, secure, and reliable connections for 
                        <span class="text-blue-400 font-semibold">web scraping</span>, 
                        <span class="text-yellow-400 font-semibold">market research</span>, and 
                        <span class="text-blue-300 font-semibold">business automation</span>.
                    </p>

                    <!-- Live Stats Row with Homepage Style -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                        <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10">
                            <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors duration-300">{{ $serverPlans->total() }}</div>
                            <div class="text-gray-400 font-medium">Available Plans</div>
                        </div>
                        <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10">
                            <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-yellow-400 transition-colors duration-300">{{ $countries ? count($countries) : 0 }}+</div>
                            <div class="text-gray-400 font-medium">Global Locations</div>
                        </div>
                        <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10">
                            <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-blue-300 transition-colors duration-300">{{ $categories ? $categories->count() : 0 }}+</div>
                            <div class="text-gray-400 font-medium">Proxy Categories</div>
                        </div>
                        <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10">
                            <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-green-400 transition-colors duration-300">99.9%</div>
                            <div class="text-gray-400 font-medium">Uptime SLA</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Enhanced Filter Bar with Categories Style -->
        <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-10 lg:p-16 xl:p-20 mb-12 shadow-2xl border border-white/10 overflow-hidden ring-1 ring-white/20">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 30px 30px;"></div>
            </div>
            
            <!-- Enhanced Floating Gradient Orbs -->
            <div class="absolute top-0 left-0 w-32 h-32 bg-gradient-to-br from-yellow-400/30 to-blue-400/30 rounded-full blur-2xl animate-pulse duration-[4000ms]"></div>
            <div class="absolute bottom-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/30 to-yellow-400/30 rounded-full blur-2xl animate-bounce duration-[5000ms]"></div>
            <div class="absolute top-1/2 left-1/4 w-24 h-24 bg-gradient-to-br from-purple-400/20 to-pink-400/20 rounded-full blur-xl animate-pulse duration-[6000ms]"></div>
            
            <div class="relative z-10">
                <!-- Enhanced Filter Header -->
                <div class="text-center mb-10 lg:mb-12">
                    <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4 tracking-tight">
                        Find Your Perfect <span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">Proxy Solution</span>
                    </h2>
                    <p class="text-lg lg:text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed font-light tracking-wide">
                        Use our advanced filters to discover the ideal proxy solution for your specific needs
                    </p>
                </div>

                <!-- Enhanced Search Bar Section -->
                <div class="mb-10 lg:mb-12">
                    <div class="mb-4">
                        <label class="block text-lg font-semibold text-white mb-3 tracking-wide flex items-center justify-center lg:justify-start">
                            <x-heroicon-o-magnifying-glass class="mr-3 h-6 w-6 text-blue-400" />
                            Search Proxy Solutions
                        </label>
                        <p class="text-blue-200 text-sm mb-4 text-center lg:text-left lg:ml-9 leading-relaxed">
                            Enter any proxy type, location, brand name, or use case to find matching solutions
                        </p>
                    </div>
                    <div class="relative group">
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               placeholder="Try: 'residential proxy', 'US servers', 'Instagram', 'web scraping', 'high speed datacenter'..."
                               class="w-full px-6 py-4 pl-14 rounded-2xl border-0 bg-white backdrop-blur-sm focus:ring-4 focus:ring-blue-500/50 focus:bg-white transition-all duration-300 text-lg text-black placeholder-gray-400 placeholder:text-sm shadow-lg hover:shadow-xl">
                        <x-heroicon-o-magnifying-glass class="absolute left-5 top-1/2 transform -translate-y-1/2 h-6 w-6 text-gray-500 group-focus-within:text-blue-500 transition-colors duration-300" />
                        @if($search)
                            <button wire:click="$set('search', '')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100 transition-all duration-200">
                                <x-heroicon-o-x-mark class="h-5 w-5" />
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Enhanced Filter Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8 mb-8">
                    <!-- Location Filter with Enhanced Styling -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-white mb-2 tracking-wide flex items-center">
                            <x-heroicon-o-globe-alt class="mr-2 h-5 w-5 text-green-400" />
                            Server Location
                        </label>
                        <p class="text-green-200 text-xs mb-3 leading-relaxed">
                            Choose specific countries or regions
                        </p>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-4 bg-gradient-to-r from-green-700 to-green-600 hover:from-green-600 hover:to-green-500 text-white rounded-2xl transition-all duration-300 border border-green-500/50 shadow-xl hover:shadow-2xl hover:shadow-green-500/25 group hover:scale-105 focus:ring-4 focus:ring-green-500/30">
                                <span class="flex items-center text-sm font-bold">
                                    <x-heroicon-o-map-pin class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
                                    {{ count($selected_countries) > 0 ? count($selected_countries) . ' selected' : 'All Countries' }}
                                </span>
                                <x-heroicon-o-chevron-down class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" ::class="open ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute top-full left-0 right-0 mt-3 bg-green-900/95 backdrop-blur-md rounded-2xl shadow-2xl border border-green-600/50 z-40 max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-green-500 scrollbar-track-gray-200">
                                <div class="p-2">
                                    @foreach($countries as $country)
                                        <label class="flex items-center px-3 py-3 hover:bg-green-700/50 cursor-pointer text-white transition-colors group rounded-xl border-b border-green-800/20 last:border-b-0">
                                            <input type="checkbox" wire:model.live="selected_countries" value="{{ $country }}"
                                                   class="mr-3 rounded border-green-500 text-yellow-500 focus:ring-yellow-500 focus:ring-2 transition-all">
                                            <span class="text-sm font-medium group-hover:text-yellow-200 transition-colors">{{ strtoupper($country) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Filter with Enhanced Styling -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-white mb-2 tracking-wide flex items-center">
                            <x-heroicon-o-rectangle-stack class="mr-2 h-5 w-5 text-blue-400" />
                            Proxy Category
                        </label>
                        <p class="text-blue-200 text-xs mb-3 leading-relaxed">
                            Select proxy type and use case
                        </p>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-4 bg-gradient-to-r from-blue-700 to-blue-600 hover:from-blue-600 hover:to-blue-500 text-white rounded-2xl transition-all duration-300 border border-blue-500/50 shadow-xl hover:shadow-2xl hover:shadow-blue-500/25 group hover:scale-105 focus:ring-4 focus:ring-blue-500/30">
                                <span class="flex items-center text-sm font-bold">
                                    <x-heroicon-o-squares-2x2 class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
                                    {{ count($selected_categories) > 0 ? count($selected_categories) . ' selected' : 'All Categories' }}
                                </span>
                                <x-heroicon-o-chevron-down class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" ::class="open ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute top-full left-0 right-0 mt-3 bg-blue-900/95 backdrop-blur-md rounded-2xl shadow-2xl border border-blue-600/50 z-40 max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-blue-500 scrollbar-track-gray-200">
                                <div class="p-2">
                                    @foreach($categories as $category)
                                        <label class="flex items-center px-3 py-3 hover:bg-blue-700/50 cursor-pointer text-white transition-colors group rounded-xl border-b border-blue-800/20 last:border-b-0">
                                            <input type="checkbox" wire:model.live="selected_categories" value="{{ $category->id }}"
                                                   class="mr-3 rounded border-blue-500 text-yellow-500 focus:ring-yellow-500 focus:ring-2 transition-all">
                                            <span class="text-sm font-medium group-hover:text-yellow-200 transition-colors">{{ $category->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Brand Filter with Enhanced Styling -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-white mb-2 tracking-wide flex items-center">
                            <x-heroicon-o-building-office class="mr-2 h-5 w-5 text-purple-400" />
                            Proxy Brand
                        </label>
                        <p class="text-purple-200 text-xs mb-3 leading-relaxed">
                            Filter by trusted proxy providers
                        </p>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-4 bg-gradient-to-r from-purple-700 to-purple-600 hover:from-purple-600 hover:to-purple-500 text-white rounded-2xl transition-all duration-300 border border-purple-500/50 shadow-xl hover:shadow-2xl hover:shadow-purple-500/25 group hover:scale-105 focus:ring-4 focus:ring-purple-500/30">
                                <span class="flex items-center text-sm font-bold">
                                    <x-heroicon-o-tag class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
                                    {{ count($selected_brands) > 0 ? count($selected_brands) . ' selected' : 'All Brands' }}
                                </span>
                                <x-heroicon-o-chevron-down class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" ::class="open ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute top-full left-0 right-0 mt-3 bg-purple-900/95 backdrop-blur-md rounded-2xl shadow-2xl border border-purple-600/50 z-40 max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-purple-500 scrollbar-track-gray-200">
                                <div class="p-2">
                                    @foreach($brands as $brand)
                                        <label class="flex items-center px-3 py-3 hover:bg-purple-700/50 cursor-pointer text-white transition-colors group rounded-xl border-b border-purple-800/20 last:border-b-0">
                                            <input type="checkbox" wire:model.live="selected_brands" value="{{ $brand->id }}"
                                                   class="mr-3 rounded border-purple-500 text-yellow-500 focus:ring-yellow-500 focus:ring-2 transition-all">
                                            <span class="text-sm font-medium group-hover:text-yellow-200 transition-colors">{{ $brand->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Range Filter with Enhanced Styling -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-white mb-2 tracking-wide flex items-center">
                            <x-heroicon-o-currency-dollar class="mr-2 h-5 w-5 text-orange-400" />
                            Price Range
                        </label>
                        <p class="text-orange-200 text-xs mb-3 leading-relaxed">
                            Set your budget preferences
                        </p>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-4 bg-gradient-to-r from-orange-700 to-orange-600 hover:from-orange-600 hover:to-orange-500 text-white rounded-2xl transition-all duration-300 border border-orange-500/50 shadow-xl hover:shadow-2xl hover:shadow-orange-500/25 group hover:scale-105 focus:ring-4 focus:ring-orange-500/30">
                                <span class="flex items-center text-sm font-bold">
                                    <x-heroicon-o-banknotes class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
                                    ${{ $price_min }} - ${{ $price_max }}
                                </span>
                                <x-heroicon-o-chevron-down class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" ::class="open ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute top-full left-0 right-0 mt-3 bg-orange-900/95 backdrop-blur-md rounded-2xl shadow-2xl border border-orange-600/50 z-40 p-6">
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-3">
                                        <label class="text-white text-sm font-bold min-w-[40px]">Min:</label>
                                        <input type="number" wire:model.live="price_min" min="0" max="1000"
                                               class="flex-1 px-4 py-3 bg-orange-800/50 text-white rounded-xl border border-orange-600 focus:ring-2 focus:ring-yellow-500 text-sm backdrop-blur-sm font-medium transition-all hover:bg-orange-800/70">
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <label class="text-white text-sm font-bold min-w-[40px]">Max:</label>
                                        <input type="number" wire:model.live="price_max" min="0" max="1000"
                                               class="flex-1 px-4 py-3 bg-orange-800/50 text-white rounded-xl border border-orange-600 focus:ring-2 focus:ring-yellow-500 text-sm backdrop-blur-sm font-medium transition-all hover:bg-orange-800/70">
                                    </div>
                                    <div class="text-center text-yellow-400 text-lg font-bold bg-orange-800/30 py-3 rounded-xl border border-orange-600/30">
                                        ${{ $price_min }} - ${{ $price_max }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Filter Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 mb-8">
                    <!-- Protocol Filter -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-white mb-2 tracking-wide flex items-center">
                            <x-heroicon-o-cog-6-tooth class="mr-2 h-5 w-5 text-teal-400" />
                            Protocol Type
                        </label>
                        <select wire:model.live="selected_protocols" 
                                class="w-full px-4 py-4 bg-gradient-to-r from-teal-700 to-teal-600 hover:from-teal-600 hover:to-teal-500 text-white rounded-2xl border border-teal-500/50 focus:ring-4 focus:ring-teal-500/30 text-sm shadow-xl hover:shadow-2xl hover:shadow-teal-500/25 transition-all duration-300 hover:scale-105 font-bold cursor-pointer">
                            <option value="" class="!bg-gray-900 !text-white">All Protocols</option>
                            @foreach($protocols as $protocol)
                                <option value="{{ $protocol }}" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">{{ $protocol }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-white mb-2 tracking-wide flex items-center">
                            <x-heroicon-o-signal class="mr-2 h-5 w-5 text-indigo-400" />
                            Server Status
                        </label>
                        <select wire:model.live="server_status" 
                                class="w-full px-4 py-4 bg-gradient-to-r from-indigo-700 to-indigo-600 hover:from-indigo-600 hover:to-indigo-500 text-white rounded-2xl border border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/30 text-sm shadow-xl hover:shadow-2xl hover:shadow-indigo-500/25 transition-all duration-300 hover:scale-105 font-bold cursor-pointer">
                            <option value="all" class="!bg-gray-900 !text-white">All Status</option>
                            <option value="online" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Online Only</option>
                            <option value="offline" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Offline</option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-white mb-2 tracking-wide flex items-center">
                            <x-heroicon-o-arrows-up-down class="mr-2 h-5 w-5 text-yellow-400" />
                            Sort Results
                        </label>
                        <select wire:model.live="sortOrder"
                                class="w-full px-4 py-4 bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-white font-bold rounded-2xl focus:ring-4 focus:ring-yellow-400/30 text-sm shadow-xl hover:shadow-2xl hover:shadow-yellow-500/25 transition-all duration-300 border border-yellow-400/50 hover:scale-105 cursor-pointer">
                            <option value="location_first" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Location First</option>
                            <option value="price_low" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Price: Low to High</option>
                            <option value="price_high" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Price: High to Low</option>
                            <option value="speed" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Speed First</option>
                            <option value="popularity" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Most Popular</option>
                            <option value="latest" class="!bg-gray-900 !text-white hover:!bg-gray-700 hover:!text-yellow-400">Latest</option>
                        </select>
                    </div>
                </div>

                <!-- Enhanced Active Filters Display -->
                @if($search || $selected_countries || $selected_categories || $selected_brands || $selected_protocols || $price_min > 0 || $price_max < 1000)
                    <div class="mt-8 pt-6 border-t border-white/20">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-white text-sm font-bold flex items-center">
                                <x-heroicon-o-funnel class="w-4 h-4 mr-2" />
                                Active filters:
                            </span>
                            
                            @if($search)
                                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-cyan-600/80 to-cyan-500/80 text-white text-sm font-medium rounded-xl border border-cyan-400/50 shadow-lg animate-bounce-in">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4 mr-2" />
                                    "{{ Str::limit($search, 20) }}"
                                    <button wire:click="$set('search', '')" class="ml-2 hover:text-red-300 transition-colors">
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                    </button>
                                </span>
                            @endif
                            
                            @foreach($selected_countries as $country)
                                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600/80 to-green-500/80 text-white text-sm font-medium rounded-xl border border-green-400/50 shadow-lg animate-bounce-in">
                                    <x-heroicon-o-map-pin class="w-4 h-4 mr-2" />
                                    {{ strtoupper($country) }}
                                    <button wire:click="removeCountryFilter('{{ $country }}')" class="ml-2 hover:text-red-300 transition-colors">
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                    </button>
                                </span>
                            @endforeach

                            @foreach($selected_categories as $categoryId)
                                @php $category = $categories->firstWhere('id', $categoryId) @endphp
                                @if($category)
                                    <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600/80 to-blue-500/80 text-white text-sm font-medium rounded-xl border border-blue-400/50 shadow-lg animate-bounce-in">
                                        <x-heroicon-o-rectangle-stack class="w-4 h-4 mr-2" />
                                        {{ $category->name }}
                                        <button wire:click="removeCategoryFilter({{ $categoryId }})" class="ml-2 hover:text-red-300 transition-colors">
                                            <x-heroicon-o-x-mark class="w-4 h-4" />
                                        </button>
                                    </span>
                                @endif
                            @endforeach

                            @foreach($selected_brands as $brandId)
                                @php $brand = $brands->firstWhere('id', $brandId) @endphp
                                @if($brand)
                                    <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600/80 to-purple-500/80 text-white text-sm font-medium rounded-xl border border-purple-400/50 shadow-lg animate-bounce-in">
                                        <x-heroicon-o-building-office class="w-4 h-4 mr-2" />
                                        {{ $brand->name }}
                                        <button wire:click="removeBrandFilter({{ $brandId }})" class="ml-2 hover:text-red-300 transition-colors">
                                            <x-heroicon-o-x-mark class="w-4 h-4" />
                                        </button>
                                    </span>
                                @endif
                            @endforeach

                            @if($selected_protocols)
                                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-teal-600/80 to-teal-500/80 text-white text-sm font-medium rounded-xl border border-teal-400/50 shadow-lg animate-bounce-in">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 mr-2" />
                                    {{ $selected_protocols }}
                                    <button wire:click="$set('selected_protocols', '')" class="ml-2 hover:text-red-300 transition-colors">
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                    </button>
                                </span>
                            @endif

                            @if($price_min > 0 || $price_max < 1000)
                                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-600/80 to-orange-500/80 text-white text-sm font-medium rounded-xl border border-orange-400/50 shadow-lg animate-bounce-in">
                                    <x-heroicon-o-currency-dollar class="w-4 h-4 mr-2" />
                                    ${{ $price_min }} - ${{ $price_max }}
                                    <button wire:click="resetPriceFilter" class="ml-2 hover:text-red-300 transition-colors">
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                    </button>
                                </span>
                            @endif

                            <button wire:click="resetFilters" 
                                    class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-red-600/80 to-red-500/80 hover:from-red-600 hover:to-red-500 text-white text-sm font-bold rounded-xl transition-all duration-300 shadow-lg hover:shadow-red-500/25 hover:scale-105 border border-red-400/50">
                                <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                                Clear All Filters
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Live Search Results Preview -->
                @if($search && $serverPlans->count() > 0)
                    <div class="mt-8 p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-white flex items-center">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5 mr-2 text-blue-400" />
                                Search Results for "{{ $search }}"
                            </h3>
                            <span class="text-sm text-gray-300">{{ $serverPlans->total() }} results found</span>
                        </div>
                        <div class="text-sm text-gray-300">
                            Showing the most relevant proxy solutions based on your search criteria.
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Enhanced Results Summary -->
        <div class="flex justify-between items-center mb-8 p-6 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10">
            <div class="flex items-center space-x-4">
                <div class="text-white">
                    <span class="text-3xl font-bold bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">{{ $serverPlans->total() }}</span>
                    <span class="text-gray-300 text-lg ml-2">proxy plans found</span>
                </div>
                @if($serverPlans->total() > 0)
                    <div class="flex items-center text-green-400 text-sm">
                        <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                        Updated {{ now()->diffForHumans() }}
                    </div>
                @endif
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-400">Showing {{ $serverPlans->firstItem() ?? 0 }}-{{ $serverPlans->lastItem() ?? 0 }} of {{ $serverPlans->total() }}</span>
            </div>
        </div>

        <!-- Enhanced Products Grid with Categories Style -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @forelse($serverPlans as $plan)
                <div class="group relative overflow-hidden bg-white/5 backdrop-blur-md rounded-3xl shadow-2xl hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.25),0_25px_50px_-12px_rgba(34,197,94,0.3)] border border-white/10 transition-all duration-500 hover:scale-105 hover:bg-white/10 animate-fade-in" 
                     wire:key="plan-{{ $plan->id }}"
                     style="animation-delay: {{ $loop->index * 0.1 }}s">
                
                <!-- Enhanced Background Gradient -->
                <div class="absolute inset-0 bg-gradient-to-br from-green-600/20 to-green-800/20 group-hover:from-yellow-600/20 group-hover:to-yellow-800/20 transition-all duration-300"></div>
                
                <!-- Enhanced Floating Gradient Orbs -->
                <div class="absolute -top-6 -right-6 w-24 h-24 bg-gradient-to-br from-yellow-400/30 to-green-400/30 rounded-full blur-2xl animate-pulse group-hover:animate-bounce"></div>
                <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-gradient-to-tr from-green-400/25 to-blue-400/25 rounded-full blur-xl animate-bounce delay-75 group-hover:animate-pulse"></div>
                
                <!-- Product Header Section -->
                <div class="relative p-6">
                    <!-- Product Image & Status Section -->
                    <div class="flex items-start space-x-4 mb-6">
                        <!-- Product Image -->
                        <div class="flex-shrink-0 relative">
                            <div class="relative group-hover:scale-110 transition-transform duration-500">
                                @php
                                    $imageUrl = null;
                                    $altText = $plan->name;
                                    
                                    // Priority 1: Product image
                                    if (!empty($plan->product_image) && file_exists(storage_path('app/public/'.$plan->product_image))) {
                                        $imageUrl = asset('storage/'.$plan->product_image);
                                        $altText = $plan->name . ' - Product Image';
                                    }
                                    // Priority 2: Brand image
                                    elseif ($plan->brand && !empty($plan->brand->image) && file_exists(storage_path('app/public/'.$plan->brand->image))) {
                                        $imageUrl = asset('storage/'.$plan->brand->image);
                                        $altText = $plan->brand->name . ' Brand Logo';
                                    }
                                    // Priority 3: Category image (if exists)
                                    elseif ($plan->category && !empty($plan->category->image) && file_exists(storage_path('app/public/'.$plan->category->image))) {
                                        $imageUrl = asset('storage/'.$plan->category->image);
                                        $altText = $plan->category->name . ' Category';
                                    }
                                    // Priority 4: Default fallback SVG
                                    else {
                                        $imageUrl = asset('images/default-proxy.svg');
                                        $altText = 'Default Proxy Server Image';
                                    }
                                @endphp
                                
                                <div class="w-20 h-20 rounded-2xl overflow-hidden shadow-2xl border-2 border-yellow-400/50 group-hover:border-yellow-400 transition-all duration-300 bg-gray-800">
                                    <img class="w-full h-full object-cover" 
                                         src="{{ $imageUrl }}" 
                                         alt="{{ $altText }}"
                                         loading="lazy"
                                         onerror="this.src='{{ asset('images/default-proxy.svg') }}';">
                                </div>
                                
                                <!-- Enhanced Glow Effect -->
                                <div class="absolute inset-0 w-20 h-20 rounded-2xl bg-gradient-to-br from-yellow-400/30 to-green-400/30 blur-lg group-hover:blur-xl group-hover:from-yellow-400/40 group-hover:to-green-400/40 transition-all duration-300"></div>
                                
                                <!-- Image Type Indicator -->
                                @if(!empty($plan->product_image))
                                    <div class="absolute -bottom-1 -right-1">
                                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-green-500 border-2 border-gray-800 shadow-lg">
                                            <x-heroicon-o-check class="w-3 h-3 text-white" />
                                        </span>
                                    </div>
                                @elseif($plan->brand && !empty($plan->brand->image))
                                    <div class="absolute -bottom-1 -right-1">
                                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-blue-500 border-2 border-gray-800 shadow-lg">
                                            <x-heroicon-o-building-office class="w-3 h-3 text-white" />
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Status Indicator -->
                            @if($plan->server && $plan->server->status)
                                <div class="absolute -top-2 -right-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full border-2 border-gray-800 shadow-lg 
                                        {{ $plan->server->status === 'online' 
                                            ? 'bg-green-500 animate-pulse' 
                                            : 'bg-red-500' }}">
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Product Details -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-white group-hover:text-yellow-400 transition-colors duration-300 mb-2 line-clamp-2 leading-tight">
                                {{ $plan->name }}
                            </h3>
                            
                            <!-- Category & Brand Tags -->
                            <div class="flex flex-wrap gap-1 mb-3">
                                @if($plan->category)
                                    <span class="inline-flex items-center px-2 py-1 bg-green-600/40 text-green-200 text-xs font-medium rounded-lg border border-green-500/30">
                                        <x-heroicon-o-rectangle-stack class="w-3 h-3 mr-1" />
                                        {{ Str::limit($plan->category->name, 8) }}
                                    </span>
                                @endif
                                @if($plan->brand)
                                    <span class="inline-flex items-center px-2 py-1 bg-blue-600/40 text-blue-200 text-xs font-medium rounded-lg border border-blue-500/30">
                                        <x-heroicon-o-building-office class="w-3 h-3 mr-1" />
                                        {{ Str::limit($plan->brand->name, 8) }}
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Server Location -->
                            @if($plan->server)
                                <div class="flex items-center text-xs text-green-300/80 group-hover:text-white/80 transition-colors duration-300">
                                    <x-heroicon-o-map-pin class="w-3 h-3 mr-1" />
                                    <span>{{ $plan->server->location ?? 'Global' }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Featured/Sale Badges -->
                        <div class="flex-shrink-0 flex flex-col items-end space-y-2">
                            @if($plan->featured)
                                <span class="px-2 py-1 bg-gradient-to-r from-yellow-500 to-yellow-400 text-black text-xs font-bold rounded-lg shadow-lg flex items-center">
                                    <x-heroicon-o-star class="w-3 h-3 mr-1" />
                                    HOT
                                </span>
                            @endif
                            @if($plan->on_sale)
                                <span class="px-2 py-1 bg-gradient-to-r from-red-500 to-red-400 text-white text-xs font-bold rounded-lg shadow-lg animate-pulse flex items-center">
                                    <x-heroicon-o-tag class="w-3 h-3 mr-1" />
                                    SALE
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Server Performance Stats -->
                    @if($plan->server)
                        <div class="grid grid-cols-2 gap-3 mb-6 p-4 bg-gray-900/60 backdrop-blur-sm rounded-2xl border border-white/10 group-hover:border-green-400/30 transition-all duration-300">
                            <div class="text-center">
                                <div class="flex items-center justify-center mb-1">
                                    <x-heroicon-o-bolt class="w-4 h-4 text-green-400 mr-1" />
                                    <span class="text-xs text-gray-300">Speed</span>
                                </div>
                                <div class="text-sm font-bold text-green-300">{{ $plan->bandwidth ?? '1000' }} Mbps</div>
                            </div>
                            <div class="text-center">
                                <div class="flex items-center justify-center mb-1">
                                    <x-heroicon-o-check-circle class="w-4 h-4 text-blue-400 mr-1" />
                                    <span class="text-xs text-gray-300">Uptime</span>
                                </div>
                                <div class="text-sm font-bold text-blue-300">99.9%</div>
                            </div>
                        </div>
                    @endif

                    <!-- Enhanced Pricing Section -->
                    <div class="text-center mb-6">
                        <div class="flex items-center justify-center mb-2">
                            <span class="text-3xl font-bold bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">
                                {{ Number::currency($plan->price) }}
                            </span>
                            <span class="text-green-200 ml-2 text-sm">/month</span>
                        </div>
                        @if($plan->original_price && $plan->original_price > $plan->price)
                            <div class="flex items-center justify-center space-x-2 text-xs">
                                <span class="line-through text-white/50">{{ Number::currency($plan->original_price) }}</span>
                                <span class="px-2 py-1 bg-red-500/20 text-red-300 rounded-lg border border-red-500/30 font-bold">
                                    -{{ number_format((($plan->original_price - $plan->price) / $plan->original_price) * 100, 0) }}%
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <a href="/servers/{{ $plan->slug }}" 
                           class="block w-full bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-center text-white font-bold py-3 px-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-yellow-500/25 hover:scale-105 border border-yellow-400/50 text-sm">
                            <span class="flex items-center justify-center">
                                <x-heroicon-o-eye class="w-4 h-4 mr-2" />
                                View Details
                            </span>
                        </a>
                        <button wire:click="addToCart({{ $plan->id }})"
                                wire:loading.attr="disabled"
                                wire:target="addToCart({{ $plan->id }})"
                                class="w-full bg-gradient-to-r from-green-700 to-green-600 hover:from-green-600 hover:to-green-500 text-white font-bold py-3 px-4 rounded-xl transition-all duration-300 disabled:opacity-50 flex items-center justify-center shadow-lg hover:shadow-green-500/25 hover:scale-105 border border-green-400/50 group text-sm">
                            <x-heroicon-o-shopping-cart class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" wire:loading.remove wire:target="addToCart({{ $plan->id }})" />
                            <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2" wire:loading wire:target="addToCart({{ $plan->id }})"></div>
                            <span wire:loading.remove wire:target="addToCart({{ $plan->id }})">Add to Cart</span>
                            <span wire:loading wire:target="addToCart({{ $plan->id }})">Adding...</span>
                        </button>
                    </div>
                </div>

                <!-- Hover Effects -->
                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                    <div class="absolute top-4 right-4 w-2 h-2 bg-yellow-400 rounded-full animate-ping"></div>
                    <div class="absolute bottom-4 left-4 w-1 h-1 bg-green-400 rounded-full animate-ping" style="animation-delay: 0.5s;"></div>
                    <div class="absolute top-1/2 left-4 w-1.5 h-1.5 bg-blue-400 rounded-full animate-ping" style="animation-delay: 1s;"></div>
                </div>
                </div>
            @empty
                <!-- Enhanced Empty State with Categories Style -->
                <div class="col-span-full">
                    <div class="text-center py-20">
                        <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-16 border border-white/10 shadow-2xl overflow-hidden max-w-2xl mx-auto">
                            <!-- Background Pattern -->
                            <div class="absolute inset-0 opacity-10">
                                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 30px 30px;"></div>
                            </div>
                            
                            <!-- Enhanced Floating Gradient Orbs -->
                            <div class="absolute top-0 left-0 w-32 h-32 bg-gradient-to-br from-yellow-400/30 to-blue-400/30 rounded-full blur-2xl animate-pulse duration-[4000ms]"></div>
                            <div class="absolute bottom-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/30 to-yellow-400/30 rounded-full blur-2xl animate-bounce duration-[5000ms]"></div>
                            
                            <div class="relative z-10">
                                <div class="mb-8">
                                    <x-heroicon-o-magnifying-glass class="w-24 h-24 text-white/40 mx-auto animate-pulse" />
                                </div>
                                <h3 class="text-4xl md:text-5xl font-extrabold text-white mb-6">No Products Found</h3>
                                <p class="text-lg md:text-xl text-gray-300 mb-8 max-w-md mx-auto leading-relaxed">
                                    We couldn't find any proxy solutions matching your criteria. Try adjusting your filters to discover more options.
                                </p>
                                
                                <!-- Quick Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                    <button wire:click="resetFilters"
                                            class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-white font-bold rounded-2xl transition-all duration-300 shadow-2xl hover:shadow-yellow-500/25 hover:scale-105 border border-yellow-400/50">
                                        <x-heroicon-o-arrow-path class="w-5 h-5 mr-2" />
                                        Reset All Filters
                                    </button>
                                    <a href="/categories" 
                                       class="inline-flex items-center px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-medium rounded-2xl transition-all duration-300 border border-white/20 hover:border-white/40">
                                        <x-heroicon-o-rectangle-stack class="w-5 h-5 mr-2" />
                                        Browse Categories
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Enhanced Pagination -->
        @if($serverPlans->hasPages())
            <div class="mt-16 flex justify-center">
                <div class="relative bg-gradient-to-br from-gray-800/90 to-gray-900/90 backdrop-blur-xl rounded-3xl p-8 border border-yellow-400/30 shadow-2xl overflow-hidden">
                    <!-- Background decoration -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-yellow-400/20 to-blue-400/20 rounded-full blur-2xl animate-pulse"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-green-400/20 to-yellow-400/20 rounded-full blur-xl animate-bounce"></div>
                    
                    <!-- Enhanced Pagination Title -->
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">
                            <span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">Navigate Results</span>
                        </h3>
                        <p class="text-gray-300 text-sm">Showing {{ $serverPlans->firstItem() ?? 0 }}-{{ $serverPlans->lastItem() ?? 0 }} of {{ $serverPlans->total() }} proxy solutions</p>
                    </div>
                    
                    <div class="relative z-10 pagination-wrapper">
                        {{ $serverPlans->links('custom.pagination') }}
                    </div>
                </div>
            </div>
        @endif
    </section>

    <!-- Enhanced Custom Styles -->
    <style>
    @keyframes fadeIn {
        from { 
            opacity: 0; 
            transform: translateY(30px) scale(0.95); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
        }
    }

    @keyframes float {
        0%, 100% { 
            transform: translateY(0px) rotate(0deg); 
        }
        33% { 
            transform: translateY(-10px) rotate(1deg); 
        }
        66% { 
            transform: translateY(-5px) rotate(-1deg); 
        }
    }

    @keyframes float-reverse {
        0%, 100% { 
            transform: translateY(0px) rotate(0deg); 
        }
        33% { 
            transform: translateY(10px) rotate(-1deg); 
        }
        66% { 
            transform: translateY(5px) rotate(1deg); 
        }
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }

    @keyframes glow {
        0%, 100% { 
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1); 
        }
        50% { 
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.2), 0 0 40px rgba(34, 197, 94, 0.1); 
        }
    }

    @keyframes pulse-dot {
        0%, 100% { 
            opacity: 0.4; 
            transform: scale(1); 
        }
        50% { 
            opacity: 1; 
            transform: scale(1.2); 
        }
    }

    @keyframes bounce-in {
        0% {
            transform: scale(0.3) rotate(-15deg);
            opacity: 0;
        }
        50% {
            transform: scale(1.05) rotate(5deg);
        }
        70% {
            transform: scale(0.9) rotate(-2deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.8s ease-out forwards;
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-float-reverse {
        animation: float-reverse 8s ease-in-out infinite;
    }

    .animate-shimmer {
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        background-size: 200% 100%;
        animation: shimmer 2s infinite;
    }

    .animate-glow {
        animation: glow 3s ease-in-out infinite;
    }

    .animate-pulse-dot {
        animation: pulse-dot 1.5s ease-in-out infinite;
    }

    .animate-bounce-in {
        animation: bounce-in 0.6s ease-out forwards;
    }

    /* Custom scrollbar for dropdowns */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(34, 197, 94, 0.6);
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(34, 197, 94, 0.8);
    }

    /* Line clamp utilities */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
    }

    /* Enhanced gradient backgrounds */
    .bg-gradient-mesh {
        background: radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 40% 40%, rgba(34, 197, 94, 0.1) 0%, transparent 50%);
    }

    /* Card hover effects */
    .card-hover-effect {
        position: relative;
        overflow: hidden;
    }

    .card-hover-effect::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s ease-in-out;
        z-index: 1;
    }

    .card-hover-effect:hover::before {
        left: 100%;
    }

    /* Smooth transitions for all interactive elements */
    * {
        transition-property: transform, background-color, border-color, opacity, box-shadow, filter;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 300ms;
    }

    /* Enhanced filter dropdown animations */
    .filter-dropdown {
        transform: translateY(-10px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .filter-dropdown.show {
        transform: translateY(0);
        opacity: 1;
    }

    /* Price badge animations */
    .price-badge {
        position: relative;
        overflow: hidden;
    }

    .price-badge::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmer 3s infinite;
    }

    /* Status indicator pulse */
    .status-online {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes status-pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Button press effect */
    .btn-press:active {
        transform: scale(0.95);
        transition: transform 0.1s ease-in-out;
    }

    /* Enhanced select styling for better contrast */
    select option {
        background-color: #111827 !important;
        color: #ffffff !important;
        padding: 12px !important;
        border: none !important;
    }

    select option:checked {
        background-color: #1f2937 !important;
        color: #fbbf24 !important;
    }

    select option:hover {
        background-color: #374151 !important;
        color: #fbbf24 !important;
    }

    /* Force dark styling for select dropdowns */
    select {
        background-color: inherit !important;
        color: white !important;
    }

    /* Enhanced pagination styling */
    .pagination-wrapper nav {
        background: transparent !important;
    }
    
    .pagination-wrapper nav[role="navigation"] {
        background: transparent !important;
    }
    
    /* Remove conflicting pagination styles and let custom pagination handle styling */
    .pagination-wrapper nav span,
    .pagination-wrapper nav a {
        background: inherit !important;
        color: inherit !important;
        border: inherit !important;
        margin: inherit !important;
        border-radius: inherit !important;
        padding: inherit !important;
        font-weight: inherit !important;
    }

    .pagination-wrapper nav a:hover {
        background: inherit !important;
        color: inherit !important;
        transform: inherit !important;
        box-shadow: inherit !important;
    }

    .pagination-wrapper nav span[aria-current="page"] {
        background: inherit !important;
        color: inherit !important;
        box-shadow: inherit !important;
        transform: inherit !important;
    }

    .pagination-wrapper nav span.relative {
        background: inherit !important;
        color: inherit !important;
        cursor: inherit !important;
    }

    /* Style pagination text */
    .pagination-text {
        color: rgba(156, 163, 175, 0.9) !important;
    }

    /* Pagination wrapper styling */
    .pagination-wrapper {
        filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.5));
    }

    .pagination-wrapper nav {
        background: transparent !important;
    }

    /* Additional select styling improvements */
    select:focus {
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5) !important;
    }

    /* Improve dropdown menu contrast in all browsers */
    option {
        background-color: #111827 !important;
        color: #ffffff !important;
    }

    option:hover,
    option:focus {
        background-color: #374151 !important;
        color: #fbbf24 !important;
    }

    /* Ensure dropdown text is always visible */
    .dropdown-item {
        background-color: rgba(31, 41, 55, 0.95) !important;
        backdrop-filter: blur(8px);
        color: #ffffff !important;
    }

    .dropdown-item:hover {
        background-color: rgba(55, 65, 81, 0.95) !important;
        color: #fbbf24 !important;
    }

    /* Enhanced scrollbar styles for dropdowns */
    .scrollbar-thin {
        scrollbar-width: thin;
    }

    .scrollbar-thumb-green-500 {
        scrollbar-color: #10b981 transparent;
    }

    .scrollbar-thumb-blue-500 {
        scrollbar-color: #3b82f6 transparent;
    }

    .scrollbar-thumb-purple-500 {
        scrollbar-color: #8b5cf6 transparent;
    }

    .scrollbar-track-gray-200 {
        scrollbar-color: #e5e7eb transparent;
    }

    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: rgba(31, 41, 55, 0.3);
        border-radius: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: linear-gradient(45deg, #3b82f6, #10b981);
        border-radius: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(45deg, #2563eb, #059669);
    }

    /* Enhanced bounce-in animation for filter badges */
    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3) translateY(20px);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .animate-bounce-in {
        animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    /* Enhanced focus states for accessibility */
    .focus\:ring-4:focus {
        --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
        --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) var(--tw-ring-color);
        box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
    }

    /* Enhanced transition for smooth animations */
    .transition-all-300 {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Search input focus enhancements */
    .search-input:focus {
        transform: scale(1.02);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 4px rgba(59, 130, 246, 0.5);
    }

    /* Dropdown enhancement animations */
    .dropdown-enter {
        animation: dropdownEnter 0.3s ease-out forwards;
    }

    @keyframes dropdownEnter {
        0% {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Enhanced hover effects for better UX */
    .filter-button:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* Loading state animations */
    .loading-pulse {
        animation: loadingPulse 2s ease-in-out infinite;
    }

    @keyframes loadingPulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    </style>
</div>

