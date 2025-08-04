<div class="relative bg-gradient-to-r from-gray-900 via-blue-900/20 to-gray-900 border-b border-blue-500/30 backdrop-blur-xl sticky top-0 z-50 shadow-2xl">
    <!-- Enhanced background effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-yellow-500/5"></div>
    <div class="absolute top-0 right-0 w-96 h-16 bg-gradient-to-l from-blue-400/10 to-transparent"></div>
    <div class="absolute top-0 left-0 w-96 h-16 bg-gradient-to-r from-yellow-400/10 to-transparent"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo Section -->
            <div class="flex items-center space-x-8">
                <a href="/" wire:navigate class="group flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-all duration-300 transform">
                            <span class="text-white font-bold text-lg">1K</span>
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <span class="text-white font-bold text-xl bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent group-hover:from-yellow-400 group-hover:to-blue-400 transition-all duration-300">
                        1000 Proxy
                    </span>
                </a>

                <!-- Main Navigation -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="/" wire:navigate class="group relative px-4 py-2 rounded-xl transition-all duration-300 {{ request()->is('/') ? 'bg-blue-600/20 text-blue-400' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <span class="relative z-10 font-medium">Home</span>
                        @if(request()->is('/'))
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-yellow-500/20 rounded-xl"></div>
                        @endif
                    </a>

                    <a href="/servers" wire:navigate class="group relative px-4 py-2 rounded-xl transition-all duration-300 {{ request()->is('servers*') ? 'bg-blue-600/20 text-blue-400' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <span class="relative z-10 font-medium flex items-center">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                            </svg>
                            Servers
                        </span>
                        @if(request()->is('servers*'))
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-yellow-500/20 rounded-xl"></div>
                        @endif
                    </a>

                    <a href="/categories" wire:navigate class="group relative px-4 py-2 rounded-xl transition-all duration-300 {{ request()->is('categories*') ? 'bg-yellow-600/20 text-yellow-400' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <span class="relative z-10 font-medium flex items-center">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Categories
                        </span>
                        @if(request()->is('categories*'))
                            <div class="absolute inset-0 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 rounded-xl"></div>
                        @endif
                    </a>

                    <a href="/servers" wire:navigate class="group relative px-4 py-2 rounded-xl transition-all duration-300 {{ request()->is('brands*') ? 'bg-green-600/20 text-green-400' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <span class="relative z-10 font-medium flex items-center">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            Brands
                        </span>
                        @if(request()->is('brands*'))
                            <div class="absolute inset-0 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-xl"></div>
                        @endif
                    </a>
                </nav>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-3">
                @auth('customer')
                    <!-- Wallet Balance -->
                    <div class="hidden sm:flex items-center space-x-2 bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 rounded-xl px-4 py-2.5 hover:border-blue-400/50 transition-all duration-300 group">
                        <div class="w-6 h-6 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-4 h-4 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17,6H22V8H17V6M12,6V8H8A2,2 0 0,0 6,10V14A2,2 0 0,0 8,16H16A2,2 0 0,0 18,14V10A2,2 0 0,0 16,8H14V6H12M8,10H16V14H8V10Z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-white group-hover:text-yellow-400 transition-colors duration-300">
                            ${{ number_format(auth('customer')->user()->wallet_balance ?? 0, 2) }}
                        </span>
                    </div>

                    <!-- Enhanced Cart Icon -->
                    <div class="relative">
                        <a href="/cart" wire:navigate class="group relative bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-blue-400/50 rounded-xl p-3 transition-all duration-300 hover:scale-105 transform {{ request()->is('cart*') ? 'ring-2 ring-blue-400/50' : '' }}">
                            <svg class="w-6 h-6 text-white group-hover:text-blue-400 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5H21M7 13v6a2 2 0 002 2h6a2 2 0 002-2v-6m-8 0V9a2 2 0 012-2h4a2 2 0 012 2v4.01"/>
                            </svg>
                            
                            @if($total_count > 0)
                                <span class="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-gray-900 text-xs font-bold rounded-full h-7 w-7 flex items-center justify-center animate-bounce shadow-lg ring-2 ring-yellow-400/30" data-cart-count>
                                    {{ $total_count }}
                                </span>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-400 rounded-full animate-ping"></div>
                            @endif
                        </a>
                    </div>

                    <!-- Enhanced User Menu -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" 
                                class="flex items-center space-x-3 bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-blue-400/50 rounded-xl px-4 py-2.5 transition-all duration-300 hover:scale-105 transform">
                            <div class="relative">
                                <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-sm">
                                        {{ strtoupper(substr(auth('customer')->user()->name ?? 'U', 0, 2)) }}
                                    </span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full ring-2 ring-gray-900"></div>
                            </div>
                            <div class="hidden sm:block">
                                <span class="text-sm font-semibold text-white">{{ auth('customer')->user()->name ?? 'User' }}</span>
                                <div class="text-xs text-gray-400">{{ auth('customer')->user()->email ?? 'user@example.com' }}</div>
                            </div>
                            <svg class="w-4 h-4 text-white transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>                        <!-- Enhanced Dropdown Menu -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200" 
                             x-transition:enter-start="transform opacity-0 scale-95 translate-y-2" 
                             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0" 
                             x-transition:leave="transition ease-in duration-150" 
                             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0" 
                             x-transition:leave-end="transform opacity-0 scale-95 translate-y-2" 
                             class="absolute right-0 mt-3 w-64 bg-gradient-to-br from-gray-800/95 to-gray-900/95 backdrop-blur-xl border border-blue-500/30 rounded-2xl shadow-2xl z-50 overflow-hidden">
                            
                            <!-- Header -->
                            <div class="px-4 py-3 border-b border-gray-700/50 bg-gradient-to-r from-blue-600/10 to-yellow-600/10">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center">
                                        <span class="text-white font-bold">{{ substr(auth('customer')->user()->name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-white">{{ auth('customer')->user()->name }}</div>
                                        <div class="text-xs text-gray-400">{{ auth('customer')->user()->email }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2">
                                <!-- Remove admin panel for customers -->

                                <a href="/my-orders" class="group flex items-center px-4 py-3 text-sm text-white hover:bg-gradient-to-r hover:from-blue-600/20 hover:to-blue-700/20 transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </div>
                                    <span class="group-hover:text-blue-400 transition-colors">My Orders</span>
                                </a>

                                <a href="/transactions" class="group flex items-center px-4 py-3 text-sm text-white hover:bg-gradient-to-r hover:from-yellow-600/20 hover:to-yellow-700/20 transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <span class="group-hover:text-yellow-400 transition-colors">Transactions</span>
                                </a>

                                <div class="border-t border-gray-700/50 my-2"></div>

                                <a href="/logout" class="group flex items-center w-full px-4 py-3 text-sm text-white hover:bg-gradient-to-r hover:from-red-600/20 hover:to-red-700/20 transition-all duration-200 text-left">
                                    <div class="w-8 h-8 bg-gradient-to-br from-red-500/20 to-red-600/20 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                    </div>
                                    <span class="group-hover:text-red-400 transition-colors">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Enhanced Guest Buttons -->
                    <div class="flex items-center space-x-3">
                        <a href="/login" wire:navigate class="group relative px-6 py-2.5 text-white hover:text-blue-400 hover:bg-white/5 rounded-xl text-sm font-medium transition-all duration-300 border border-transparent hover:border-blue-500/30">
                            <span class="relative z-10">Login</span>
                        </a>
                        <a href="/register" wire:navigate class="group relative bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <span class="relative z-10">Register</span>
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-yellow-600 rounded-xl blur opacity-30 group-hover:opacity-50 transition-opacity duration-300"></div>
                        </a>
                    </div>
                @endauth

                <!-- Enhanced Mobile Menu Button -->
                <div class="md:hidden" x-data="{ mobileOpen: false }">
                    <button @click="mobileOpen = !mobileOpen" 
                            class="text-white hover:text-blue-400 hover:bg-white/5 p-2.5 rounded-xl transition-all duration-300 border border-blue-500/30 hover:border-blue-400/50">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                
                    <!-- Enhanced Mobile Menu -->
                    <div x-show="mobileOpen" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="transform opacity-0 scale-95" 
                         x-transition:enter-end="transform opacity-100 scale-100" 
                         x-transition:leave="transition ease-in duration-150" 
                         x-transition:leave-start="transform opacity-100 scale-100" 
                         x-transition:leave-end="transform opacity-0 scale-95" 
                         class="absolute top-16 left-0 right-0 z-50">
                        <div class="mx-4 bg-gradient-to-br from-gray-800/95 to-gray-900/95 backdrop-blur-xl border border-blue-500/30 rounded-2xl shadow-2xl overflow-hidden">
                            <div class="px-4 py-3 space-y-2">
                                <a href="/" wire:navigate class="flex items-center text-white hover:text-blue-400 px-3 py-2.5 rounded-xl text-base font-medium transition-all duration-200 {{ request()->is('/') ? 'bg-blue-600/20 text-blue-400' : 'hover:bg-white/5' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    Home
                                </a>
                                
                                <a href="/servers" wire:navigate class="flex items-center text-white hover:text-blue-400 px-3 py-2.5 rounded-xl text-base font-medium transition-all duration-200 {{ request()->is('servers*') ? 'bg-blue-600/20 text-blue-400' : 'hover:bg-white/5' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                                    </svg>
                                    Servers
                                </a>

                                <a href="/categories" wire:navigate class="flex items-center text-white hover:text-yellow-400 px-3 py-2.5 rounded-xl text-base font-medium transition-all duration-200 {{ request()->is('categories*') ? 'bg-yellow-600/20 text-yellow-400' : 'hover:bg-white/5' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    Categories
                                </a>

                                <a href="/servers" wire:navigate class="flex items-center text-white hover:text-green-400 px-3 py-2.5 rounded-xl text-base font-medium transition-all duration-200 {{ request()->is('brands*') ? 'bg-green-600/20 text-green-400' : 'hover:bg-white/5' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    Brands
                                </a>

                                @auth
                                    <div class="border-t border-gray-700/50 my-3"></div>
                                    
                                    <a href="/cart" wire:navigate class="flex items-center justify-between text-white hover:text-blue-400 px-3 py-2.5 rounded-xl text-base font-medium transition-all duration-200 {{ request()->is('cart*') ? 'bg-blue-600/20 text-blue-400' : 'hover:bg-white/5' }}">
                                        <div class="flex items-center">
                                            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5H21M7 13v6a2 2 0 002 2h6a2 2 0 002-2v-6m-8 0V9a2 2 0 012-2h4a2 2 0 012 2v4.01"/>
                                            </svg>
                                            Cart
                                        </div>
                                        @if($total_count > 0)
                                            <span class="bg-gradient-to-r from-yellow-500 to-orange-500 text-gray-900 text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center" data-cart-count>
                                                {{ $total_count }}
                                            </span>
                                        @endif
                                    </a>
                                @else
                                    <div class="border-t border-gray-700/50 my-3"></div>
                                    
                                    <a href="/login" wire:navigate class="flex items-center text-white hover:text-blue-400 px-3 py-2.5 rounded-xl text-base font-medium transition-all duration-200 hover:bg-white/5">
                                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                        </svg>
                                        Login
                                    </a>
                                    
                                    <a href="/register" wire:navigate class="flex items-center bg-gradient-to-r from-blue-600 to-yellow-600 text-white px-3 py-2.5 rounded-xl text-base font-semibold transition-all duration-200 hover:from-blue-700 hover:to-yellow-700">
                                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                        Register
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
