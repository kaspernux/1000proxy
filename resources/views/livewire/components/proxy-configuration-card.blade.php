<div class="proxy-configuration-card bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header with client info --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <span class="text-2xl">{{ $serverClient->server->flag }}</span>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $serverClient->serverPlan->name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $serverClient->email }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Status indicator --}}
        <div class="flex items-center space-x-2">
            <div class="w-3 h-3 rounded-full {{ $clientStatus['status'] === 'active' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
            <span class="text-sm font-medium {{ $clientStatus['status'] === 'active' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ucfirst($clientStatus['status']) }}
            </span>
        </div>
    </div>

    {{-- Quick stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($clientStatus['data_usage']['total'] / 1024 / 1024, 1) }}
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400">MB Used</div>
        </div>

        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ $connectionStats['success_rate'] }}
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400">Success Rate</div>
        </div>

        <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ $connectionStats['avg_speed'] }}
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400">Avg Speed</div>
        </div>

        <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                @if($clientStatus['remaining_days'])
                    {{ $clientStatus['remaining_days'] }}
                @else
                    ∞
                @endif
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400">Days Left</div>
        </div>
    </div>

    {{-- Configuration format selector --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Configuration Format
            </label>
            <div class="flex items-center space-x-2">
                <button
                    wire:click="toggleQrCode"
                    class="p-2 rounded-lg {{ $showQrCode ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
                    title="Toggle QR Code"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"></path>
                        <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 01-1-1zM16 10a1 1 0 100-2H15a1 1 0 100 2h1zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"></path>
                    </svg>
                </button>
                <button
                    wire:click="testConnection"
                    class="p-2 bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-500 hover:text-white transition-all duration-200"
                    title="Test Connection"
                    wire:loading.attr="disabled"
                >
                    <svg class="w-4 h-4" wire:loading.class="animate-spin" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                wire:click="changeConfigFormat('link')"
                class="px-3 py-1 text-sm rounded-lg {{ $configFormat === 'link' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
            >
                Link
            </button>
            <button
                wire:click="changeConfigFormat('json')"
                class="px-3 py-1 text-sm rounded-lg {{ $configFormat === 'json' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
            >
                JSON
            </button>
            <button
                wire:click="changeConfigFormat('clash')"
                class="px-3 py-1 text-sm rounded-lg {{ $configFormat === 'clash' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
            >
                Clash
            </button>
            <button
                wire:click="changeConfigFormat('v2ray')"
                class="px-3 py-1 text-sm rounded-lg {{ $configFormat === 'v2ray' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
            >
                V2Ray
            </button>
        </div>
    </div>

    {{-- Configuration display --}}
    <div class="space-y-4">
        {{-- QR Code section --}}
        @if($showQrCode && $configFormat === 'link')
            <div class="text-center py-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="inline-block p-4 bg-white rounded-lg shadow-sm">
                    {!! $qrCodeSvg !!}
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Scan with your proxy client app
                </p>
            </div>
        @endif

        {{-- Configuration text --}}
        <div class="relative">
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ ucfirst($configFormat) }} Configuration
                </label>
                <div class="flex items-center space-x-2">
                    <button
                        wire:click="copyToClipboard('{{ addslashes($configData[$configFormat] ?? '') }}')"
                        class="p-1 text-gray-500 hover:text-blue-500 transition-colors duration-200"
                        title="Copy to clipboard"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"></path>
                        </svg>
                    </button>
                    <select
                        wire:model.live="downloadFormat"
                        class="text-xs py-1 px-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="txt">TXT</option>
                        <option value="json">JSON</option>
                        <option value="yaml">YAML</option>
                    </select>
                    <button
                        wire:click="downloadConfig"
                        class="p-1 text-gray-500 hover:text-green-500 transition-colors duration-200"
                        title="Download configuration"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="relative">
                <textarea
                    readonly
                    rows="{{ $configFormat === 'link' ? '3' : '8' }}"
                    class="w-full p-3 font-mono text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Configuration will appear here..."
                >{{ $configData[$configFormat] ?? 'Configuration not available' }}</textarea>

                {{-- Copy overlay --}}
                <div
                    x-data="{ copied: false }"
                    @click="
                        navigator.clipboard.writeText($el.previousElementSibling.value);
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                        $wire.alert('success', 'Copied to clipboard!', { position: 'top-end', timer: 2000, toast: true });
                    "
                    class="absolute inset-0 cursor-pointer opacity-0 hover:opacity-100 transition-opacity duration-200 bg-black bg-opacity-10 rounded-lg flex items-center justify-center"
                >
                    <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 shadow-lg">
                        <span x-show="!copied" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Click to copy
                        </span>
                        <span x-show="copied" class="text-sm font-medium text-green-600 dark:text-green-400">
                            Copied!
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Advanced configuration toggle --}}
        <div>
            <button
                wire:click="toggleAdvancedConfig"
                class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors duration-200"
            >
                <svg class="w-4 h-4 transition-transform duration-200 {{ $showAdvancedConfig ? 'rotate-90' : '' }}" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span>Advanced Configuration Details</span>
            </button>

            @if($showAdvancedConfig)
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Server</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ $configData['base']['server'] }}</div>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Port</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ $configData['base']['port'] }}</div>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Protocol</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ strtoupper($configData['base']['protocol']) }}</div>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Security</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ strtoupper($configData['base']['security']) }}</div>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">UUID</label>
                            <div class="text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $configData['base']['uuid'] }}</div>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">SNI</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ $configData['base']['sni'] ?: 'Not set' }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Action buttons --}}
    <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
        <div class="flex items-center space-x-2">
            <button
                wire:click="refreshConfig"
                class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-500 hover:text-white transition-all duration-200"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Refresh</span>
                <span wire:loading>Refreshing...</span>
            </button>
            <button
                wire:click="resetClientConfig"
                class="px-3 py-1 text-sm bg-orange-100 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 rounded-lg hover:bg-orange-500 hover:text-white transition-all duration-200"
                onclick="return confirm('Are you sure you want to reset the client configuration?')"
            >
                Reset Config
            </button>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400">
            Last updated: {{ $clientStatus['last_connection'] ? $clientStatus['last_connection']->diffForHumans() : 'Never' }}
        </div>
    </div>

    {{-- Copy to clipboard script --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('copyToClipboard', (event) => {
                navigator.clipboard.writeText(event.text).then(() => {
                    console.log('Text copied to clipboard');
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });

            Livewire.on('downloadFile', (event) => {
                const blob = new Blob([event.content], { type: event.mimeType });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = event.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            });
        });
    </script>
</div>
