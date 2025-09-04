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

    <div class="w-full max-w-3xl mx-auto relative z-10">
        <div class="bg-white/5 backdrop-blur-lg rounded-3xl shadow-2xl p-6 sm:p-10 border border-white/10 relative overflow-hidden">
            
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
                    <p class="text-base sm:text-lg text-gray-300 font-light">Enter an amount, select a payment method, and choose your currency</p>
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

                {{-- Top-Up Form (amount + payment method + currency) --}}
                <form wire:submit.prevent="submitTopup" class="space-y-8" autocomplete="off">
                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-white font-medium mb-2">Amount</label>
                        <input type="number" id="amount" wire:model.defer="amount" min="1" step="0.01" required
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all duration-200" placeholder="Enter amount (USD)">
                        @error('amount') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Payment Methods (exclude wallet) -->
                    <div>
                        <label class="block text-white font-medium mb-3">Payment Method</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @php($methods = $availableMethods ?? ['crypto'])
                            @foreach($methods as $method)
                            <button type="button" wire:click="$set('payment_method', '{{ $method }}')"
                                    class="relative p-4 rounded-xl border-2 transition-all duration-300 text-left
                                    {{ $payment_method === $method ? 'border-green-500 bg-green-500/20' : 'border-white/20 bg-white/5 hover:border-green-400/50 hover:bg-green-400/10' }}">
                                <div class="flex items-center space-x-3">
                                    @switch($method)
                                        @case('crypto')
                                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                                <span class="text-white font-bold">₿</span>
                                            </div>
                                            <div>
                                                <div class="text-white font-semibold">Crypto</div>
                                                <div class="text-gray-400 text-xs">Processed by NowPayments</div>
                                            </div>
                                            @break
                                        @case('stripe')
                                            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                                                <x-custom-icon name="credit-card" class="w-5 h-5 text-white" />
                                            </div>
                                            <div>
                                                <div class="text-white font-semibold">Credit Card</div>
                                                <div class="text-gray-400 text-xs">Stripe</div>
                                            </div>
                                            @break
                                        @case('paypal')
                                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                                <span class="text-white font-bold">P</span>
                                            </div>
                                            <div>
                                                <div class="text-white font-semibold">PayPal</div>
                                                <div class="text-gray-400 text-xs">Worldwide</div>
                                            </div>
                                            @break
                                        @case('mir')
                                            <div class="w-10 h-10 bg-blue-700 rounded-lg flex items-center justify-center">
                                                <span class="text-white font-bold">₽</span>
                                            </div>
                                            <div>
                                                <div class="text-white font-semibold">MIR</div>
                                                <div class="text-gray-400 text-xs">Russian Rubles</div>
                                            </div>
                                            @break
                                    @endswitch
                                </div>
                                @if($payment_method === $method)
                                    <div class="absolute -top-2 -right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                        <x-custom-icon name="check" class="w-3 h-3 text-white" />
                                    </div>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Payment Details: currency selection -->
                    <div class="bg-white/5 rounded-2xl p-5 border border-white/10">
                        @if($payment_method === 'crypto')
                            <h3 class="text-orange-400 font-bold mb-3">Cryptocurrency Payment</h3>
                            <label class="block text-white font-medium mb-2">Choose Cryptocurrency</label>
                            <select wire:model="crypto_currency" class="w-full px-4 py-3 bg-white border border-white/20 rounded-xl text-black focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                <option value="" class="text-gray-500">Select coin</option>
                                <option value="btc" class="text-black">Bitcoin (BTC)</option>
                                <option value="eth" class="text-black">Ethereum (ETH)</option>
                                <option value="xmr" class="text-black">Monero (XMR)</option>
                                <option value="ltc" class="text-black">Litecoin (LTC)</option>
                                <option value="doge" class="text-black">Dogecoin (DOGE)</option>
                                <option value="ada" class="text-black">Cardano (ADA)</option>
                                <option value="dot" class="text-black">Polkadot (DOT)</option>
                                <option value="sol" class="text-black">Solana (SOL)</option>
                                <option value="usdt" class="text-black">Tether (USDT)</option>
                                <option value="usdc" class="text-black">USD Coin (USDC)</option>
                                <option value="bnb" class="text-black">Binance Coin (BNB)</option>
                            </select>
                            @error('crypto_currency') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            <p class="text-green-200 text-xs mt-3 flex items-center"><x-custom-icon name="shield-check" class="w-4 h-4 mr-1" /> Processed securely via NowPayments</p>
                        @else
                            <h3 class="text-blue-400 font-bold mb-3">Currency</h3>
                            <label class="block text-white font-medium mb-2">Choose Currency</label>
                            <select wire:model="fiat_currency" class="w-full px-4 py-3 bg-white border border-white/20 rounded-xl text-black focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="USD" class="text-black">USD</option>
                                <option value="EUR" class="text-black">EUR</option>
                                <option value="GBP" class="text-black">GBP</option>
                                <option value="RUB" class="text-black">RUB</option>
                            </select>
                            @error('fiat_currency') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        @endif
                    </div>

                    <!-- Optional Reference -->
                    <div>
                        <label for="reference" class="block text-white font-medium mb-2">Transaction Reference (optional)</label>
                        <input type="text" id="reference" wire:model.defer="reference" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" placeholder="Auto-generated if left empty">
                        @error('reference') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 flex items-center justify-center">
                            <x-custom-icon name="lock-closed" class="w-5 h-5 mr-2" />
                            Pay & Top Up
                        </button>
                    </div>
                </form>

                {{-- Pending Payments / Live Status --}}
                @if(!empty($pendingTransactions))
                    <div class="mt-6 bg-white/3 rounded-2xl p-4 border border-white/10">
                        <h4 class="text-white font-semibold mb-2">Pending Payments</h4>
                        <div class="space-y-3">
                            @foreach($pendingTransactions as $pt)
                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-lg">
                                    <div>
                                        <div class="text-sm text-gray-300">TxID:
                                            @if(!empty($pt->payment_url))
                                                <a href="{{ $pt->payment_url }}" target="_blank" class="text-yellow-300 hover:underline">{{ $pt->payment_id }}</a>
                                            @else
                                                <span class="text-yellow-300">{{ $pt->payment_id }}</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400">Amount: {{ $pt->amount }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-white">Status: <span class="font-semibold">{{ ucfirst($pt->status) }}</span></div>
                                        <div class="text-xs text-gray-400 mt-1">Progress: 
                                            @php
                                                $percent = match(strtolower($pt->status)) {
                                                    'waiting' => 5,
                                                    'pending' => 25,
                                                    'confirming' => 60,
                                                    'finished' => 100,
                                                    'confirmed' => 100,
                                                    default => 0,
                                                };
                                            @endphp
                                            <div class="w-40 bg-white/10 rounded-full h-2 mt-1 overflow-hidden">
                                                <div style="width: {{ $percent }}%" class="h-2 bg-green-400"></div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Poll this payment id every 8s while visible --}}
                                    <div wire:poll.visible.8s="pollPaymentStatus('{{ $pt->payment_id }}')"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <footer class="text-center pt-6 mb-4">
                    <a href="{{ url()->previous() }}" class="text-sm font-bold text-yellow-600 dark:text-yellow-400 hover:underline">
                        ← Back to Wallet
                    </a>
                </footer>
            </div>
        </div>
    </div>
</section>
