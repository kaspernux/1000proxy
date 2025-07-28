<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '1000Proxy') }} - Premium Proxy Solutions</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Livewire Styles -->
        @livewireStyles

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #1e293b;
            }

            ::-webkit-scrollbar-thumb {
                background: #10b981;
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #059669;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-white">
        <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
            <!-- Navigation -->
            <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-700/50 sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <a href="/" class="flex items-center">
                                <x-custom-icon name="server" class="h-8 w-8 text-emerald-500 mr-3" />
                                <span class="text-2xl font-bold bg-gradient-to-r from-emerald-400 to-blue-500 bg-clip-text text-transparent">
                                    1000Proxy
                                </span>
                            </a>

                            <!-- Navigation Links -->
                            <div class="hidden md:ml-10 md:flex space-x-8">
                                <a href="/" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                    Home
                                </a>
                                <a href="/products" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                    <x-custom-icon name="server" class="h-4 w-4 inline mr-1" />
                                    Proxies
                                </a>
                                <a href="/pricing" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                    <x-custom-icon name="credit-card" class="h-4 w-4 inline mr-1" />
                                    Pricing
                                </a>
                                <a href="/docs" class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                    <x-custom-icon name="document-text" class="h-4 w-4 inline mr-1" />
                                    Documentation
                                </a>
                            </div>
                        </div>

                        <!-- Right side menu -->
                        <div class="flex items-center space-x-4">
                            @auth
                                <!-- Cart Icon -->
                                <a href="/cart" class="relative p-2 text-slate-300 hover:text-white transition-colors duration-200">
                                    <x-custom-icon name="shopping-cart" class="h-6 w-6" />
                                    @if(session('cart') && count(session('cart')) > 0)
                                        <span class="absolute -top-1 -right-1 bg-emerald-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ count(session('cart')) }}
                                        </span>
                                    @endif
                                </a>

                                <!-- User Menu -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open"
                                            class="flex items-center text-sm rounded-full text-white hover:text-emerald-400 transition-colors duration-200">
                                        <x-custom-icon name="user" class="h-6 w-6 mr-2" />
                                        {{ Auth::user()->name }}
                                        <x-custom-icon name="chevron-down" class="h-4 w-4 ml-1" />
                                    </button>

                                    <div x-show="open"
                                         @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         class="absolute right-0 mt-2 w-48 bg-slate-800 rounded-md shadow-lg py-1 z-50">
                                        <a href="/dashboard" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700">
                                            <x-custom-icon name="chart-bar" class="h-4 w-4 mr-2" />
                                            Dashboard
                                        </a>
                                        <a href="/profile" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700">
                                            <x-custom-icon name="user" class="h-4 w-4 mr-2" />
                                            Profile
                                        </a>
                                        <a href="/orders" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700">
                                            <x-custom-icon name="document-text" class="h-4 w-4 mr-2" />
                                            My Orders
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-slate-300 hover:bg-slate-700">
                                                <x-custom-icon name="arrow-right" class="h-4 w-4 mr-2 rotate-180" />
                                                Sign Out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('login') }}"
                                   class="text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                    <x-custom-icon name="user" class="h-4 w-4 inline mr-1" />
                                    Sign In
                                </a>
                                <a href="{{ route('register') }}"
                                   class="bg-gradient-to-r from-emerald-500 to-blue-600 hover:from-emerald-600 hover:to-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                                    <x-custom-icon name="star" class="h-4 w-4 inline mr-1" />
                                    Get Started
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-slate-900/50 backdrop-blur-md border-t border-slate-700/50 mt-20">
                <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- Company Info -->
                        <div class="col-span-1 md:col-span-2">
                            <div class="flex items-center mb-4">
                                <x-custom-icon name="server" class="h-8 w-8 text-emerald-500 mr-3" />
                                <span class="text-2xl font-bold bg-gradient-to-r from-emerald-400 to-blue-500 bg-clip-text text-transparent">
                                    1000Proxy
                                </span>
                            </div>
                            <p class="text-slate-400 mb-4">
                                Professional-grade proxy solutions for developers, businesses, and individuals
                                who demand the best performance and reliability.
                            </p>
                            <div class="flex space-x-4">
                                <a href="#" class="text-slate-400 hover:text-emerald-400 transition-colors duration-200">
                                    <x-custom-icon name="globe-alt" class="h-5 w-5" />
                                </a>
                                <a href="#" class="text-slate-400 hover:text-emerald-400 transition-colors duration-200">
                                    <x-custom-icon name="heart" class="h-5 w-5" />
                                </a>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div>
                            <h3 class="text-white font-semibold mb-4">Products</h3>
                            <ul class="space-y-2">
                                <li><a href="/proxies" class="text-slate-400 hover:text-white transition-colors duration-200">Proxy Services</a></li>
                                <li><a href="/vpn" class="text-slate-400 hover:text-white transition-colors duration-200">VPN Solutions</a></li>
                                <li><a href="/api" class="text-slate-400 hover:text-white transition-colors duration-200">API Access</a></li>
                                <li><a href="/enterprise" class="text-slate-400 hover:text-white transition-colors duration-200">Enterprise</a></li>
                            </ul>
                        </div>

                        <!-- Support -->
                        <div>
                            <h3 class="text-white font-semibold mb-4">Support</h3>
                            <ul class="space-y-2">
                                <li><a href="/docs" class="text-slate-400 hover:text-white transition-colors duration-200">Documentation</a></li>
                                <li><a href="/contact" class="text-slate-400 hover:text-white transition-colors duration-200">Contact Us</a></li>
                                <li><a href="/status" class="text-slate-400 hover:text-white transition-colors duration-200">System Status</a></li>
                                <li><a href="/help" class="text-slate-400 hover:text-white transition-colors duration-200">Help Center</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="border-t border-slate-700 pt-8 mt-8">
                        <div class="flex flex-col md:flex-row justify-between items-center">
                            <p class="text-slate-400 text-sm">
                                &copy; {{ date('Y') }} 1000Proxy. All rights reserved.
                            </p>
                            <div class="flex space-x-6 mt-4 md:mt-0">
                                <a href="/privacy" class="text-slate-400 hover:text-white text-sm transition-colors duration-200">Privacy Policy</a>
                                <a href="/terms" class="text-slate-400 hover:text-white text-sm transition-colors duration-200">Terms of Service</a>
                                <a href="/security" class="text-slate-400 hover:text-white text-sm transition-colors duration-200">Security</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Livewire Scripts -->
        @livewireScripts
    </body>
</html>
