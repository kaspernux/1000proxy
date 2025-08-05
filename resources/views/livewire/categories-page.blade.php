<main class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
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

    <!-- Enhanced Hero Section -->
    <div class="max-w-7xl mx-auto mb-12 relative z-10">
        <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-8 md:p-12 border border-white/10 shadow-2xl overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 30px 30px;"></div>
            </div>
            
            <!-- Enhanced Floating Gradient Orbs -->
            <div class="absolute top-0 left-0 w-32 h-32 bg-gradient-to-br from-yellow-400/30 to-blue-400/30 rounded-full blur-2xl animate-pulse duration-[4000ms]"></div>
            <div class="absolute bottom-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-400/30 to-yellow-400/30 rounded-full blur-2xl animate-bounce duration-[5000ms]"></div>
            
            <div class="relative z-10 text-center">
                <!-- Enhanced Breadcrumb -->
                <div class="mb-6">
                    <nav class="flex justify-center items-center space-x-2 text-sm">
                        <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="text-blue-400 font-medium">Proxy Categories</span>
                    </nav>
                </div>

                <!-- Enhanced Main Title -->
                <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-6 leading-tight">
                    <span class="block mb-2">Proxy</span>
                    <span class="bg-gradient-to-r from-blue-400 via-yellow-400 to-blue-500 bg-clip-text text-transparent">
                        Categories
                    </span>
                </h1>
                
                <!-- Enhanced Description -->
                <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed mb-8 font-light">
                    Discover our specialized proxy categories, each tailored for specific use cases and optimized for 
                    <span class="text-blue-400 font-semibold">maximum performance</span> and 
                    <span class="text-yellow-400 font-semibold">reliability</span>.
                </p>

                <!-- Enhanced Stats Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                    <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10">
                        <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors duration-300">{{ $categories->count() }}</div>
                        <div class="text-gray-400 font-medium">Categories</div>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                        <div class="text-2xl font-bold text-yellow-400">{{ $categories->sum(function($cat) { return $cat->plans ? $cat->plans->count() : 0; }) }}</div>
                        <div class="text-sm text-green-200">Total Plans</div>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                        <div class="text-2xl font-bold text-yellow-400">{{ $categories->sum(function($cat) { return $cat->servers ? $cat->servers->count() : 0; }) }}</div>
                        <div class="text-sm text-green-200">Active Servers</div>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm border border-white/20">
                        <div class="text-2xl font-bold text-yellow-400">99.9%</div>
                        <div class="text-sm text-green-200">Uptime</div>
                    </div>
                </div>
            </div>
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

        <!-- Enhanced Features & Benefits Section -->
        <div class="mt-16 relative">
            <!-- Section Header -->
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Why Choose Our <span class="text-yellow-400">Proxy Network</span>?
                </h2>
                <p class="text-lg text-green-200 max-w-2xl mx-auto">
                    Experience the difference with our premium infrastructure and dedicated support.
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Performance Feature -->
                <div class="group relative bg-white/5 backdrop-blur-md rounded-2xl p-8 border border-white/10 hover:bg-white/10 transition-all duration-300 hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-600/10 to-green-800/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10">
                        <!-- Icon -->
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <!-- Content -->
                        <h4 class="text-xl font-bold text-white mb-3 group-hover:text-green-300 transition-colors">Lightning Fast</h4>
                        <p class="text-green-200 text-sm leading-relaxed group-hover:text-white transition-colors">
                            Experience blazing-fast speeds with our optimized global network infrastructure and premium bandwidth allocation.
                        </p>
                        <!-- Stats -->
                        <div class="mt-4 pt-4 border-t border-white/10">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-green-400 font-medium">Avg Speed:</span>
                                <span class="text-yellow-400 font-bold">1000+ Mbps</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Feature -->
                <div class="group relative bg-white/5 backdrop-blur-md rounded-2xl p-8 border border-white/10 hover:bg-white/10 transition-all duration-300 hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-yellow-600/10 to-yellow-800/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10">
                        <!-- Icon -->
                        <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <!-- Content -->
                        <h4 class="text-xl font-bold text-white mb-3 group-hover:text-yellow-300 transition-colors">Military-Grade Security</h4>
                        <p class="text-green-200 text-sm leading-relaxed group-hover:text-white transition-colors">
                            Your privacy is our priority. Advanced encryption protocols and strict no-logs policy ensure complete anonymity.
                        </p>
                        <!-- Stats -->
                        <div class="mt-4 pt-4 border-t border-white/10">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-green-400 font-medium">Encryption:</span>
                                <span class="text-yellow-400 font-bold">AES-256</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Feature -->
                <div class="group relative bg-white/5 backdrop-blur-md rounded-2xl p-8 border border-white/10 hover:bg-white/10 transition-all duration-300 hover:scale-105 md:col-span-2 lg:col-span-1">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/10 to-blue-800/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10">
                        <!-- Icon -->
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <!-- Content -->
                        <h4 class="text-xl font-bold text-white mb-3 group-hover:text-blue-300 transition-colors">24/7 Expert Support</h4>
                        <p class="text-green-200 text-sm leading-relaxed group-hover:text-white transition-colors">
                            Round-the-clock technical support from our experienced team. Get help whenever you need it, wherever you are.
                        </p>
                        <!-- Stats -->
                        <div class="mt-4 pt-4 border-t border-white/10">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-green-400 font-medium">Response Time:</span>
                                <span class="text-yellow-400 font-bold">&lt; 15 mins</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action Section -->
            <div class="mt-12 text-center">
                <div class="bg-gradient-to-r from-yellow-600/20 to-green-600/20 backdrop-blur-sm rounded-2xl p-8 border border-yellow-400/30">
                    <h3 class="text-2xl font-bold text-white mb-4">Ready to Get Started?</h3>
                    <p class="text-green-200 mb-6 max-w-md mx-auto">
                        Browse our categories and find the perfect proxy solution for your needs.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/servers" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-white font-bold rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Browse All Proxies
                        </a>
                        <a href="/contact" class="inline-flex items-center px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-lg transition-all duration-200 border border-white/20 hover:border-white/40">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            Contact Sales
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Custom Styles -->
    <style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        33% { transform: translateY(-10px) rotate(1deg); }
        66% { transform: translateY(-5px) rotate(-1deg); }
    }

    @keyframes float-reverse {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        33% { transform: translateY(10px) rotate(-1deg); }
        66% { transform: translateY(5px) rotate(1deg); }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-float-reverse {
        animation: float-reverse 8s ease-in-out infinite;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 0.4; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.2); }
    }

    .animate-pulse-dot {
        animation: pulse-dot 1.5s ease-in-out infinite;
    }

    @keyframes glow {
        0%, 100% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.1); }
        50% { box-shadow: 0 0 30px rgba(255, 255, 255, 0.2), 0 0 40px rgba(34, 197, 94, 0.1); }
    }

    .animate-glow {
        animation: glow 3s ease-in-out infinite;
    }

    /* Smooth transitions for all interactive elements */
    * {
        transition-property: transform, background-color, border-color, opacity, box-shadow;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 300ms;
    }

    /* Custom gradient backgrounds */
    .bg-gradient-mesh {
        background: radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 40% 40%, rgba(34, 197, 94, 0.1) 0%, transparent 50%);
    }
    </style>
</main>
