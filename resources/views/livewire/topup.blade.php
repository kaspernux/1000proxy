<section class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 py-10 px-2 sm:px-6 lg:px-8 flex items-center justify-center relative overflow-hidden">
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

    <div class="w-full max-w-2xl mx-auto relative z-10">
        <div class="bg-white/5 backdrop-blur-lg rounded-3xl shadow-2xl p-6 sm:p-10 border border-white/10 relative overflow-hidden" x-data="topupForm()" x-init="init()">
            
            <!-- Background decoration -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-yellow-400/10 to-blue-400/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-blue-400/10 to-yellow-400/10 rounded-full blur-2xl"></div>

            <div class="relative z-10">
                {{-- Enhanced Header --}}
                <header class="text-center mb-10">
                    <!-- Breadcrumb -->
                    <nav class="flex justify-center items-center space-x-2 text-sm mb-6">
                        <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="text-blue-400 font-medium">Wallet Top-up</span>
                    </nav>

                    <h2 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight mb-4 leading-tight">
                        <span class="bg-gradient-to-r from-blue-400 via-yellow-400 to-blue-500 bg-clip-text text-transparent">
                            Top-Up Wallet
                        </span>
                    </h2>
                    <p class="text-base sm:text-lg text-gray-300 font-light">Choose your preferred cryptocurrency and deposit funds securely</p>
                </header>

                {{-- Enhanced Alerts --}}
                @if (session()->has('success'))
                    <div x-data="{show: true}" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                         class="bg-gradient-to-r from-green-500/20 to-blue-500/20 border border-green-400/50 backdrop-blur-sm rounded-2xl p-4 mb-6 shadow-lg">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-100 font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Enhanced Currency Selector --}}
                <nav class="flex flex-wrap justify-center mb-10 gap-4" aria-label="Currency selector">
                    @foreach (['BTC', 'XMR', 'SOL'] as $coin)
                        <button type="button"
                            class="group px-6 sm:px-8 py-3 text-sm sm:text-lg rounded-2xl font-bold uppercase transition-all duration-300 border-2 focus:outline-none shadow-lg hover:shadow-xl transform hover:scale-105"
                            :class="currency === '{{ $coin }}' ? 'bg-gradient-to-r from-blue-500 to-yellow-500 text-white border-blue-400/50 shadow-blue-500/25' : 'bg-white/10 backdrop-blur-sm text-white border-white/20 hover:bg-white/20 hover:border-blue-400/50'"
                            @click="switchCurrency('{{ $coin }}')">
                            <span class="relative z-10">{{ $coin }}</span>
                        </button>
                    @endforeach
                </nav>

                {{-- Enhanced QR Code Section --}}
                <section class="flex flex-col items-center space-y-6 mb-10">
                    <div class="relative w-48 h-48 sm:w-56 sm:h-56 group cursor-pointer" @click="downloadQr" aria-label="Deposit QR Code">
                        <template x-if="loading">
                            <div class="flex justify-center items-center w-full h-full bg-white/10 backdrop-blur-sm rounded-3xl border border-white/20">
                                <div class="relative">
                                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-400 border-t-transparent"></div>
                                    <div class="absolute inset-0 rounded-full h-12 w-12 border-4 border-yellow-400 border-t-transparent animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
                                </div>
                            </div>
                        </template>

                        <div class="relative w-full h-full" x-show="qrCode">
                            <img 
                                :src="qrCode" 
                                alt="Deposit QR Code" 
                                class="w-full h-full object-cover rounded-3xl border-2 border-blue-400/50 shadow-2xl transition duration-300 ease-in-out group-hover:scale-105 group-hover:shadow-blue-500/25"
                            />
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 bg-black/50 rounded-3xl backdrop-blur-sm">
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
