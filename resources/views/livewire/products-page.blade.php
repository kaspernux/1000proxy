@extends('layouts.app')

@extends('layouts.app')

@section('content')
@php
use Illuminate\Support\Str;
@endphp

<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 py-6 px-4 sm:px-6 lg:px-8"
     x-data="{ showFilters: false }">

    <!-- Loading Overlay (Livewire) -->
    <div wire:loading.delay class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-green-900 border-2 border-yellow-600 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-600"></div>
            <span class="text-white font-bold">Loading products...</span>
        </div>
    </div>

    <section class="py-6 lg:py-10 rounded-lg">
        <div class="mx-auto max-w-7xl">

        <!-- Mobile Filter Toggle Button -->
        <div class="lg:hidden mb-6">
            <button @click="showFilters = !showFilters"
                    class="btn-filter-toggle">
                <span class="font-bold text-lg flex items-center">
                    <x-custom-icon name="funnel" class="h-5 w-5 mr-2" />
                    Filters & Search
                </span>
                <span x-text="showFilters ? '▲' : '▼'" class="text-yellow-600 transition-transform duration-200"></span>
            </button>
        </div>

        <div class="flex flex-col lg:flex-row gap-6 lg:gap-10">
            <!-- Filters - Mobile Collapsible, Desktop Always Visible -->
            <div class="w-full lg:w-1/4 space-y-4 lg:space-y-6"
                 :class="{'hidden': !showFilters && window.innerWidth < 1024}"
                 x-show="showFilters || window.innerWidth >= 1024"
                 x-transition:enter="transition-all duration-300 ease-in-out"
                 x-transition:enter-start="-translate-y-4 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100">
                <!-- Location Filter (Priority First) -->
                <div class="filter-section">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        <x-custom-icon name="globe-alt" class="h-6 w-6 mr-2" />
                        <span>Location</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <ul class="max-h-32 lg:max-h-40 overflow-y-auto space-y-1 lg:space-y-2 custom-scrollbar">
                        @foreach($countries as $country)
                            <li wire:key="country-{{ $country }}" class="mb-2 lg:mb-3">
                                <label class="flex items-center space-x-2 text-white cursor-pointer hover:text-yellow-200 transition-colors duration-200">
                                    <input type="checkbox" wire:model.debounce.500ms="selected_countries" value="{{ $country }}"
                                        class="filter-input focus-visible-custom">
                                    <span class="text-sm lg:text-lg flex items-center">
                                        <x-custom-icon name="flag" class="h-4 w-4 mr-2" />
                                        {{ strtoupper($country) }}
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Category Filter -->
                <div class="filter-section">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        <x-custom-icon name="folder" class="h-6 w-6 mr-2" />
                        <span>Category</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <ul class="space-y-1 lg:space-y-2">
                        @foreach($categories as $category)
                            <li wire:key="category-{{ $category->id }}" class="mb-2 lg:mb-3">
                                <label class="flex items-center space-x-2 text-white cursor-pointer hover:text-yellow-200 transition-colors duration-200">
                                    <input type="checkbox" wire:model.debounce.500ms="selected_categories" value="{{ $category->id }}"
                                        class="filter-input focus-visible-custom">
                                    <span class="text-sm lg:text-lg uppercase">{{ $category->name }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Brand Filter -->
                <div class="filter-section">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        <x-custom-icon name="building-office" class="h-6 w-6 mr-2" />
                        <span>Brand</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <ul class="space-y-1 lg:space-y-2">
                        @foreach($brands as $brand)
                            <li wire:key="brand-{{ $brand->id }}" class="mb-2 lg:mb-3">
                                <label class="flex items-center space-x-2 text-white cursor-pointer hover:text-yellow-200 transition-colors duration-200">
                                    <input type="checkbox" wire:model.debounce.500ms="selected_brands" value="{{ $brand->id }}"
                                        class="filter-input focus-visible-custom">
                                    <span class="text-sm lg:text-lg uppercase">{{ $brand->name }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Protocol Filter -->
                <div class="p-3 lg:p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        <x-custom-icon name="shield-check" class="w-4 h-4" /> <span class="ml-2">Protocol</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <ul class="space-y-1 lg:space-y-2">
                        @foreach($protocols as $protocol)
                            <li wire:key="protocol-{{ $protocol }}" class="mb-2 lg:mb-3">
                                <label class="flex items-center space-x-2 text-white cursor-pointer">
                                    <input type="checkbox" wire:model.debounce.500ms="selected_protocols" value="{{ $protocol }}"
                                        class="w-4 h-4 border-yellow-600 rounded-lg border-2 shrink-0">
                                    <span class="text-sm lg:text-lg">{{ $protocol }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- IP Version Filter -->
                <div class="filter-section">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        🌐 <span class="ml-2">IP Version</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <select wire:model.debounce.500ms="ip_version" class="filter-select">
                        <option value="">All IP Versions</option>
                        <option value="ipv4">IPv4 Only</option>
                        <option value="ipv6">IPv6 Only</option>
                        <option value="both">IPv4 + IPv6</option>
                    </select>
                </div>

                <!-- Server Status Filter -->
                <div class="filter-section">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        📡 <span class="ml-2">Server Status</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <select wire:model.debounce.500ms="server_status" class="filter-select">
                        <option value="all">All Servers</option>
                        <option value="online">Online Only</option>
                        <option value="offline">Offline Only</option>
                    </select>
                </div>

                <!-- Price Range Filter -->
                <div class="p-3 lg:p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        <x-custom-icon name="credit-card" class="w-4 h-4" /> <span class="ml-2">Price Range</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <div class="space-y-2 lg:space-y-3">
                        <div class="flex items-center space-x-2">
                            <label class="text-white text-xs lg:text-sm shrink-0">Min:</label>
                            <input type="number" wire:model.debounce.500ms="price_min" min="0" max="1000"
                                   class="flex-1 px-2 py-1 bg-yellow-600 text-white rounded text-sm">
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-white text-xs lg:text-sm shrink-0">Max:</label>
                            <input type="number" wire:model.debounce.500ms="price_max" min="0" max="1000"
                                   class="flex-1 px-2 py-1 bg-yellow-600 text-white rounded text-sm">
                        </div>
                        <div class="text-white text-xs lg:text-sm text-center bg-green-800 py-1 rounded">
                            ${{ $price_min }} - ${{ $price_max }}
                        </div>
                    </div>
                </div>

                <!-- Bandwidth Filter -->
                <div class="p-3 lg:p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        <x-custom-icon name="bolt" class="w-4 h-4" /> <span class="ml-2">Bandwidth</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <div class="space-y-2 lg:space-y-3">
                        <div class="flex items-center space-x-2">
                            <label class="text-white text-xs lg:text-sm shrink-0">Min:</label>
                            <input type="number" wire:model.debounce.500ms="bandwidth_min" min="0" max="1000"
                                   class="flex-1 px-2 py-1 bg-yellow-600 text-white rounded text-sm">
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-white text-xs lg:text-sm shrink-0">Max:</label>
                            <input type="number" wire:model.debounce.500ms="bandwidth_max" min="0" max="1000"
                                   class="flex-1 px-2 py-1 bg-yellow-600 text-white rounded text-sm">
                        </div>
                        <div class="text-white text-xs lg:text-sm text-center bg-green-800 py-1 rounded">
                            {{ $bandwidth_min }} - {{ $bandwidth_max }} Mbps
                        </div>
                    </div>
                </div>

                <!-- Product Status -->
                <div class="p-3 lg:p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                    <h2 class="text-lg lg:text-2xl font-bold text-white flex items-center">
                        <x-custom-icon name="star" class="h-6 w-6 mr-2" />
                        <span>Product Status</span>
                    </h2>
                    <div class="w-12 lg:w-16 border-b border-yellow-600 mb-3 lg:mb-4"></div>
                    <ul class="space-y-1 lg:space-y-2">
                        <li class="mb-2 lg:mb-3">
                            <label class="flex items-center space-x-2 text-white cursor-pointer">
                                <input type="checkbox" wire:model.debounce.500ms="featured"
                                    class="w-4 h-4 border-yellow-600 rounded-lg border-2 shrink-0">
                                <span class="text-sm lg:text-lg flex items-center">
                                    <x-custom-icon name="star" class="h-4 w-4 mr-2" />
                                    Featured
                                </span>
                            </label>
                        </li>
                        <li class="mb-2 lg:mb-3">
                            <label class="flex items-center space-x-2 text-white cursor-pointer">
                                <input type="checkbox" wire:model.debounce.500ms="on_sale"
                                    class="w-4 h-4 border-yellow-600 rounded-lg border-2 shrink-0">
                                <span class="text-sm lg:text-lg flex items-center">
                                    <x-custom-icon name="credit-card" class="h-4 w-4 mr-2" />
                                    On Sale
                                </span>
                            </label>
                        </li>
                    </ul>
                </div>

                <!-- Mobile Apply Filters Button (optional, triggers refresh) -->
                <div class="p-4 mb-5">
                    <button @click="$wire.$refresh()"
                        class="inline-flex justify-center w-full gap-2 py-2 border-2 border-double border-yellow-600 text-lg font-bold text-white bg-green-900 rounded-md shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-600 dark:bg-green-900 dark:hover:bg-yellow-600 dark:focus:ring-offset-green-900 dark:text-green-900">
                        🔍 Apply Filters
                    </button>
                </div>
            </div>

            <!-- Products -->
            <div class="w-full lg:w-3/4">
                <!-- Advanced Sorting -->
                <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <div class="text-white text-base lg:text-lg font-bold">
                        Found {{ $serverPlans->total() }} proxy plans
                    </div>
                    <select wire:model.debounce.500ms="sortOrder"
                        class="w-full sm:w-60 px-3 py-2 bg-yellow-600 text-white font-bold rounded-lg focus:outline-none focus:ring text-sm lg:text-base">
                        <option value="location_first">
                            <x-custom-icon name="globe-alt" class="h-4 w-4 mr-2 inline" />
                            Location First
                        </option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="speed">Speed: Fastest First</option>
                        <option value="popularity">Most Popular</option>
                        <option value="latest">Latest First</option>
                    </select>
                </div>

                <!-- Products Grid - Responsive -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
                    @forelse($serverPlans as $plan)
                        <div class="proxy-card animate-fade-in" wire:key="plan-{{ $plan->id }}" style="animation-delay: {{ $loop->index * 0.1 }}s">
                            <a href="/servers/{{ $plan->slug }}" class="bg-green-900 p-3 lg:p-4 h-40 lg:h-60 flex items-center justify-center group">
                                <img src="{{ url('storage/' . $plan->product_image) }}"
                                     class="object-contain h-full w-full transition-transform duration-300 group-hover:scale-105"
                                     alt="{{ $plan->name }}"
                                     loading="lazy">
                            </a>
                            <div class="p-3 lg:p-4 bg-white">
                                <h3 class="text-base lg:text-lg font-bold text-green-900 truncate hover:text-yellow-600 transition-colors duration-200">{{ $plan->name }}</h3>
                                <p class="text-lg lg:text-xl text-yellow-600 font-bold">{{ Number::currency($plan->price) }}</p>

                                <!-- Additional Info Pills -->
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @if($plan->featured)
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full flex items-center gap-1"><x-custom-icon name="star" class="w-3 h-3" /> Featured</span>
                                    @endif
                                    @if($plan->on_sale)
                                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full flex items-center gap-1"><x-custom-icon name="star" class="w-3 h-3" /> Sale</span>
                                    @endif
                                </div>
                            </div>
                            <div class="bg-green-900 p-2 lg:p-3 text-center">
                                <button wire:click.prevent="addToCart({{ $plan->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart({{ $plan->id }})"
                                        class="btn-primary w-full touch-target mobile-optimized text-sm lg:text-base">
                                    <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="currentColor" viewBox="0 0 16 16"
                                         wire:loading.remove wire:target="addToCart({{ $plan->id }})">
                                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2l.89 2H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401L4.415 9.07 4 11h9a.5.5 0 0 1 0 1H3.5a.5.5 0 0 1-.491-.408L1.01 3.607 0.5 2H.5a.5.5 0 0 1-.5-.5zM5 13a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 1a1 1 0 1 0-2 0 1 1 0 0 0 2 0z"/>
                                    </svg>
                                    <div class="loading-spinner h-4 w-4 border-white"
                                         wire:loading wire:target="addToCart({{ $plan->id }})"></div>
                                    <span wire:loading.remove wire:target="addToCart({{ $plan->id }})">Add to Cart</span>
                                    <span wire:loading wire:target="addToCart({{ $plan->id }})">Adding...</span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center text-white text-base lg:text-lg py-8 animate-fade-in">
                            <div class="bg-green-800 rounded-lg p-6 border-2 border-yellow-600">
                                <p class="mb-2 text-xl flex items-center gap-2"><x-custom-icon name="magnifying-glass" class="w-5 h-5" /> No products found.</p>
                                <p class="text-sm opacity-75">Try adjusting your filters to see more results.</p>
                                <button @click="$wire.call('resetFilters')"
                                        class="mt-4 px-4 py-2 bg-yellow-600 text-green-900 rounded-lg font-bold hover:bg-yellow-500 transition-colors duration-200">
                                    Reset Filters
                                </button>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    <div class="flex justify-center">
                        {{ $serverPlans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection