@extends('layouts.app')

@section('content')
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

    <!-- Header Section -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-green-900/50 to-green-600/50"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-4">
                    <span class="bg-gradient-to-r from-yellow-400 to-yellow-200 bg-clip-text text-transparent">Premium Proxies</span>
                </h1>
                <p class="text-xl text-green-100 max-w-2xl mx-auto">
                    Discover high-performance proxy solutions tailored to your needs
                </p>
            </div>
        </div>
    </div>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Compact Filter Bar -->
        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 mb-8 shadow-xl border border-white/20">
            <div class="flex flex-col lg:flex-row gap-4 items-center">
                <!-- Quick Filters Row -->
                <div class="flex-1 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 w-full">
                    <!-- Location Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-4 py-3 bg-green-800/80 hover:bg-green-700 text-white rounded-lg transition-all duration-200 border border-green-600">
                            <span class="flex items-center text-sm font-medium">
                                <x-custom-icon name="globe-alt" class="w-4 h-4 mr-2" />
                                Location
                            </span>
                            <x-custom-icon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="absolute top-full left-0 right-0 mt-2 bg-green-900 rounded-lg shadow-xl border border-green-600 z-20 max-h-48 overflow-y-auto">
                            @foreach($countries as $country)
                                <label class="flex items-center px-4 py-2 hover:bg-green-700 cursor-pointer text-white">
                                    <input type="checkbox" wire:model.live="selected_countries" value="{{ $country }}"
                                           class="mr-3 rounded border-green-500 text-yellow-500 focus:ring-yellow-500">
                                    <span class="text-sm">{{ strtoupper($country) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Category Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-4 py-3 bg-green-800/80 hover:bg-green-700 text-white rounded-lg transition-all duration-200 border border-green-600">
                            <span class="flex items-center text-sm font-medium">
                                <x-custom-icon name="folder" class="w-4 h-4 mr-2" />
                                Category
                            </span>
                            <x-custom-icon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             class="absolute top-full left-0 right-0 mt-2 bg-green-900 rounded-lg shadow-xl border border-green-600 z-20">
                            @foreach($categories as $category)
                                <label class="flex items-center px-4 py-2 hover:bg-green-700 cursor-pointer text-white">
                                    <input type="checkbox" wire:model.live="selected_categories" value="{{ $category->id }}"
                                           class="mr-3 rounded border-green-500 text-yellow-500 focus:ring-yellow-500">
                                    <span class="text-sm">{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Brand Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-4 py-3 bg-green-800/80 hover:bg-green-700 text-white rounded-lg transition-all duration-200 border border-green-600">
                            <span class="flex items-center text-sm font-medium">
                                <x-custom-icon name="building-office" class="w-4 h-4 mr-2" />
                                Brand
                            </span>
                            <x-custom-icon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             class="absolute top-full left-0 right-0 mt-2 bg-green-900 rounded-lg shadow-xl border border-green-600 z-20">
                            @foreach($brands as $brand)
                                <label class="flex items-center px-4 py-2 hover:bg-green-700 cursor-pointer text-white">
                                    <input type="checkbox" wire:model.live="selected_brands" value="{{ $brand->id }}"
                                           class="mr-3 rounded border-green-500 text-yellow-500 focus:ring-yellow-500">
                                    <span class="text-sm">{{ $brand->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-4 py-3 bg-green-800/80 hover:bg-green-700 text-white rounded-lg transition-all duration-200 border border-green-600">
                            <span class="flex items-center text-sm font-medium">
                                <x-custom-icon name="currency-dollar" class="w-4 h-4 mr-2" />
                                Price
                            </span>
                            <x-custom-icon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             class="absolute top-full left-0 right-0 mt-2 bg-green-900 rounded-lg shadow-xl border border-green-600 z-20 p-4">
                            <div class="space-y-3">
                                <div class="flex items-center space-x-2">
                                    <label class="text-white text-sm min-w-0">Min:</label>
                                    <input type="number" wire:model.live="price_min" min="0" max="1000"
                                           class="flex-1 px-3 py-2 bg-green-800 text-white rounded-lg border border-green-600 focus:ring-2 focus:ring-yellow-500 text-sm">
                                </div>
                                <div class="flex items-center space-x-2">
                                    <label class="text-white text-sm min-w-0">Max:</label>
                                    <input type="number" wire:model.live="price_max" min="0" max="1000"
                                           class="flex-1 px-3 py-2 bg-green-800 text-white rounded-lg border border-green-600 focus:ring-2 focus:ring-yellow-500 text-sm">
                                </div>
                                <div class="text-center text-yellow-400 text-sm font-medium">
                                    ${{ $price_min }} - ${{ $price_max }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Protocol Select -->
                    <select wire:model.live="selected_protocols" 
                            class="px-4 py-3 bg-green-800/80 hover:bg-green-700 text-white rounded-lg border border-green-600 focus:ring-2 focus:ring-yellow-500 text-sm">
                        <option value="">All Protocols</option>
                        @foreach($protocols as $protocol)
                            <option value="{{ $protocol }}">{{ $protocol }}</option>
                        @endforeach
                    </select>

                    <!-- Status Filter -->
                    <select wire:model.live="server_status" 
                            class="px-4 py-3 bg-green-800/80 hover:bg-green-700 text-white rounded-lg border border-green-600 focus:ring-2 focus:ring-yellow-500 text-sm">
                        <option value="all">All Status</option>
                        <option value="online">Online Only</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>

                <!-- Sort & Results -->
                <div class="flex items-center gap-4">
                    <select wire:model.live="sortOrder"
                            class="px-4 py-3 bg-yellow-600 hover:bg-yellow-500 text-white font-medium rounded-lg focus:ring-2 focus:ring-yellow-400 text-sm">
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
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse($serverPlans as $plan)
                <div class="group bg-white/5 backdrop-blur-sm rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 border border-white/10" 
                     wire:key="plan-{{ $plan->id }}">
                    <!-- Product Image -->
                    <div class="relative aspect-video bg-gradient-to-br from-green-800 to-green-700 overflow-hidden">
                        <img src="{{ url('storage/' . $plan->product_image) }}"
                             class="w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-110"
                             alt="{{ $plan->name }}"
                             loading="lazy">
                        
                        <!-- Status Badge -->
                        @if($plan->server && $plan->server->status)
                            <div class="absolute top-3 right-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold shadow-lg
                                    {{ $plan->server->status === 'online' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                                    {{ ucfirst($plan->server->status) }}
                                </span>
                            </div>
                        @endif

                        <!-- Featured Badge -->
                        @if($plan->featured)
                            <div class="absolute top-3 left-3">
                                <span class="px-3 py-1 bg-yellow-500 text-black rounded-full text-xs font-bold shadow-lg flex items-center">
                                    <x-custom-icon name="star" class="w-3 h-3 mr-1" />
                                    Featured
                                </span>
                            </div>
                        @endif

                        <!-- Sale Badge -->
                        @if($plan->on_sale)
                            <div class="absolute bottom-3 left-3">
                                <span class="px-3 py-1 bg-red-500 text-white rounded-full text-xs font-bold shadow-lg">
                                    🔥 Sale
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Product Info -->
                    <div class="p-6">
                        <!-- Title & Category -->
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-white mb-2 group-hover:text-yellow-400 transition-colors duration-200">
                                {{ $plan->name }}
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                @if($plan->category)
                                    <span class="px-2 py-1 bg-green-600/30 text-green-200 rounded-lg text-xs">
                                        {{ $plan->category->name }}
                                    </span>
                                @endif
                                @if($plan->brand)
                                    <span class="px-2 py-1 bg-blue-600/30 text-blue-200 rounded-lg text-xs">
                                        {{ $plan->brand->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Server Details -->
                        @if($plan->server)
                            <div class="mb-4 p-3 bg-green-900/30 rounded-lg border border-green-700">
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div class="text-green-300">
                                        <span class="text-white/60">Location:</span>
                                        <div class="font-medium">{{ $plan->server->location ?? 'Global' }}</div>
                                    </div>
                                    <div class="text-green-300">
                                        <span class="text-white/60">Speed:</span>
                                        <div class="font-medium">{{ $plan->bandwidth ?? '1000' }} Mbps</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Pricing -->
                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <span class="text-3xl font-bold text-yellow-400">
                                    {{ Number::currency($plan->price) }}
                                </span>
                                <span class="text-green-200 ml-2">/month</span>
                            </div>
                            @if($plan->original_price && $plan->original_price > $plan->price)
                                <div class="flex items-center text-sm">
                                    <span class="line-through text-white/60 mr-2">
                                        {{ Number::currency($plan->original_price) }}
                                    </span>
                                    <span class="text-red-400 font-medium">
                                        Save {{ number_format((($plan->original_price - $plan->price) / $plan->original_price) * 100, 0) }}%
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-3">
                            <a href="/servers/{{ $plan->slug }}" 
                               class="block w-full bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-center text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                                View Details
                            </a>
                            <button wire:click="addToCart({{ $plan->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="addToCart({{ $plan->id }})"
                                    class="w-full bg-green-700 hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 disabled:opacity-50 flex items-center justify-center">
                                <x-custom-icon name="shopping-cart" class="w-5 h-5 mr-2" wire:loading.remove wire:target="addToCart({{ $plan->id }})" />
                                <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2" wire:loading wire:target="addToCart({{ $plan->id }})"></div>
                                <span wire:loading.remove wire:target="addToCart({{ $plan->id }})">Add to Cart</span>
                                <span wire:loading wire:target="addToCart({{ $plan->id }})">Adding...</span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="text-center py-16">
                        <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-12 border border-white/10">
                            <div class="mb-6">
                                <x-custom-icon name="magnifying-glass" class="w-16 h-16 text-white/40 mx-auto" />
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4">No products found</h3>
                            <p class="text-green-200 mb-8">Try adjusting your filters to see more results.</p>
                            <button wire:click="resetFilters"
                                    class="px-6 py-3 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded-lg transition-colors duration-200">
                                Reset All Filters
                            </button>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Enhanced Pagination -->
        @if($serverPlans->hasPages())
            <div class="mt-12 flex justify-center">
                <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                    {{ $serverPlans->links() }}
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

.animate-fade-in {
    animation: fadeIn 0.6s ease-out forwards;
}
</style>

@endsection
