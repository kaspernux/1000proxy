<section class="min-h-screen bg-gradient-to-b from-green-50 via-white to-green-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-10 px-2 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="w-full max-w-2xl mx-auto">
        <div class="bg-white/90 dark:bg-green-900 rounded-2xl shadow-2xl p-6 sm:p-10 border border-yellow-100 dark:border-green-800" x-data="topupForm()" x-init="init()">

                        {{-- Header --}}
                        <header class="text-center mb-8">
                            <h2 class="text-3xl sm:text-4xl font-extrabold text-green-700 dark:text-green-300 tracking-tight mb-2 drop-shadow-lg">Top-Up Wallet</h2>
                            <p class="text-base sm:text-lg text-green-800 dark:text-green-200">Choose cryptocurrency and deposit easily.</p>
                        </header>

                        {{-- Alerts --}}
                        @if (session()->has('success'))
                            <div x-data="{show: true}" x-show="show" x-init="setTimeout(() => show = false, 4000)" 
                                 class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 p-3 rounded mb-4 text-center font-semibold transition ease-in-out">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{-- Currency Selector --}}
                        <nav class="flex flex-wrap justify-center mb-8 gap-2" aria-label="Currency selector">
                            @foreach (['BTC', 'XMR', 'SOL'] as $coin)
                                <button type="button"
                                    class="px-4 sm:px-6 py-2 text-sm sm:text-lg rounded-full font-bold uppercase transition-all duration-300 border-2 border-double border-yellow-600 focus:outline-none"
                                    :class="currency === '{{ $coin }}' ? 'bg-yellow-600 text-green-900' : 'bg-green-900 text-white hover:bg-yellow-600 hover:text-green-900'"
                                    @click="switchCurrency('{{ $coin }}')">
                                    {{ $coin }}
                                </button>
                            @endforeach
                        </div>

                        {{-- QR Code --}}
                        <section class="flex flex-col items-center space-y-4 mb-8">
                            <div class="relative w-40 h-40 sm:w-48 sm:h-48 group cursor-pointer" @click="downloadQr" aria-label="Deposit QR Code">
                                <template x-if="loading">
                                    <div class="flex justify-center items-center w-full h-full">
                                        <svg class="animate-spin h-10 w-10 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </div>
                                </template>

                                <div class="relative w-full h-full" x-show="qrCode">
                                    <img 
                                        :src="qrCode" 
                                        alt="Deposit QR Code" 
                                        class="w-full h-full object-cover rounded-xl border-2 border-yellow-600 shadow-lg transition duration-300 ease-in-out hover:scale-105"
                                    />
                                </div>
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black bg-opacity-50 rounded-lg">
                                    <span class="text-yellow-400 text-sm font-bold">Click to Download</span>
                                </div>
                            </div>

                            <p class="text-base sm:text-lg text-green-800 dark:text-green-200 font-mono" x-text="'Deposit ' + currency "></p>                           
                            <p class="text-lg sm:text-xl font-bold text-yellow-600 dark:text-yellow-400" x-text="'Balance: $' + balance"></p>
                        </div>

                        <!-- Deposit Address -->
                        <section class="flex items-center mb-4 px-2 sm:px-10 w-full" aria-label="Deposit Address">
                            <div
                                class="w-full overflow-x-auto rounded-md border-2 border-double border-yellow-600 bg-green-800 cursor-pointer"
                                @click="copyAddress"
                                title="Click to copy address"
                                tabindex="0"
                                aria-label="Copy deposit address"
                            >
                                <div
                                    class="inline-block whitespace-nowrap text-white font-bold text-sm sm:text-base py-3 px-4 select-none"
                                    x-text="depositAddress"
                                ></div>
                            </div>
                        </section>


                        {{-- Copy Address Button --}}


                        {{-- Top-Up Form --}}
                        <form wire:submit.prevent="topUp" class="space-y-8 px-2 sm:px-10" autocomplete="off">
                            <input type="hidden" wire:model="currency" x-bind:value="currency" />

                            <div class="space-y-2 mb-4">
                                <label for="amount" class="block text-green-700 dark:text-green-300 text-sm sm:text-md text-center font-bold">Amount</label>
                                <input type="number" id="amount" wire:model.defer="amount" step="0.00000001" required
                                    class="w-full rounded-md bg-green-50 dark:bg-green-950 border-2 border-yellow-600 text-green-900 dark:text-white py-3 px-4 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition" />
                                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2 mt-6">
                                <label for="reference" class="block text-green-700 dark:text-green-300 text-sm sm:text-md text-center font-bold">Transaction Reference</label>
                                <input type="text" id="reference" wire:model.defer="reference"
                                    class="w-full rounded-md bg-green-50 dark:bg-green-950 border-2 border-yellow-600 text-green-900 dark:text-white py-3 px-4 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition" />
                                @error('reference') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <button type="submit"
                                    class="w-full mt-8 text-base sm:text-lg flex justify-center items-center gap-2 bg-gradient-to-r from-yellow-500 to-green-400 hover:from-yellow-600 hover:to-green-600 text-green-900 font-bold py-3 px-6 rounded-xl shadow-lg transition-all duration-200">
                                    <svg x-show="loading" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-show="!loading">Top-Up Now</span>
                                </button>
                            </div>
                        </form>

                        <footer class="text-center pt-6 mb-4">
                            <a href="{{ url()->previous() }}" class="text-sm font-bold text-yellow-600 dark:text-yellow-400 hover:underline">
                                ← Back to Wallet
                            </a>
                        </footer>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function topupForm() {
    return {
        currency: '{{ strtoupper($currency ?? 'BTC') }}',
        loading: false,
        success: false,
        qrCodes: {
            BTC: "{{ $wallet->btc_qr ? asset('storage/' . $wallet->btc_qr) : '' }}",
            XMR: "{{ $wallet->xmr_qr ? asset('storage/' . $wallet->xmr_qr) : '' }}",
            SOL: "{{ $wallet->sol_qr ? asset('storage/' . $wallet->sol_qr) : '' }}",
        },
        addresses: {
            BTC: "{{ $wallet->btc_address }}",
            XMR: "{{ $wallet->xmr_address }}",
            SOL: "{{ $wallet->sol_address }}",
        },
        balance: '{{ number_format($wallet->balance ?? 0, 2) }}',
        qrCode: '',
        depositAddress: '',

        init() {
            this.updateQr();
            setInterval(() => {
                $wire.call('render');
            }, 10000);

            $wire.on('submitStarted', () => {
                this.loading = true;
                this.success = false;
            });
            $wire.on('submitEnded', () => {
                this.loading = false;
                this.success = true;
                setTimeout(() => this.success = false, 2000);
            });
        },

        switchCurrency(newCurrency) {
            this.currency = newCurrency;
            this.updateQr();
        },

        updateQr() {
            this.loading = true;
            this.qrCode = this.qrCodes[this.currency] || '';
            this.depositAddress = this.addresses[this.currency] || '';
            this.loading = false;
        },

        downloadQr() {
            if (!this.qrCode) return;
            const link = document.createElement('a');
            link.href = this.qrCode;
            link.download = this.currency + '_wallet_qr.png';
            link.click();
        },

        copyAddress() {
            navigator.clipboard.writeText(this.depositAddress).then(() => {
                alert('Address copied to clipboard!');
            });
        }
    }
}
</script>
