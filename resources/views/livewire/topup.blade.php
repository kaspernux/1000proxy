<x-layouts.app>
<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <section class="bg-gradient-to-r from-green-900 to-green-600 font-mono rounded-lg py-8 px-6">
        <div class="mx-auto max-w-3xl">
            <div class="flex justify-center">
                <div class="w-full max-w-lg mx-8">
                    <div class="border-2 border-double rounded-2xl border-yellow-600 bg-green-900 p-8 shadow-2xl" x-data="topupForm()" x-init="init()">

                        {{-- Header --}}
                        <div class="text-center space-y-3 mb-8">
                            <h2 class="mt-4 text-4xl font-bold text-white tracking-wide">Top-Up Wallet</h2>
                            <p class="text-lg text-green-300">Choose cryptocurrency and deposit easily.</p>
                        </div>

                        {{-- Alerts --}}
                        @if (session()->has('success'))
                            <div x-data="{show: true}" x-show="show" x-init="setTimeout(() => show = false, 4000)" 
                                 class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 p-3 rounded mb-4 text-center font-semibold transition ease-in-out">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{-- Currency Selector --}}
                        <div class="flex justify-center mb-8 space-x-4">
                            @foreach (['BTC', 'XMR', 'SOL'] as $coin)
                                <button type="button"
                                    class="px-6 py-2 text-lg rounded-full font-bold uppercase transition-all duration-300 border-2 border-double border-yellow-600 focus:outline-none"
                                    :class="currency === '{{ $coin }}' ? 'bg-yellow-600 text-green-900' : 'bg-green-900 text-white hover:bg-yellow-600 hover:text-green-900'"
                                    @click="switchCurrency('{{ $coin }}')">
                                    {{ $coin }}
                                </button>
                            @endforeach
                        </div>

                        {{-- QR Code --}}
                        <div class="flex flex-col items-center space-y-4 mb-8">
                            <div class="relative w-48 h-48 group cursor-pointer" @click="downloadQr">
                                <template x-if="loading">
                                    <div class="flex justify-center items-center w-full h-full">
                                        <svg class="animate-spin h-10 w-10 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </div>
                                </template>

                                <div class="relative w-48 h-48 group" x-show="qrCode">
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

                            <p class="text-lg text-green-300 font-mono" x-text="'Deposit ' + currency "></p>                           
                            <p class="text-xl font-bold text-yellow-400" x-text="'Balance: $' + balance"></p>
                        </div>

                        <!-- Deposit Address -->
                        <div class="flex text-xl font-bold items-center  mb-4 px-10">
                            <input type="text" x-model="depositAddress" readonly
                                class="w-full bg-green-800 border-2 border-double text-center border-yellow-600 text-white font-bold items-center justify-center py-3 px-4 rounded-md" />  
                        </div>

    

                        {{-- Top-Up Form --}}
                        <form wire:submit.prevent="topUp" class="space-y-8 px-10">
                            <input type="hidden" wire:model="currency" x-bind:value="currency" />

                            <div class="space-y-2 mb-4">
                                <label for="amount" class="block text-white text-md text-center font-bold text-yellow-400">Amount</label>
                                <input type="number" id="amount" wire:model.defer="amount" step="0.00000001" required
                                    class="w-full rounded-md bg-green-800 border-2 border-double border-yellow-600 text-white py-3 px-4" />
                                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2 mt-6">
                                <label for="reference" class="block text-white text-md text-center font-bold text-yellow-400">Transaction Reference</label>
                                <input type="text" id="reference" wire:model.defer="reference"
                                    class="w-full rounded-md bg-green-800 border-2 border-double border-yellow-600 text-white py-3 px-4" />
                                @error('reference') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <button type="submit"
                                    class="w-full mt-8 text-lg flex justify-center items-center gap-2 bg-yellow-600 hover:bg-yellow-500 text-green-900 font-bold py-3 px-6 rounded-xl">
                                    <svg x-show="loading" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-show="!loading">Top-Up Now</span>
                                </button>
                            </div>
                        </form>

                        <div class="text-center pt-6 mb-4">
                            <a href="{{ url()->previous() }}" class="text-sm font-bold text-yellow-400 hover:underline">
                                ← Back to Wallet
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- SCRIPT --}}
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



</x-layouts.app>
