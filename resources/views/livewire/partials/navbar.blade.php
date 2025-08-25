<style>
    /* Force dark text on mobile devices, overriding system dark mode for navigation */
    @media (max-width: 768px) {
        .mobile-force-dark-text {
            color: #111827 !important; /* text-gray-900 equivalent */
        }
        .mobile-force-dark-text * {
            color: #111827 !important;
        }
        .mobile-force-icon-dark {
            color: #374151 !important; /* text-gray-700 equivalent */
        }
        .mobile-brand-text {
            color: #111827 !important;
        }
    }
</style>

<div class="relative bg-gradient-to-r from-gray-900 via-blue-900/20 to-gray-900 border-b border-blue-500/30 sticky top-0 z-50 shadow-2xl">
    <!-- Enhanced background effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-yellow-500/5"></div>
    <div class="absolute top-0 right-0 w-96 h-16 bg-gradient-to-l from-blue-400/10 to-transparent"></div>
    <div class="absolute top-0 left-0 w-96 h-16 bg-gradient-to-r from-yellow-400/10 to-transparent"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 w-full">
            <!-- Logo Section -->
            <div class="flex items-center space-x-2 sm:space-x-8 flex-shrink-0">
                <a href="/" wire:navigate class="group flex items-center space-x-2 sm:space-x-3">
                    <div class="relative">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-all duration-300 transform">
                            <span class="text-white font-bold text-sm sm:text-lg">1K</span>
                        </div>
                        <div class="absolute -top-1 -right-1 w-3 h-3 sm:w-4 sm:h-4 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <span class="text-white font-bold text-lg sm:text-xl bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent group-hover:from-yellow-400 group-hover:to-blue-400 transition-all duration-300 whitespace-nowrap">
                        1000 Proxy
                    </span>
                </a>

                <!-- Main Navigation -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="/" wire:navigate class="group relative px-4 py-2 rounded-xl transition-all duration-300 {{ request()->is('/') ? 'bg-blue-600/20 text-blue-400' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <span class="relative z-10 font-medium flex items-center">
                            <x-heroicon-o-home class="mr-2 h-4 w-4" />
                            Home
                        </span>
                        @if(request()->is('/'))
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-yellow-500/20 rounded-xl"></div>
                        @endif
                    </a>

                    <a href="/servers" wire:navigate class="group relative px-4 py-2 rounded-xl transition-all duration-300 {{ request()->is('servers*') ? 'bg-blue-600/20 text-blue-400' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <span class="relative z-10 font-medium flex items-center">
                            <x-heroicon-o-server class="mr-2 h-4 w-4" />
                            Servers
                        </span>
                        @if(request()->is('servers*'))
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-yellow-500/20 rounded-xl"></div>
                        @endif
                    </a>
                </nav>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
                @php
                    $customerUser = optional(auth('customer')->user())?->fresh(['wallet']);
                    $adminUser = auth('web')->user();
                @endphp
                @if($customerUser)
            <!-- Wallet Balance -->
            <a href="/account/wallet-management" class="hidden sm:flex items-center space-x-2 bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 rounded-xl px-4 py-2.5 hover:border-blue-400/50 transition-all duration-300 group">
                        <div class="w-6 h-6 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <x-heroicon-o-wallet class="w-4 h-4 text-gray-900" />
                        </div>
                        <span class="text-sm font-semibold text-white group-hover:text-yellow-400 transition-colors duration-300">
                ${{ number_format($customerUser->wallet?->balance ?? ($customerUser->wallet_balance ?? 0), 2) }}
                        </span>
                    </a>

                    <!-- Enhanced Cart Icon -->
                    <div class="relative">
                        <a href="/cart" wire:navigate class="group bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-blue-400/50 rounded-xl p-2 sm:p-3 transition-all duration-300 hover:scale-105 flex items-center justify-center {{ request()->is('cart*') ? 'ring-2 ring-blue-400/50' : '' }}">
                            <x-heroicon-o-shopping-cart class="w-5 h-5 sm:w-6 sm:h-6 text-white group-hover:text-blue-400 transition-colors duration-300" />
                        </a>
                        
                        @if($total_count > 0)
                            <span class="absolute -top-1 -right-1 sm:-top-2 sm:-right-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-gray-900 text-xs font-bold rounded-full h-5 w-5 sm:h-7 sm:w-7 flex items-center justify-center animate-bounce shadow-lg ring-2 ring-yellow-400/30" data-cart-count>
                                {{ $total_count }}
                            </span>
                            <div class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2 h-2 sm:w-3 sm:h-3 bg-yellow-400 rounded-full animate-ping"></div>
                        @endif
                    </div>

                    <!-- Enhanced User Menu -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" 
                                class="flex items-center space-x-2 sm:space-x-3 bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-blue-400/50 rounded-xl px-2 sm:px-4 py-2 sm:py-2.5 transition-all duration-300 hover:scale-105 transform">
                            <div class="relative">
                                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-xs sm:text-sm">
                    {{ strtoupper(substr($customerUser->name ?? 'U', 0, 2)) }}
                                    </span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 sm:w-4 sm:h-4 bg-green-500 rounded-full ring-2 ring-gray-900"></div>
                            </div>
                            <div class="hidden sm:block">
                <span class="text-sm font-semibold text-white">{{ $customerUser->name ?? 'User' }}</span>
                <div class="text-xs text-gray-400">{{ $customerUser->email ?? 'user@example.com' }}</div>
                            </div>
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-white transition-transform duration-300" x-bind:class="{ 'rotate-180': open }" />
                        </button>

                        <!-- Desktop Dropdown Backdrop Overlay -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200" 
                             x-transition:enter-start="opacity-0" 
                             x-transition:enter-end="opacity-100" 
                             x-transition:leave="transition ease-in duration-150" 
                             x-transition:leave-start="opacity-100" 
                             x-transition:leave-end="opacity-0"
                             @click="open = false"
                             class="fixed inset-0 bg-black/5 backdrop-blur-md z-40"></div>

                        <!-- Enhanced Dropdown Menu -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200" 
                             x-transition:enter-start="transform opacity-0 scale-95 translate-y-2" 
                             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0" 
                             x-transition:leave="transition ease-in duration-150" 
                             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0" 
                             x-transition:leave-end="transform opacity-0 scale-95 translate-y-2" 
                             class="absolute right-0 mt-3 w-64 backdrop-blur-xl border shadow-2xl z-50 overflow-hidden ring-1 rounded-2xl bg-white md:bg-gray-800 border-gray-300 md:border-blue-400/60 ring-gray-200 md:ring-white/20">
                            
                            <!-- Header -->
                            <div class="px-4 py-3 border-b border-gray-200 md:border-gray-600/80 bg-gradient-to-r from-blue-50 to-yellow-50 md:from-blue-600/40 md:to-yellow-600/40">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center">
                                        <span class="text-white font-bold">{{ substr($customerUser->name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold mobile-force-dark-text md:text-white">{{ $customerUser->name }}</div>
                                        <div class="text-xs mobile-force-icon-dark md:text-gray-300">{{ $customerUser->email }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2 bg-white md:bg-gray-800 mobile-force-dark-text md:text-white">
                                <!-- Dashboard/Profile -->
                                <a href="/account" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 md:hover:from-blue-600/40 md:hover:to-blue-700/40 hover:text-blue-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-blue-200 md:from-blue-500/40 md:to-blue-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-window class="w-4 h-4 text-blue-600 md:text-blue-300" />
                                    </div>
                                    <span class="group-hover:text-blue-900 md:group-hover:text-blue-200 transition-colors">Dashboard</span>
                                </a>

                                <!-- <a href="/categories" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-yellow-50 hover:to-orange-100 md:hover:from-yellow-600/40 md:hover:to-orange-700/40 hover:text-yellow-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-100 to-orange-200 md:from-yellow-500/40 md:to-orange-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-squares-2x2 class="w-4 h-4 text-yellow-600 md:text-yellow-300" />
                                    </div>
                                    <span class="group-hover:text-yellow-900 md:group-hover:text-orange-200 transition-colors">Categories</span>
                                </a> -->

                                <div class="border-t border-gray-300 md:border-gray-600/80 my-2"></div>

                                <!-- Navigation Links -->
                                <a href="/servers" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 md:hover:from-blue-600/40 md:hover:to-blue-700/40 hover:text-blue-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-blue-200 md:from-blue-500/40 md:to-blue-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-server class="w-4 h-4 text-blue-600 md:text-blue-300" />
                                    </div>
                                    <span class="group-hover:text-blue-900 md:group-hover:text-blue-200 transition-colors">Servers</span>
                                </a>

                                <a href="/my-orders" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 md:hover:from-blue-600/40 md:hover:to-blue-700/40 hover:text-blue-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-blue-200 md:from-blue-500/40 md:to-blue-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-shopping-bag class="w-4 h-4 text-blue-600 md:text-blue-300" />
                                    </div>
                                    <span class="group-hover:text-blue-900 md:group-hover:text-blue-200 transition-colors">My Orders</span>
                                </a>

                                <a href="/account/wallet-management" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-green-50 hover:to-green-100 md:hover:from-green-600/40 md:hover:to-green-700/40 hover:text-green-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-green-100 to-green-200 md:from-green-500/40 md:to-green-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-wallet class="w-4 h-4 text-green-600 md:text-green-300" />
                                    </div>
                                    <span class="group-hover:text-green-900 md:group-hover:text-green-200 transition-colors">My Wallet</span>
                                </a>

                                <a href="/transactions" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-yellow-50 hover:to-yellow-100 md:hover:from-yellow-600/40 md:hover:to-yellow-700/40 hover:text-yellow-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-100 to-yellow-200 md:from-yellow-500/40 md:to-yellow-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-credit-card class="w-4 h-4 text-yellow-600 md:text-yellow-300" />
                                    </div>
                                    <span class="group-hover:text-yellow-900 md:group-hover:text-yellow-200 transition-colors">Transactions</span>
                                </a>

                                <a href="/telegram-link" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-cyan-50 hover:to-cyan-100 md:hover:from-cyan-600/40 md:hover:to-cyan-700/40 hover:text-cyan-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-cyan-100 to-cyan-200 md:from-cyan-500/40 md:to-cyan-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-chat-bubble-oval-left-ellipsis class="w-4 h-4 text-cyan-600 md:text-cyan-300" />
                                    </div>
                                    <span class="group-hover:text-cyan-900 md:group-hover:text-cyan-200 transition-colors">Telegram Link</span>
                                </a>

                                <div class="border-t border-gray-300 md:border-gray-600/80 my-2"></div>

                                <a href="/account/user-profile" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-purple-50 hover:to-purple-100 md:hover:from-purple-600/40 md:hover:to-purple-700/40 hover:text-purple-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-100 to-purple-200 md:from-purple-500/40 md:to-purple-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-user-circle class="w-4 h-4 text-purple-600 md:text-purple-300" />
                                    </div>
                                    <span class="group-hover:text-purple-900 md:group-hover:text-purple-200 transition-colors">Account Settings</span>
                                </a>

                                <!-- Support & Help -->
                                <a href="#" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-indigo-50 hover:to-indigo-100 md:hover:from-indigo-600/40 md:hover:to-indigo-700/40 hover:text-indigo-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-100 to-indigo-200 md:from-indigo-500/40 md:to-indigo-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-question-mark-circle class="w-4 h-4 text-indigo-600 md:text-indigo-300" />
                                    </div>
                                    <span class="group-hover:text-indigo-900 md:group-hover:text-indigo-200 transition-colors">Help & Support</span>
                                </a>

                                <div class="border-t border-gray-300 md:border-gray-600/80 my-2"></div>

                                <a href="/logout" class="group flex items-center w-full px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-red-50 hover:to-red-100 md:hover:from-red-600/40 md:hover:to-red-700/40 hover:text-red-900 md:hover:text-white transition-all duration-200 text-left">
                                    <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-red-200 md:from-red-500/40 md:to-red-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 text-red-600 md:text-red-300" />
                                    </div>
                                    <span class="group-hover:text-red-900 md:group-hover:text-red-200 transition-colors">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif($adminUser)
                    <!-- Admin User Menu (styled like customer) -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open"
                                class="flex items-center space-x-2 sm:space-x-3 bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-blue-400/50 rounded-xl px-2 sm:px-4 py-2 sm:py-2.5 transition-all duration-300 hover:scale-105 transform">
                            <div class="relative">
                                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-xs sm:text-sm">
                                        {{ strtoupper(substr($adminUser->name ?? 'AD', 0, 2)) }}
                                    </span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 sm:w-4 sm:h-4 bg-green-500 rounded-full ring-2 ring-gray-900"></div>
                            </div>
                            <div class="hidden sm:block">
                                <span class="text-sm font-semibold text-white">{{ $adminUser->name ?? 'Admin' }}</span>
                                <div class="text-xs text-gray-400">{{ $adminUser->email ?? '' }}</div>
                            </div>
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-white transition-transform duration-300" x-bind:class="{ 'rotate-180': open }" />
                        </button>

                        <!-- Dropdown Backdrop -->
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             @click="open = false"
                             class="fixed inset-0 bg-black/5 backdrop-blur-md z-40"></div>

                        <!-- Admin Dropdown Menu -->
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
                             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="transform opacity-0 scale-95 translate-y-2"
                             class="absolute right-0 mt-3 w-64 backdrop-blur-xl border shadow-2xl z-50 overflow-hidden ring-1 rounded-2xl bg-white md:bg-gray-800 border-gray-300 md:border-blue-400/60 ring-gray-200 md:ring-white/20">

                            <!-- Header -->
                            <div class="px-4 py-3 border-b border-gray-200 md:border-gray-600/80 bg-gradient-to-r from-blue-50 to-yellow-50 md:from-blue-600/40 md:to-yellow-600/40">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center">
                                        <span class="text-white font-bold">{{ substr($adminUser->name ?? 'A', 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold mobile-force-dark-text md:text-white">{{ $adminUser->name ?? 'Admin' }}</div>
                                        <div class="text-xs mobile-force-icon-dark md:text-gray-300">{{ $adminUser->email ?? '' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2 bg-white md:bg-gray-800 mobile-force-dark-text md:text-white">
                                <a href="/admin" class="group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 md:hover:from-blue-600/40 md:hover:to-blue-700/40 hover:text-blue-900 md:hover:text-white transition-all duration-200">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-blue-200 md:from-blue-500/40 md:to-blue-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                        <x-heroicon-o-window class="w-4 h-4 text-blue-600 md:text-blue-300" />
                                    </div>
                                    <span class="group-hover:text-blue-900 md:group-hover:text-blue-200 transition-colors">Admin Dashboard</span>
                                </a>

                                <div class="border-t border-gray-300 md:border-gray-600/80 my-2"></div>

                                <!-- Admin Logout (Filament uses POST) -->
                                <form method="POST" action="/admin/logout">
                                    @csrf
                                    <button type="submit" class="w-full text-left group flex items-center px-4 py-3 text-sm mobile-force-dark-text hover:bg-gradient-to-r hover:from-red-50 hover:to-red-100 md:hover:from-red-600/40 md:hover:to-red-700/40 hover:text-red-900 md:hover:text-white transition-all duration-200">
                                        <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-red-200 md:from-red-500/40 md:to-red-600/40 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 text-red-600 md:text-red-300" />
                                        </div>
                                        <span class="group-hover:text-red-900 md:group-hover:text-red-200 transition-colors">Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Enhanced Guest Buttons -->
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <a href="/login" wire:navigate class="group relative px-3 sm:px-6 py-2 sm:py-2.5 text-white hover:text-blue-400 hover:bg-white/5 rounded-xl text-sm font-medium transition-all duration-300 border border-transparent hover:border-blue-500/30 flex items-center">
                            <x-heroicon-o-arrow-right-end-on-rectangle class="mr-1 sm:mr-2 h-4 w-4" />
                            <span class="relative z-10">Login</span>
                        </a>
                        <a href="/register" wire:navigate class="group relative bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 text-white px-3 sm:px-6 py-2 sm:py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center">
                            <x-heroicon-o-user-plus class="mr-1 sm:mr-2 h-4 w-4" />
                            <span class="relative z-10">Register</span>
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-yellow-600 rounded-xl blur opacity-30 group-hover:opacity-50 transition-opacity duration-300"></div>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
