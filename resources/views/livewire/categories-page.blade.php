@extends('layouts.app')

@section('content')
<main class="min-h-screen bg-gradient-to-br from-green-900 via-green-800 to-green-600 py-12 px-4 sm:px-6 lg:px-8">
    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto text-center mb-16">
        <div class="relative">
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6">
                <span class="bg-gradient-to-r from-yellow-400 to-yellow-200 bg-clip-text text-transparent">
                    Proxy Categories
                </span>
            </h1>
            <p class="text-xl md:text-2xl text-green-100 max-w-3xl mx-auto leading-relaxed">
                Choose from our carefully curated selection of premium proxy services, 
                each category designed for specific use cases and performance requirements.
            </p>
            
            <!-- Floating Elements -->
            <div class="absolute -top-4 -left-4 w-24 h-24 bg-yellow-400/20 rounded-full blur-xl animate-pulse"></div>
            <div class="absolute -bottom-4 -right-4 w-32 h-32 bg-green-400/20 rounded-full blur-xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>
    </div>

    <!-- Categories Grid -->
    <section class="max-w-7xl mx-auto">
        <!-- Loading State -->
        <div wire:loading.delay class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @for($i = 0; $i < 8; $i++)
            <div class="bg-white/5 backdrop-blur-md rounded-2xl shadow-xl border border-white/10 p-6 animate-pulse">
                <div class="flex items-start space-x-4 mb-4">
                    <div class="w-16 h-16 bg-white/10 rounded-xl"></div>
                    <div class="flex-1">
                        <div class="h-4 bg-white/10 rounded mb-2"></div>
                        <div class="h-3 bg-white/10 rounded w-3/4"></div>
                    </div>
                </div>
                <div class="h-3 bg-white/10 rounded w-full mb-2"></div>
                <div class="h-3 bg-white/10 rounded w-2/3"></div>
            </div>
            @endfor
        </div>

        <!-- Categories Content -->
        <div wire:loading.remove class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($categories as $serverCategory)
            <a href="/servers?selected_categories[0]={{ $serverCategory->id }}" 
               wire:key="{{ $serverCategory->id }}"
               class="group relative overflow-hidden bg-white/5 backdrop-blur-md rounded-2xl shadow-xl hover:shadow-2xl border border-white/10 transition-all duration-300 hover:scale-105 hover:bg-white/10">
                
                <!-- Background Gradient -->
                <div class="absolute inset-0 bg-gradient-to-br from-green-600/20 to-green-800/20 group-hover:from-yellow-600/20 group-hover:to-yellow-800/20 transition-all duration-300"></div>
                
                <!-- Content -->
                <div class="relative p-6">
                    <!-- Category Image & Info Section -->
                    <div class="flex items-start space-x-4 mb-4">
                        <!-- Category Icon/Image -->
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <img class="w-16 h-16 object-cover rounded-xl shadow-lg border-2 border-yellow-400/50 group-hover:border-yellow-400 transition-all duration-300 group-hover:scale-110" 
                                     src="{{ url('storage/'.$serverCategory->image) }}" 
                                     alt="{{ $serverCategory->name }}"
                                     loading="lazy">
                                
                                <!-- Glow Effect -->
                                <div class="absolute inset-0 w-16 h-16 rounded-xl bg-gradient-to-br from-yellow-400/20 to-green-400/20 blur-lg group-hover:blur-xl transition-all duration-300"></div>
                            </div>
                        </div>

                        <!-- Category Details -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-white group-hover:text-yellow-400 transition-colors duration-300 mb-2 line-clamp-2">
                                {{ $serverCategory->name }}
                            </h3>
                            
                            <!-- Plans Count -->
                            <div class="flex items-center text-sm text-green-200 group-hover:text-white transition-colors duration-300">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <span>{{ $serverCategory->plans->count() }} plans available</span>
                            </div>
                            
                            <!-- Server Count -->
                            <div class="flex items-center text-xs text-green-300/80 group-hover:text-white/80 transition-colors duration-300 mt-1">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                </svg>
                                <span>{{ $serverCategory->servers->count() }} servers</span>
                            </div>
                        </div>

                        <!-- Arrow Icon -->
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-yellow-400 group-hover:text-white group-hover:translate-x-1 transition-all duration-300" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Category Description (if available) -->
                    @if($serverCategory->description)
                        <div class="text-xs text-green-200 group-hover:text-white/90 transition-colors duration-300 line-clamp-2 leading-relaxed">
                            {{ Str::limit($serverCategory->description, 80) }}
                        </div>
                    @endif

                    <!-- Bottom Action -->
                    <div class="mt-4 pt-3 border-t border-white/10 group-hover:border-white/20 transition-colors duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-yellow-400 group-hover:text-white transition-colors duration-300">
                                Explore Category
                            </span>
                            <div class="flex space-x-1">
                                <div class="w-1 h-1 bg-yellow-400 rounded-full group-hover:bg-white transition-colors duration-300 animate-pulse-dot"></div>
                                <div class="w-1 h-1 bg-yellow-400 rounded-full group-hover:bg-white transition-colors duration-300 animate-pulse-dot" style="animation-delay: 0.1s;"></div>
                                <div class="w-1 h-1 bg-yellow-400 rounded-full group-hover:bg-white transition-colors duration-300 animate-pulse-dot" style="animation-delay: 0.2s;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hover Effects -->
                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <div class="absolute top-3 right-3 w-1.5 h-1.5 bg-yellow-400 rounded-full animate-ping"></div>
                    <div class="absolute bottom-3 left-3 w-1 h-1 bg-green-400 rounded-full animate-ping" style="animation-delay: 0.5s;"></div>
                </div>
            </a>
            @empty
            <!-- Empty State -->
            <div class="col-span-full">
                <div class="text-center py-16">
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-12 border border-white/10">
                        <div class="mb-6">
                            <svg class="w-16 h-16 text-white/40 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-4">No Categories Available</h3>
                        <p class="text-green-200 mb-8">We're currently updating our category listings. Please check back soon!</p>
                        <a href="/servers" class="inline-flex items-center px-6 py-3 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                            </svg>
                            Browse All Servers
                        </a>
                    </div>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Additional Features Section -->
        <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-8 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10">
                <div class="w-16 h-16 bg-green-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-custom-icon name="bolt" class="w-8 h-8 text-green-400" />
                </div>
                <h4 class="text-xl font-bold text-white mb-2">Lightning Fast</h4>
                <p class="text-green-200">Experience blazing-fast connection speeds with our optimized proxy network.</p>
            </div>
            
            <div class="text-center p-8 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10">
                <div class="w-16 h-16 bg-yellow-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-custom-icon name="shield-check" class="w-8 h-8 text-yellow-400" />
                </div>
                <h4 class="text-xl font-bold text-white mb-2">Secure & Private</h4>
                <p class="text-green-200">Your data is protected with enterprise-grade security and encryption.</p>
            </div>
            
            <div class="text-center p-8 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10">
                <div class="w-16 h-16 bg-blue-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-custom-icon name="clock" class="w-8 h-8 text-blue-400" />
                </div>
                <h4 class="text-xl font-bold text-white mb-2">24/7 Support</h4>
                <p class="text-green-200">Round-the-clock technical support to ensure uninterrupted service.</p>
            </div>
        </div>
    </section>
</main>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 1; }
}

.animate-pulse-dot {
    animation: pulse-dot 1.5s ease-in-out infinite;
}
</style>

@endsection
