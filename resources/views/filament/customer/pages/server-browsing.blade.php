<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Servers</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ \App\Models\Server::where('is_active', true)->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Servers</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ \App\Models\Server::where('is_active', true)->where('status', 'active')->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Locations</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ \App\Models\Server::where('is_active', true)->distinct('location')->count('location') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Starting From</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            ${{ \App\Models\Server::where('is_active', true)->min('price') ?? '0' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Server Filters Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            {{ $this->form }}
        </div>

        {{-- Featured Servers Banner --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">🌟 Featured Servers</h3>
                    <p class="text-blue-100">High-performance servers with 99.9% uptime guarantee</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">Special Offers</div>
                    <div class="text-blue-200">Up to 50% off first month</div>
                </div>
            </div>
        </div>

        {{-- Quick Filters --}}
        <div class="flex flex-wrap gap-2">
            <button
                class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm hover:bg-blue-200 transition-colors"
                wire:click="$set('filters.location', ['us'])"
            >
                🇺🇸 US Servers
            </button>
            <button
                class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm hover:bg-green-200 transition-colors"
                wire:click="$set('filters.location', ['eu'])"
            >
                🇪🇺 EU Servers
            </button>
            <button
                class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm hover:bg-purple-200 transition-colors"
                wire:click="$set('filters.location', ['asia'])"
            >
                🌏 Asia Servers
            </button>
            <button
                class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm hover:bg-yellow-200 transition-colors"
                wire:click="$set('filters.max_price', 10)"
            >
                💰 Under $10
            </button>
            <button
                class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm hover:bg-red-200 transition-colors"
                wire:click="$set('filters.features', ['high_speed'])"
            >
                ⚡ High Speed
            </button>
            <button
                class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm hover:bg-indigo-200 transition-colors"
                wire:click="resetTable()"
            >
                🔄 Reset All
            </button>
        </div>

        {{-- Servers Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            {{ $this->table }}
        </div>

        {{-- Help Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    🤔 Need Help Choosing?
                </h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Check server location closest to you for best performance
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Look for 99%+ uptime for reliable connections
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Consider your usage needs (streaming, gaming, browsing)
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Try our recommendations for personalized suggestions
                    </li>
                </ul>
                <button
                    wire:click="getServerRecommendations"
                    class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Get Personalized Recommendations
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📊 Protocol Comparison
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium">VLESS</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Fastest</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium">VMess</span>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Secure</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium">Trojan</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">Stealth</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium">Shadowsocks</span>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">Lightweight</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium">SOCKS5</span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded">Universal</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Real-time Status Indicator --}}
        <div class="text-center text-sm text-gray-500 dark:text-gray-400">
            🔄 Server list updates automatically every 30 seconds
            <span class="mx-2">•</span>
            Last updated: {{ now()->format('H:i:s') }}
        </div>
    </div>

    {{-- Custom Styles --}}
    <style>
        .fi-ta-table {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .fi-ta-row:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        .server-status-active {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .quick-filter-btn {
            transition: all 0.2s ease-in-out;
        }

        .quick-filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>

    {{-- JavaScript for Enhanced Interactivity --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh functionality
            setInterval(function() {
                if (document.querySelector('.fi-ta-table')) {
                    // Trigger table refresh
                    Livewire.dispatch('refreshTable');
                }
            }, 30000); // 30 seconds

            // Add click animations to quick filters
            document.querySelectorAll('[wire\\:click]').forEach(button => {
                button.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</x-filament-panels::page>
