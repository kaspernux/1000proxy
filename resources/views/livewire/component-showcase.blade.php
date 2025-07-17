<div class="component-showcase min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Advanced Livewire Components Showcase
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                Explore our comprehensive collection of advanced Livewire components designed for the 1000proxy platform.
                Each component features real-time functionality, professional UI/UX, and seamless integration.
            </p>
        </div>

        {{-- Component Navigation --}}
        <div class="mb-8">
            <nav class="flex space-x-4 bg-white dark:bg-gray-800 rounded-lg p-2 shadow-sm">
                <button
                    wire:click="switchDemo('server-browser')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeDemo === 'server-browser' ? 'bg-blue-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                >
                    <x-custom-icon name="server" class="w-4 h-4 mr-2" /> Server Browser
                </button>
                <button
                    wire:click="switchDemo('proxy-config')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeDemo === 'proxy-config' ? 'bg-blue-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                >
                    <x-custom-icon name="cog-6-tooth" class="w-4 h-4 mr-2" /> Proxy Configuration
                </button>
                <button
                    wire:click="switchDemo('payment-processor')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeDemo === 'payment-processor' ? 'bg-blue-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                >
                    💳 Payment Processor
                </button>
                <button
                    wire:click="switchDemo('health-monitor')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeDemo === 'health-monitor' ? 'bg-blue-500 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                >
                    <x-custom-icon name="chart-bar" class="w-4 h-4 mr-2" /> Health Monitor
                </button>
            </nav>
        </div>

        {{-- Component Demos --}}
        <div class="space-y-8">

            {{-- Server Browser Demo --}}
            @if($activeDemo === 'server-browser')
                <div class="component-demo">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            ServerBrowser Component
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">
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
                        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                            <p class="text-gray-500 dark:text-gray-400">No sample servers available. Please seed the database with sample data.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Proxy Configuration Demo --}}
            @if($activeDemo === 'proxy-config')
                <div class="component-demo">
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
                        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                            <p class="text-gray-500 dark:text-gray-400">No sample client configuration available. Please create a sample client configuration.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Payment Processor Demo --}}
            @if($activeDemo === 'payment-processor')
                <div class="component-demo">
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
                <div class="component-demo">
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
                        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                            <p class="text-gray-500 dark:text-gray-400">No sample servers available for health monitoring. Please seed the database with sample data.</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Implementation Guide --}}
        <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
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
        <div class="mt-8 bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
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
