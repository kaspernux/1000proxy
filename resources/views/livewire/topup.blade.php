<div x-data="{ currency: 'BTC' }" class="w-full py-10 bg-gradient-to-r from-green-900 to-green-600 min-h-screen">
    <div class="max-w-2xl mx-auto px-4">
        <h2 class="text-4xl font-bold font-mono text-white text-center mb-6">Top-Up Your <span class="text-yellow-400">Wallet</span></h2>

        {{-- Alerts --}}
        @if (session()->has('success'))
            <div class="bg-green-500 text-white p-3 rounded mb-4 text-center font-mono">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('warning'))
            <div class="bg-yellow-500 text-white p-3 rounded mb-4 text-center font-mono">
                {{ session('warning') }}
            </div>
        @endif
        @error('amount') <div class="text-red-500 text-sm font-mono mb-2">{{ $message }}</div> @enderror
        @error('reference') <div class="text-red-500 text-sm font-mono mb-2">{{ $message }}</div> @enderror

        {{-- Currency Tabs --}}
        <div class="flex justify-center mb-6 space-x-4">
            <button @click="currency = 'BTC'"
                :class="currency === 'BTC' ? 'bg-yellow-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white'"
                class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 font-mono">Bitcoin</button>
            <button @click="currency = 'XMR'"
                :class="currency === 'XMR' ? 'bg-purple-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white'"
                class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 font-mono">Monero</button>
            <button @click="currency = 'SOL'"
                :class="currency === 'SOL' ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white'"
                class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 font-mono">Solana</button>
        </div>

        {{-- Top-up Form --}}
        <form wire:submit.prevent="topUp" class="space-y-6 bg-white dark:bg-gray-900 p-6 rounded-lg shadow-xl font-mono">
            <div x-show="currency === 'BTC'" x-transition>
                <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-yellow-700 dark:text-yellow-300 mb-2">Top-Up with Bitcoin</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Send BTC to your personal wallet address.</p>
                </div>
            </div>

            <div x-show="currency === 'XMR'" x-transition>
                <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-purple-700 dark:text-purple-300 mb-2">Top-Up with Monero</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Your payment ID is used for tracking.</p>
                </div>
            </div>

            <div x-show="currency === 'SOL'" x-transition>
                <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-blue-700 dark:text-blue-300 mb-2">Top-Up with Solana</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Solana payments are fast and low cost.</p>
                </div>
            </div>

            <input type="hidden" wire:model="currency" x-bind:value="currency" />

            <div>
                <label class="block text-gray-700 dark:text-white mb-1 font-semibold">Amount</label>
                <input type="number" wire:model.defer="amount" step="0.00000001"
                    class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
            </div>

            <div>
                <label class="block text-gray-700 dark:text-white mb-1 font-semibold">Transaction Reference</label>
                <input type="text" wire:model.defer="reference"
                    class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-yellow-600 text-white py-3 rounded-lg font-bold transition-all duration-300">
                Top-Up Now
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('topup', () => ({
            currency: 'BTC',
            init() {
                this.$watch('currency', value => {
                    console.log('Currency changed to:', value);
                });
            }
        }));
    });
</script>
