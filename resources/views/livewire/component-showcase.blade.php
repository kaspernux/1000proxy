<main class="component-showcase min-h-screen bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 py-10 px-2 sm:px-6 lg:px-8 flex flex-col items-center relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <section class="w-full max-w-7xl mx-auto relative z-10">

        {{-- Header --}}
        <header class="text-center mb-10">
            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md mx-auto mb-6 border border-blue-400/30">
                <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-4">Advanced Livewire Components Showcase</h1>
            <p class="text-lg text-gray-300 max-w-3xl mx-auto">Explore our comprehensive collection of advanced Livewire components designed for the 1000proxy platform. Each component features real-time functionality, professional UI/UX, and seamless integration.</p>
        </header>

        {{-- Component Navigation --}}
        <nav class="mb-10 flex flex-wrap gap-4 justify-center bg-white/10 backdrop-blur-md rounded-xl p-3 shadow-lg border border-white/20">
                <button
                    wire:click="switchDemo('server-browser')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 {{ $activeDemo === 'server-browser' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-white/10' }}"
                >
                    <x-custom-icon name="server" class="w-4 h-4 mr-2" /> Server Browser
                </button>
                <button
                    wire:click="switchDemo('proxy-config')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 {{ $activeDemo === 'proxy-config' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-white/10' }}"
                >
                    <x-custom-icon name="cog-6-tooth" class="w-4 h-4 mr-2" /> Proxy Configuration
                </button>
                <button
                    wire:click="switchDemo('payment-processor')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 {{ $activeDemo === 'payment-processor' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-white/10' }}"
                >
                    💳 Payment Processor
                </button>
                <button
                    wire:click="switchDemo('health-monitor')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 {{ $activeDemo === 'health-monitor' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-white/10' }}"
                >
                    <x-custom-icon name="chart-bar" class="w-4 h-4 mr-2" /> Health Monitor
                </button>
            </nav>

        {{-- Component Demos --}}
        <div class="space-y-12">

            {{-- Server Browser Demo --}}
            @if($activeDemo === 'server-browser')
                <section class="component-demo bg-white/10 backdrop-blur-md rounded-2xl shadow-2xl p-8 border border-white/20 hover:shadow-3xl transition-all duration-500">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-2">
                            ServerBrowser Component
                        </h2>
                        <p class="text-gray-300">
                            Advanced server filtering and browsing with real-time health monitoring, multiple view modes,
                            and comprehensive search capabilities.
                        </p>

                        {{-- Features list --}}
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span>Real-time server health monitoring</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                <span>Advanced filtering & search</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                <span>Multiple view modes (grid/list)</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                <span>Location-first sorting</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                <span>Protocol & category filtering</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-teal-500 rounded-full"></span>
                                <span>Price & speed range filters</span>
                            </div>
                        </div>
                    </div>

                    @if($sampleServers->count() > 0)
                        <livewire:components.server-browser :servers="$sampleServers" />
                    @else
                        <div class="text-center py-12 bg-white/80 dark:bg-gray-800/80 rounded-lg">
                            <p class="text-gray-500 dark:text-gray-400">No sample servers available. Please seed the database with sample data.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Proxy Configuration Demo --}}
            @if($activeDemo === 'proxy-config')
                <section class="component-demo bg-white/90 dark:bg-gray-800/90 rounded-2xl shadow-lg p-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            ProxyConfigurationCard Component
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Generate proxy configurations in multiple formats with QR codes, connection testing,
                            and advanced configuration options.
                        </p>

                        {{-- Features list --}}
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span>Multiple config formats (VLESS, VMESS, Trojan, Shadowsocks)</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                <span>QR code generation for mobile apps</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                <span>Connection testing & status monitoring</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                <span>One-click copy & download functionality</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                <span>Advanced configuration details</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-teal-500 rounded-full"></span>
                                <span>Client statistics & usage data</span>
                            </div>
                        </div>
                    </div>

                    @if($sampleClient)
                        <livewire:components.proxy-configuration-card :server-client="$sampleClient" />
                    @else
                        <div class="text-center py-12 bg-white/80 dark:bg-gray-800/80 rounded-lg">
                            <p class="text-gray-500 dark:text-gray-400">No sample client configuration available. Please create a sample client configuration.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Payment Processor Demo --}}
            @if($activeDemo === 'payment-processor')
                <section class="component-demo bg-white/90 dark:bg-gray-800/90 rounded-2xl shadow-lg p-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            PaymentProcessor Component
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Comprehensive payment processing with multiple gateways, real-time status updates,
                            and secure transaction handling.
                        </p>

                        {{-- Features list --}}
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span>Multiple payment gateways (Stripe, PayPal, Crypto, Wallet)</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                <span>Real-time payment processing & status updates</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                <span>Cryptocurrency support with live rates</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                <span>Wallet balance integration</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                <span>Secure card processing with validation</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-teal-500 rounded-full"></span>
                                <span>Progress tracking & error handling</span>
                            </div>
                        </div>
                    </div>

                    <livewire:components.payment-processor :order="$sampleOrder" />
                </div>
            @endif

            {{-- Health Monitor Demo --}}
            @if($activeDemo === 'health-monitor')
                <section class="component-demo bg-white/90 dark:bg-gray-800/90 rounded-2xl shadow-lg p-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            XUIHealthMonitor Component
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Real-time monitoring of XUI server health with system metrics, alerts,
                            and automated status tracking.
                        </p>

                        {{-- Features list --}}
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span>Real-time server health monitoring</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                <span>System metrics (CPU, Memory, Disk, Network)</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                <span>Automated alerts & notifications</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                <span>Connection testing & server restart</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                <span>Customizable refresh intervals</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="w-2 h-2 bg-teal-500 rounded-full"></span>
                                <span>Detailed system metrics modal</span>
                            </div>
                        </div>
                    </div>

                    @if($sampleServers->count() > 0)
                        <livewire:components.xui-health-monitor :servers="$sampleServers" />
                    @else
                        <div class="text-center py-12 bg-white/80 dark:bg-gray-800/80 rounded-lg">
                            <p class="text-gray-500 dark:text-gray-400">No sample servers available for health monitoring. Please seed the database with sample data.</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Implementation Guide --}}
        <section class="mt-16 bg-white/90 dark:bg-gray-800/90 rounded-2xl shadow-lg p-8">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Implementation Guide
            </h3>

            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    These advanced Livewire components are designed to be modular, reusable, and easily integrated into your application.
                    Each component follows best practices for performance, security, and user experience.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div class="implementation-card p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2"><x-custom-icon name="bolt" class="w-4 h-4" /> Quick Integration</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Simply include the component in your Blade templates using the <code>&lt;livewire:components.component-name /&gt;</code> syntax.
                        </p>
                    </div>

                    <div class="implementation-card p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2"><x-custom-icon name="bolt" class="w-4 h-4" /> Real-time Features</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            All components support real-time updates with automatic polling, event broadcasting, and live notifications.
                        </p>
                    </div>

                    <div class="implementation-card p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🎨 Customizable UI</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Built with Tailwind CSS and Alpine.js for easy customization and responsive design across all devices.
                        </p>
                    </div>

                    <div class="implementation-card p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2"><x-custom-icon name="shield-check" class="w-4 h-4" /> Security First</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Implements proper validation, CSRF protection, and secure data handling for payment processing and sensitive operations.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Component Files --}}
        <section class="mt-10 bg-gray-50/90 dark:bg-gray-700/90 rounded-2xl p-6 shadow-lg">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Component File Locations</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm font-mono">
                <div>
                    <h5 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">PHP Components:</h5>
                    <ul class="space-y-1 text-gray-600 dark:text-gray-400">
                        <li>app/Livewire/Components/ServerBrowser.php</li>
                        <li>app/Livewire/Components/ProxyConfigurationCard.php</li>
                        <li>app/Livewire/Components/PaymentProcessor.php</li>
                        <li>app/Livewire/Components/XUIHealthMonitor.php</li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Blade Views:</h5>
                    <ul class="space-y-1 text-gray-600 dark:text-gray-400">
                        <li>resources/views/livewire/components/server-browser.blade.php</li>
                        <li>resources/views/livewire/components/proxy-configuration-card.blade.php</li>
                        <li>resources/views/livewire/components/payment-processor.blade.php</li>
                        <li>resources/views/livewire/components/xui-health-monitor.blade.php</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
