{{-- Modern Telegram Link Page matching homepage design --}}
<div class="relative bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 overflow-hidden min-h-screen flex items-center">
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

    <div class="relative z-10 container mx-auto px-4 max-w-7xl">
        <main class="w-full max-w-lg mx-auto">
            <div class="bg-gradient-to-br from-gray-800/80 to-gray-900/80 backdrop-blur-xl border border-blue-500/30 rounded-2xl shadow-2xl">
                <div class="p-8">
                    {{-- Header --}}
                    <div class="text-center mb-8">
                        <div class="mb-6">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-500 to-yellow-500 rounded-2xl flex items-center justify-center shadow-2xl">
                                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.05 1.577c-.393-.016-.78.08-1.127.283L.85 8.343c-.348.204-.673.48-.953.816-.28.335-.483.719-.606 1.139-.123.42-.166.86-.12 1.296.046.436.162.86.34 1.252.178.393.407.748.677 1.053.27.305.578.556.912.742.334.186.69.305 1.056.352.367.047.737.024 1.095-.068l5.227-1.336 5.227 1.336c.358.092.728.115 1.095.068.366-.047.722-.166 1.056-.352.334-.186.642-.437.912-.742.27-.305.5-.66.677-1.053.178-.392.294-.816.34-1.252.046-.436.003-.876-.12-1.296-.123-.42-.326-.804-.606-1.139-.28-.336-.605-.612-.953-.816L22.05 1.577z"/>
                                </svg>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold text-white mb-3 bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent">
                            Telegram Integration
                        </h1>
                        <p class="text-gray-300 text-lg">
                            Connect your Telegram account to receive notifications and manage your proxies directly from Telegram.
                        </p>
                    </div>

                    {{-- Controls & Status --}}
                    <div class="flex items-center justify-end mb-6">
                        <button 
                            wire:click="refreshStatus" 
                            class="inline-flex items-center px-4 py-2 bg-gray-700/50 border border-gray-600 rounded-lg text-white hover:bg-gray-600/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all duration-200 backdrop-blur-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>

                    {{-- Flash Messages --}}
                    @if (session()->has('success'))
                        <div class="mb-6 p-4 bg-gradient-to-r from-green-500/20 to-green-600/20 border border-green-500/50 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <p class="text-green-100 font-medium">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="mb-6 p-4 bg-gradient-to-r from-red-500/20 to-red-600/20 border border-red-500/50 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <p class="text-red-100 font-medium">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    @if (session()->has('info'))
                        <div class="mb-6 p-4 bg-gradient-to-r from-blue-500/20 to-blue-600/20 border border-blue-500/50 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-blue-100 font-medium">{{ session('info') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($isLinked)
                        <div class="flex items-center justify-between p-6 bg-green-500/20 border border-green-500/50 rounded-xl backdrop-blur-sm">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M22.05 1.577c-.393-.016-.78.08-1.127.283L.85 8.343c-.348.204-.673.48-.953.816-.28.335-.483.719-.606 1.139-.123.42-.166.86-.12 1.296.046.436.162.86.34 1.252.178.393.407.748.677 1.053.27.305.578.556.912.742.334.186.69.305 1.056.352.367.047.737.024 1.095-.068l5.227-1.336 5.227 1.336c.358.092.728.115 1.095.068.366-.047.722-.166 1.056-.352.334-.186.642-.437.912-.742.27-.305.5-.66.677-1.053.178-.392.294-.816.34-1.252.046-.436.003-.876-.12-1.296-.123-.42-.326-.804-.606-1.139-.28-.336-.605-.612-.953-.816L22.05 1.577z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-semibold text-white">
                                        Telegram Account Linked
                                    </p>
                                    <p class="text-sm text-green-100">
                                        Connected as: {{ $telegramInfo['display_name'] }}
                                        @if($telegramInfo['username'])
                                            ({{ '@' . $telegramInfo['username'] }})
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <button 
                                wire:click="unlinkTelegram" 
                                wire:confirm="Are you sure you want to unlink your Telegram account?"
                                class="inline-flex items-center px-4 py-2 bg-red-500/20 border border-red-400/50 rounded-lg text-red-100 hover:bg-red-500/30 focus:outline-none focus:ring-2 focus:ring-red-500/50 transition-all duration-200 backdrop-blur-sm">
                                Unlink
                            </button>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-gradient-to-br from-blue-500/20 to-yellow-500/20 border border-blue-500/30 mb-6 backdrop-blur-sm">
                                <svg class="w-8 h-8 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.05 1.577c-.393-.016-.78.08-1.127.283L.85 8.343c-.348.204-.673.48-.953.816-.28.335-.483.719-.606 1.139-.123.42-.166.86-.12 1.296.046.436.162.86.34 1.252.178.393.407.748.677 1.053.27.305.578.556.912.742.334.186.69.305 1.056.352.367.047.737.024 1.095-.068l5.227-1.336 5.227 1.336c.358.092.728.115 1.095.068.366-.047.722-.166 1.056-.352.334-.186.642-.437.912-.742.27-.305.5-.66.677-1.053.178-.392.294-.816.34-1.252.046-.436.003-.876-.12-1.296-.123-.42-.326-.804-.606-1.139-.28-.336-.605-.612-.953-.816L22.05 1.577z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-white mb-3">Link Your Telegram Account</h3>
                            <p class="text-gray-400 mb-6">
                                Connect your Telegram account to receive notifications and manage your proxies directly from Telegram.
                            </p>
                            @if(!$showLinkingCode)
                                <button 
                                    wire:click="generateLinkingCode" 
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 transform focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22.05 1.577c-.393-.016-.78.08-1.127.283L.85 8.343c-.348.204-.673.48-.953.816-.28.335-.483.719-.606 1.139-.123.42-.166.86-.12 1.296.046.436.162.86.34 1.252.178.393.407.748.677 1.053.27.305.578.556.912.742.334.186.69.305 1.056.352.367.047.737.024 1.095-.068l5.227-1.336 5.227 1.336c.358.092.728.115 1.095.068.366-.047.722-.166 1.056-.352.334-.186.642-.437.912-.742.27-.305.5-.66.677-1.053.178-.392.294-.816.34-1.252.046-.436.003-.876-.12-1.296-.123-.42-.326-.804-.606-1.139-.28-.336-.605-.612-.953-.816L22.05 1.577z"/>
                                    </svg>
                                    Generate Linking Code
                                </button>
                            @else
                                <div class="bg-blue-500/20 border border-blue-500/50 rounded-xl p-6 backdrop-blur-sm">
                                    <h4 class="text-lg font-semibold text-white mb-4">Linking Code Generated</h4>
                                    <div class="bg-gray-800/50 border border-blue-400/50 rounded-xl p-4 mb-4 backdrop-blur-sm">
                                        <p class="text-3xl font-mono font-bold text-blue-400 text-center tracking-wider">{{ $linkingCode }}</p>
                                    </div>
                                    <div class="text-sm text-blue-100 space-y-3 text-left">
                                        <p class="font-semibold text-white">Instructions:</p>
                                        <ol class="list-decimal list-inside space-y-2 text-gray-300">
                                            <li>Open Telegram and search for our bot: <span class="font-semibold text-blue-400">@{{ config('app.name') }}Bot</span></li>
                                            <li>Start a conversation with the bot by clicking "Start"</li>
                                            <li>Send the linking code above to the bot</li>
                                            <li>Your account will be linked automatically</li>
                                        </ol>
                                    </div>
                                    <div class="mt-6 flex space-x-3">
                                        <button 
                                            wire:click="cancelLinking"
                                            class="flex-1 px-4 py-2 bg-gray-700/50 border border-gray-600 rounded-lg text-white hover:bg-gray-600/50 focus:outline-none focus:ring-2 focus:ring-gray-500/50 transition-all duration-200 backdrop-blur-sm">
                                            Cancel
                                        </button>
                                        <button 
                                            wire:click="refreshStatus"
                                            class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 text-white font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                                            Check Status
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                    {{-- Telegram Features Section --}}
                    <div class="mt-8 p-6 bg-gradient-to-br from-gray-800/50 to-gray-900/50 border border-gray-700/50 rounded-xl backdrop-blur-sm">
                        <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Telegram Features
                        </h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Check wallet balance
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Browse servers
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Place orders
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                View order history
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Get support
                            </div>
                            <div class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Receive notifications
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('telegram-linking-code-generated', (event) => {
        console.log('Linking code generated:', event.code);
        // You can add additional UI feedback here
    });
    
    Livewire.on('telegram-unlinked', (event) => {
        console.log('Telegram unlinked:', event.message);
        // You can add additional UI feedback here
    });
});
</script>
