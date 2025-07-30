
<section class="proxy-configuration-card bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-0 overflow-hidden border border-gray-100 dark:border-gray-800">
    <!-- Header -->
    <header class="flex flex-col md:flex-row items-center justify-between gap-4 px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-br from-blue-50/60 dark:from-gray-800/60 to-white dark:to-gray-900">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <span class="text-3xl md:text-4xl">{{ $serverClient->server->flag }}</span>
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white leading-tight">
                    {{ $serverClient->serverPlan->name }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $serverClient->email }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1">
                <span class="w-3 h-3 rounded-full {{ $clientStatus['status'] === 'active' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
                <span class="text-sm font-semibold {{ $clientStatus['status'] === 'active' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ ucfirst($clientStatus['status']) }}
                </span>
            </span>
        </div>
    </header>

    <!-- Quick Stats -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 px-6 py-4 bg-gray-50 dark:bg-gray-900">
        <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-blue-50 dark:bg-blue-900/30">
            <span class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ number_format($clientStatus['data_usage']['total'] / 1024 / 1024, 1) }}</span>
            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1">MB Used</span>
        </div>
        <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-green-50 dark:bg-green-900/30">
            <span class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ $connectionStats['success_rate'] }}</span>
            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1">Success Rate</span>
        </div>
        <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-purple-50 dark:bg-purple-900/30">
            <span class="text-2xl font-extrabold text-purple-600 dark:text-purple-400">{{ $connectionStats['avg_speed'] }}</span>
            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1">Avg Speed</span>
        </div>
        <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-orange-50 dark:bg-orange-900/30">
            <span class="text-2xl font-extrabold text-orange-600 dark:text-orange-400">
                @if($clientStatus['remaining_days'])
                    {{ $clientStatus['remaining_days'] }}
                @else
                    ∞
                @endif
            </span>
            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1">Days Left</span>
        </div>
    </section>

    <!-- Configuration Format Selector -->
    <section class="px-6 pt-6 pb-2">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Configuration Format</label>
            <div class="flex items-center gap-2">
                <button
                    wire:click="toggleQrCode"
                    class="p-2 rounded-lg {{ $showQrCode ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
                    title="Toggle QR Code"
                    aria-label="Toggle QR Code"
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
                    aria-label="Test Connection"
                >
                    <svg class="w-4 h-4" wire:loading.class="animate-spin" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
        <nav class="flex flex-wrap gap-2" aria-label="Configuration format tabs">
            <button
                wire:click="changeConfigFormat('link')"
                class="px-3 py-1 text-sm rounded-lg font-medium {{ $configFormat === 'link' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
                aria-current="{{ $configFormat === 'link' ? 'page' : false }}"
            >Link</button>
            <button
                wire:click="changeConfigFormat('json')"
                class="px-3 py-1 text-sm rounded-lg font-medium {{ $configFormat === 'json' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
                aria-current="{{ $configFormat === 'json' ? 'page' : false }}"
            >JSON</button>
            <button
                wire:click="changeConfigFormat('clash')"
                class="px-3 py-1 text-sm rounded-lg font-medium {{ $configFormat === 'clash' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
                aria-current="{{ $configFormat === 'clash' ? 'page' : false }}"
            >Clash</button>
            <button
                wire:click="changeConfigFormat('v2ray')"
                class="px-3 py-1 text-sm rounded-lg font-medium {{ $configFormat === 'v2ray' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} hover:bg-blue-500 hover:text-white transition-all duration-200"
                aria-current="{{ $configFormat === 'v2ray' ? 'page' : false }}"
            >V2Ray</button>
        </nav>
    </section>

    <!-- Configuration Display -->
    <section class="space-y-4 px-6 pb-6">
        @if($showQrCode && $configFormat === 'link')
            <div class="flex flex-col items-center justify-center py-6 bg-gray-50 dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="inline-block p-4 bg-white dark:bg-gray-900 rounded-lg shadow">
                    {!! $qrCodeSvg !!}
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Scan with your proxy client app</p>
            </div>
        @endif

        <div class="relative">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ ucfirst($configFormat) }} Configuration
                </label>
                <div class="flex items-center gap-2">
                    <button
                        wire:click="copyToClipboard('{{ addslashes($configData[$configFormat] ?? '') }}')"
                        class="p-1 text-gray-500 hover:text-blue-500 transition-colors duration-200"
                        title="Copy to clipboard"
                        aria-label="Copy to clipboard"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"></path>
                        </svg>
                    </button>
                    <select
                        wire:model.live="downloadFormat"
                        class="text-xs py-1 px-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        aria-label="Download format"
                    >
                        <option value="txt">TXT</option>
                        <option value="json">JSON</option>
                        <option value="yaml">YAML</option>
                    </select>
                    <button
                        wire:click="downloadConfig"
                        class="p-1 text-gray-500 hover:text-green-500 transition-colors duration-200"
                        title="Download configuration"
                        aria-label="Download configuration"
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
                        <span x-show="!copied" class="text-sm font-medium text-gray-700 dark:text-gray-300">Click to copy</span>
                        <span x-show="copied" class="text-sm font-medium text-green-600 dark:text-green-400">Copied!</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced configuration toggle -->
        <div>
            <button
                wire:click="toggleAdvancedConfig"
                class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-500 transition-colors duration-200"
                aria-expanded="{{ $showAdvancedConfig ? 'true' : 'false' }}"
                aria-controls="advanced-config-details"
            >
                <svg class="w-4 h-4 transition-transform duration-200 {{ $showAdvancedConfig ? 'rotate-90' : '' }}" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span>Advanced Configuration Details</span>
            </button>
            @if($showAdvancedConfig)
                <div id="advanced-config-details" class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Server</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ $configData['base']['server'] }}</div>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Port</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ $configData['base']['port'] }}</div>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Protocol</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ strtoupper($configData['base']['protocol']) }}</div>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Security</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ strtoupper($configData['base']['security']) }}</div>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">UUID</label>
                            <div class="text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $configData['base']['uuid'] }}</div>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">SNI</label>
                            <div class="text-gray-600 dark:text-gray-400">{{ $configData['base']['sni'] ?: 'Not set' }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- Action Buttons -->
    <footer class="flex flex-col md:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gradient-to-t from-gray-50/60 dark:from-gray-900/60 to-white dark:to-gray-900">
        <div class="flex items-center gap-2">
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
        <div class="text-xs text-gray-500 dark:text-gray-400 text-right w-full md:w-auto">
            Last updated: {{ $clientStatus['last_connection'] ? $clientStatus['last_connection']->diffForHumans() : 'Never' }}
        </div>
    </footer>

    <!-- Copy to clipboard/download script -->
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
</section>
