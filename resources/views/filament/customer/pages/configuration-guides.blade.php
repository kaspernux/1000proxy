<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Quick Setup Guide -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-8 text-white">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Proxy Setup Guides</h1>
                    <p class="text-indigo-100 mb-4">
                        Get your proxy configured quickly with our step-by-step guides for all major platforms and clients.
                    </p>
                    <div class="flex items-center space-x-4">
                        <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-2">
                            <span class="text-sm">{{ count($this->getUserConfigurations()) }} Active Configs</span>
                        </div>
                        <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-2">
                            <span class="text-sm">{{ count($this->getAvailableConfigurations()['protocols']) }} Protocols</span>
                        </div>
                    </div>
                </div>
                <div class="text-center lg:text-right">
                    <div class="inline-block p-6 bg-white/10 backdrop-blur rounded-full">
                        <x-heroicon-o-cog-6-tooth class="w-16 h-16" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform & Client Selector -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Select Your Platform & Client</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Platform Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform</label>
                    <div class="space-y-2">
                        @foreach($this->getAvailableConfigurations()['platforms'] as $platform => $info)
                            <button
                                wire:click="updatePlatform('{{ $platform }}')"
                                class="w-full text-left p-3 rounded-lg border {{ $selectedPlatform === $platform ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }} hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div class="font-medium">{{ $info['name'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ count($info['recommended_clients']) }} clients available
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Client Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recommended Clients</label>
                    <div class="space-y-2">
                        @foreach($this->getAvailableConfigurations()['platforms'][$selectedPlatform]['recommended_clients'] as $client)
                            <button
                                wire:click="updateClient('{{ $client }}')"
                                class="w-full text-left p-3 rounded-lg border {{ $selectedClient === $client ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }} hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div class="font-medium">{{ $client }}</div>
                                @if($selectedClient === $client)
                                    <div class="text-sm text-blue-600 dark:text-blue-400">✓ Selected</div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Protocol Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Protocol</label>
                    <div class="space-y-2">
                        @foreach($this->getAvailableConfigurations()['protocols'] as $protocol => $info)
                            <button
                                wire:click="updateProtocol('{{ $protocol }}')"
                                class="w-full text-left p-3 rounded-lg border {{ $selectedProtocol === $protocol ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }} hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div class="font-medium">{{ $info['name'] }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $info['description'] }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Steps -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Setup Steps</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @foreach($this->getSetupSteps() as $step)
                    <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            @if($step['icon'])
                                <x-dynamic-component :component="$step['icon']" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            @endif
                        </div>
                        <div class="font-medium text-gray-900 dark:text-white mb-2">{{ $step['step'] }}. {{ $step['title'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $step['description'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Your Configurations -->
        @if(count($this->getUserConfigurations()) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Your Active Configurations</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($this->getUserConfigurations() as $config)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white">{{ $config['server_name'] }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $config['location'] }} • {{ strtoupper($config['protocol']) }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Active
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <!-- QR Code -->
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center">
                                        <x-heroicon-o-qr-code class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-2" />
                                        <span class="text-sm font-medium">QR Code</span>
                                    </div>
                                    <button
                                        onclick="showQRCode('{{ $config['qr_code'] }}')"
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors"
                                    >
                                        Show
                                    </button>
                                </div>

                                <!-- Subscription URL -->
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center">
                                        <x-heroicon-o-link class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-2" />
                                        <span class="text-sm font-medium">Subscription URL</span>
                                    </div>
                                    <button
                                        onclick="copyToClipboard('{{ $config['subscription_url'] }}')"
                                        class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded transition-colors"
                                    >
                                        Copy
                                    </button>
                                </div>

                                <!-- Manual Config -->
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center">
                                        <x-heroicon-o-document-text class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-2" />
                                        <span class="text-sm font-medium">Manual Configuration</span>
                                    </div>
                                    <button
                                        onclick="showManualConfig({{ json_encode($config['manual_config']) }})"
                                        class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded transition-colors"
                                    >
                                        View
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <!-- No Configurations -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-8 text-center">
                <x-heroicon-o-exclamation-circle class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Active Configurations</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    You need to purchase a proxy service first to get configuration files.
                </p>
                <a
                    href="{{ route('filament.customer.pages.server-browsing') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                    <x-heroicon-o-server class="w-4 h-4 mr-2" />
                    Browse Servers
                </a>
            </div>
        @endif

        <!-- Troubleshooting -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Troubleshooting</h3>
            <div class="space-y-4">
                @foreach($this->getTroubleshootingTips() as $tip)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ $tip['issue'] }}</h4>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            @foreach($tip['solutions'] as $solution)
                                <li class="flex items-start">
                                    <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" />
                                    {{ $solution }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Need Help? -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex items-center">
                <x-heroicon-o-question-mark-circle class="w-8 h-8 text-blue-600 dark:text-blue-400 mr-4" />
                <div class="flex-1">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-1">Need Additional Help?</h4>
                    <p class="text-blue-800 dark:text-blue-200 text-sm">
                        Our support team is available 24/7 to assist you with configuration and troubleshooting.
                    </p>
                </div>
                <div class="ml-4">
                    <a
                        href="#"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    >
                        <x-heroicon-o-chat-bubble-left class="w-4 h-4 mr-2" />
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm mx-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">QR Code</h3>
                    <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>
                <div class="text-center">
                    <img id="qrImage" src="" alt="QR Code" class="mx-auto mb-4 border border-gray-200 dark:border-gray-700 rounded">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Scan with your mobile client</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Config Modal -->
    <div id="configModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl mx-auto max-h-96 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Manual Configuration</h3>
                    <button onclick="closeConfigModal()" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>
                <div id="configContent" class="space-y-3">
                    <!-- Config content will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success notification
                window.dispatchEvent(new CustomEvent('show-notification', {
                    detail: { message: 'Copied to clipboard!', type: 'success' }
                }));
            });
        }

        function showQRCode(qrUrl) {
            document.getElementById('qrImage').src = qrUrl;
            const modal = document.getElementById('qrModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeQRModal() {
            const modal = document.getElementById('qrModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function showManualConfig(config) {
            const content = document.getElementById('configContent');
            content.innerHTML = '';

            Object.entries(config).forEach(([protocol, settings]) => {
                const protocolDiv = document.createElement('div');
                protocolDiv.className = 'border border-gray-200 dark:border-gray-700 rounded-lg p-4';

                let html = `<h4 class="font-medium text-gray-900 dark:text-white mb-2">${protocol.toUpperCase()}</h4>`;

                Object.entries(settings).forEach(([key, value]) => {
                    html += `
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">${key}:</span>
                            <span class="font-mono text-gray-900 dark:text-white">${value}</span>
                        </div>
                    `;
                });

                protocolDiv.innerHTML = html;
                content.appendChild(protocolDiv);
            });

            const modal = document.getElementById('configModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeConfigModal() {
            const modal = document.getElementById('configModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.id === 'qrModal') {
                closeQRModal();
            }
            if (event.target.id === 'configModal') {
                closeConfigModal();
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
