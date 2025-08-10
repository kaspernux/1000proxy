<div class="py-16">
    <section class="payment-processor bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 rounded-3xl shadow-4xl max-w-xl mx-auto my-12 p-0 border border-blue-900/30">
    <!-- Payment Progress Indicator -->
    <header class="px-8 pt-8 pb-6 border-b border-blue-900/20">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-2xl font-extrabold text-white drop-shadow-lg tracking-wide">Payment Process</h3>
            <span class="text-sm text-blue-200">
                {{ $paymentProgress['progress'] }}% Complete
            </span>
        </div>
        <div class="w-full bg-blue-900/30 rounded-full h-2">
            <div
                class="bg-gradient-to-r from-blue-500 via-blue-400 to-yellow-400 h-2 rounded-full transition-all duration-500 ease-out"
                style="width: {{ $paymentProgress['progress'] }}%"
            ></div>
        </div>
    <div class="mt-2 text-sm text-blue-100">
            {{ $paymentProgress['steps'][$paymentProgress['current']] }}
        </div>
    </header>

    <!-- Order Summary -->
    @if($order)
        <section class="px-8 py-6 bg-gradient-to-r from-blue-900/10 via-gray-900/10 to-blue-900/10 border-b border-blue-900/20 rounded-t-3xl">
            <h4 class="font-semibold text-white mb-3 drop-shadow">Order Summary</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-blue-200">Order #{{ $order->id }}</span>
                    <span class="font-semibold text-white">${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->tax_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-blue-200">Tax</span>
                        <span class="text-white">${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                @endif
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-green-400">
                        <span>Discount</span>
                        <span>-${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-bold text-lg border-t pt-2">
                    <span class="text-yellow-300">Total</span>
                    <span class="text-yellow-300">${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </section>
    @endif

    <!-- Payment Step Content -->
    @if($paymentStep === 'select_gateway')
    <section class="px-8 py-8">
            <h4 class="font-semibold text-white mb-4 drop-shadow">Select Payment Method</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($availableGateways as $gatewayKey => $gateway)
                    @if($gateway['enabled'])
                        <div
                            wire:click="selectGateway('{{ $gatewayKey }}')"
                            class="payment-gateway-option p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 {{ $selectedGateway === $gatewayKey ? 'border-yellow-400 bg-gradient-to-r from-blue-900/40 to-yellow-400/10' : 'border-blue-900/30 bg-gradient-to-r from-gray-900/40 to-blue-900/10 hover:border-blue-400' }}"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="text-2xl">{!! $gateway['icon'] ?? '' !!}</span>
                                <div class="flex-1">
                                    <div class="font-semibold text-white">
                                        {{ $gateway['name'] ?? ucfirst($gatewayKey) }}
                                    </div>
                                    <div class="text-sm text-blue-200">
                                        {{ $gateway['description'] ?? '' }}
                                    </div>
                                    @if(isset($gateway['fee']) && $gateway['fee'] > 0)
                                        <div class="text-xs text-blue-300">Fee: ${{ number_format($gateway['fee'], 2) }}</div>
                                    @endif
                                </div>
                                @if($selectedGateway === $gatewayKey)
                                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            <!-- Gateway-specific forms -->
            @if($selectedGateway === 'stripe')
                <div class="mt-6 p-4 border border-blue-900/30 bg-gradient-to-r from-blue-900/20 to-gray-900/10 rounded-xl">
                    <h5 class="font-semibold text-white mb-4 drop-shadow">Card Information</h5>
                    <div class="py-10">
                        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                                <div class="lg:col-span-2 space-y-6">
                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-white/10">
                                        <div class="flex items-center justify-between mb-4">
                                            <h1 class="text-2xl font-bold text-white flex items-center">
                                                <x-custom-icon name="credit-card" class="w-7 h-7 mr-3 text-green-400" />
                                                {{ $isWalletTopup ? 'Wallet Top-Up' : 'Payment' }}
                                            </h1>
                                            <div class="text-sm text-green-200">{{ $paymentProgress['progress'] }}% Complete</div>
                                        </div>
                                        <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden mb-6">
                                            <div class="h-2 bg-gradient-to-r from-green-500 via-green-400 to-green-300 rounded-full transition-all duration-500" style="width: {{ $paymentProgress['progress'] }}%"></div>
                                        </div>

                                        <!-- Gateway Selection (mirrors checkout style) -->
                                        <h2 class="text-lg font-semibold text-white mb-4 flex items-center">
                                            <x-custom-icon name="wallet" class="w-5 h-5 mr-2 text-green-400" />
                                            Select Payment Method
                                        </h2>
                                        @php($gatewaysList = $isWalletTopup ? ($activeTopupGateways ?? []) : array_keys($availableGateways))
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                            @foreach($gatewaysList as $g)
                                                @php($info = $availableGateways[$g] ?? null)
                                                @if(!$info || empty($info['enabled'])) @continue @endif
                                                <button wire:click="selectGateway('{{ $g }}')"
                                                        class="relative p-4 rounded-xl border-2 transition-all duration-300 text-left
                                                            {{ $selectedGateway === $g ? 'border-green-500 bg-green-500/15' : 'border-white/15 bg-white/5 hover:border-green-400/50 hover:bg-green-400/10' }}">
                                                    <div class="text-center">
                                                        @switch($g)
                                                            @case('nowpayments')
                                                                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-2">
                                                                    <span class="text-white font-bold text-lg">₿</span>
                                                                </div>
                                                                <h3 class="text-white text-sm font-medium">Cryptocurrency</h3>
                                                                <p class="text-gray-400 text-xs mt-1">BTC, XMR, etc.</p>
                                                                @break
                                                            @case('stripe')
                                                                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mx-auto mb-2">
                                                                    <x-custom-icon name="credit-card" class="w-6 h-6 text-white" />
                                                                </div>
                                                                <h3 class="text-white text-sm font-medium">Credit Card</h3>
                                                                <p class="text-gray-400 text-xs mt-1">Visa, Mastercard</p>
                                                                @break
                                                            @case('mir')
                                                                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-2">
                                                                    <span class="text-white font-bold text-lg">₽</span>
                                                                </div>
                                                                <h3 class="text-white text-sm font-medium">MIR</h3>
                                                                <p class="text-gray-400 text-xs mt-1">Russian Cards</p>
                                                                @break
                                                            @case('paypal')
                                                                <div class="w-12 h-12 bg-blue-400 rounded-xl flex items-center justify-center mx-auto mb-2">
                                                                    <x-custom-icon name="banknotes" class="w-6 h-6 text-white" />
                                                                </div>
                                                                <h3 class="text-white text-sm font-medium">PayPal</h3>
                                                                <p class="text-gray-400 text-xs mt-1">Balance / Cards</p>
                                                                @break
                                                        @endswitch
                                                    </div>
                                                    @if($selectedGateway === $g)
                                                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                                            <x-custom-icon name="check" class="w-4 h-4 text-white" />
                                                        </div>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>

                                        <!-- Gateway Details -->
                                        <div class="bg-white/5 rounded-xl p-5 border border-white/10 mb-6" wire:key="gateway-details-{{ $selectedGateway }}">
                                            @switch($selectedGateway)
                                                @case('nowpayments')
                                                    <h3 class="text-lg sm:text-xl font-bold text-orange-400 mb-3 sm:mb-4">Cryptocurrency Payment</h3>
                                                    <p class="text-green-200 mb-3 sm:mb-4 text-sm sm:text-base">Pay with your preferred cryptocurrency using NowPayments.</p>
                                                    <div class="mb-4">
                                                        <label class="block text-white font-medium mb-2 text-sm sm:text-base">Select Cryptocurrency</label>
                                                        <select wire:model.live="selectedCrypto" wire:change="refreshCryptoEstimate"
                                                                class="w-full px-3 sm:px-4 py-2 sm:py-3 bg-white border border-white/20 rounded-lg sm:rounded-xl text-black focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm sm:text-base">
                                                            <option value="" class="text-gray-500">Choose a cryptocurrency</option>
                                                            @foreach($cryptoCurrencies as $code => $details)
                                                                <option value="{{ $code }}" class="text-black">{{ $details['name'] }} ({{ $code }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @if($selectedCrypto && $cryptoAmount > 0)
                                                        <div class="p-4 bg-white/5 rounded-lg sm:rounded-xl border border-white/10 mb-4 flex items-center justify-between">
                                                            <div>
                                                                <div class="text-orange-300 font-semibold text-sm sm:text-base">Estimated Amount</div>
                                                                <div class="text-gray-300 text-xs sm:text-sm">Based on current rate</div>
                                                            </div>
                                                            <div class="text-right">
                                                                <div class="text-orange-400 font-bold text-base sm:text-lg">{{ $cryptoAmount }} {{ $selectedCrypto }}</div>
                                                                <div class="text-gray-400 text-[10px]">Subject to network fees</div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="flex items-center text-green-300 text-xs sm:text-sm">
                                                        <x-custom-icon name="shield-check" class="w-3 h-3 sm:w-4 sm:h-4 mr-2 flex-shrink-0" />
                                                        <span>Secure and anonymous cryptocurrency payment via NowPayments</span>
                                                    </div>
                                                    @break
                                                @case('stripe')
                                                    <h3 class="text-purple-400 font-semibold mb-3">Card Information</h3>
                                                    <div class="grid gap-4 md:grid-cols-2">
                                                        <div class="md:col-span-2">
                                                            <label class="block text-xs font-medium text-gray-300 mb-1">Cardholder Name</label>
                                                            <input type="text" wire:model.live="cardholderName" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-purple-500" placeholder="John Doe" />
                                                            @error('cardholderName') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="md:col-span-2">
                                                            <label class="block text-xs font-medium text-gray-300 mb-1">Card Number</label>
                                                            <input type="text" wire:model.live="cardNumber" maxlength="19" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-purple-500 font-mono" placeholder="1234 5678 9012 3456" />
                                                            @error('cardNumber') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-300 mb-1">Month</label>
                                                            <select wire:model.live="expiryMonth" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-purple-500">
                                                                <option value="">MM</option>
                                                                @for($i=1;$i<=12;$i++) <option value="{{ $i }}">{{ str_pad($i,2,'0',STR_PAD_LEFT) }}</option> @endfor
                                                            </select>
                                                            @error('expiryMonth') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-300 mb-1">Year</label>
                                                            <select wire:model.live="expiryYear" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-purple-500">
                                                                <option value="">YYYY</option>
                                                                @for($i=date('Y');$i<=date('Y')+10;$i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                                                            </select>
                                                            @error('expiryYear') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-300 mb-1">CVC</label>
                                                            <input type="text" wire:model.live="cvc" maxlength="4" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-purple-500 font-mono" placeholder="123" />
                                                            @error('cvc') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                    @break
                                                @case('mir')
                                                    <h3 class="text-blue-400 font-semibold mb-3">MIR Payment</h3>
                                                    <p class="text-gray-300 text-sm mb-2">Process your payment securely using Russian MIR cards.</p>
                                                    @break
                                                @case('paypal')
                                                    <h3 class="text-blue-300 font-semibold mb-3">PayPal Payment</h3>
                                                    <p class="text-gray-300 text-sm mb-2">You will be redirected to PayPal to complete the payment.</p>
                                                    @break
                                            @endswitch
                                        </div>

                                        <div>
                                            <button wire:click="processPayment" wire:loading.attr="disabled" class="w-full py-4 rounded-xl bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-semibold shadow-lg hover:shadow-2xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span wire:loading.remove wire:target="processPayment">Pay ${{ number_format($paymentAmount,2) }}</span>
                                                <span wire:loading wire:target="processPayment" class="flex items-center justify-center">
                                                    <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                                                    Processing...
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary Sidebar -->
                                <div class="space-y-6">
                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-white/10">
                                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                                            <x-custom-icon name="chart-bar" class="w-5 h-5 mr-2 text-green-400" />Top-Up Summary
                                        </h3>
                                        <ul class="space-y-3 text-sm">
                                            <li class="flex justify-between"><span class="text-gray-300">Amount</span><span class="text-white font-medium">${{ number_format($paymentAmount,2) }} {{ $currency }}</span></li>
                                            <li class="flex justify-between"><span class="text-gray-300">Method</span><span class="text-white font-medium capitalize">{{ $selectedGateway === 'nowpayments' ? 'Crypto (' . $selectedCrypto . ')' : $selectedGateway }}</span></li>
                                            @if($selectedGateway === 'nowpayments' && $cryptoAmount > 0)
                                                <li class="flex justify-between"><span class="text-gray-300">Crypto Amount</span><span class="text-orange-300 font-medium">{{ $cryptoAmount }} {{ $selectedCrypto }}</span></li>
                                            @endif
                                        </ul>
                                        <div class="mt-4 p-3 bg-white/10 rounded-lg text-xs text-gray-300">
                                            Funds will be added to your wallet balance after confirmation. Crypto confirmations may take several minutes.
                                        </div>
                                    </div>

                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-white/10">
                                        <h3 class="text-sm font-semibold text-white mb-3 flex items-center"><x-custom-icon name="shield-check" class="w-4 h-4 mr-2 text-green-400" />Security</h3>
                                        <p class="text-xs text-gray-300 leading-relaxed">All transactions are encrypted. We never store sensitive card details. Cryptocurrency payments are processed via secure third-party gateways.</p>
                                                                </section>
                                </div>
                            </div>

                            @if($paymentStep === 'processing')
                                <div class="mt-10 text-center">
                                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-green-500 border-t-transparent mx-auto mb-4"></div>
                                    <p class="text-green-200 text-sm">Processing payment... please wait.</p>
                                </div>
                            @elseif($paymentStep === 'completed')
                                <div class="mt-10 text-center">
                                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <x-custom-icon name="check" class="w-8 h-8 text-white" />
                                    </div>
                                    <h2 class="text-2xl font-bold text-white mb-2">Top-Up Successful</h2>
                                    <p class="text-green-200 text-sm mb-4">Your wallet will reflect the new balance shortly.</p>
                                </div>
                            @elseif($paymentStep === 'failed')
                                <div class="mt-10 text-center">
                                    <div class="w-16 h-16 bg-red-600/30 border border-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <x-custom-icon name="x-mark" class="w-8 h-8 text-red-400" />
                                    </div>
                                    <h2 class="text-2xl font-bold text-red-300 mb-2">Payment Failed</h2>
                                    <p class="text-red-200 text-sm mb-4">Please try another payment method.</p>
                                    <button wire:click="retryPayment" class="px-5 py-3 rounded-lg bg-red-500 hover:bg-red-400 text-white font-medium transition">Retry</button>
                                </div>
                            @endif
                        </section>
                                placeholder="1234 5678 9012 3456"
                     </div>
                                class="w-full px-3 py-2 border border-blue-900/30 rounded-lg focus:ring-2 focus:ring-yellow-400 focus:border-transparent bg-gray-900 text-white font-mono"
                            >
                            @error('cardNumber') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Month
                                </label>
                                <select
                                    wire:model.live="expiryMonth"
                                    class="w-full px-3 py-2 border border-blue-900/30 rounded-lg focus:ring-2 focus:ring-yellow-400 focus:border-transparent bg-gray-900 text-white"
                                >
                                    <option value="">MM</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                    @endfor
                                </select>
                                @error('expiryMonth') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Year
                                </label>
                                <select
                                    wire:model.live="expiryYear"
                                    class="w-full px-3 py-2 border border-blue-900/30 rounded-lg focus:ring-2 focus:ring-yellow-400 focus:border-transparent bg-gray-900 text-white font-mono"
                                >
                                    <option value="">YYYY</option>
                                    @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                                @error('expiryYear') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    CVC
                                </label>
                                <input
                                    type="text"
                                    wire:model.live="cvc"
                                    placeholder="123"
                                    maxlength="4"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white font-mono"
                                >
                                @error('cvc') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($selectedGateway === 'nowpayments')
                <div class="mt-6 p-4 border border-yellow-400/30 bg-gradient-to-r from-yellow-900/20 to-blue-900/10 rounded-xl">
                    <label class="block text-sm font-medium text-blue-200 mb-2">Select Cryptocurrency</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($cryptoCurrencies as $crypto => $details)
                            <div
                                wire:click="selectCrypto('{{ $crypto }}')"
                                type="button"
                                class="crypto-option px-3 py-3 text-sm rounded-lg border flex flex-col items-center justify-center cursor-pointer transition-all duration-200 {{ $selectedCrypto === $crypto ? 'bg-yellow-400 text-gray-900 border-yellow-400' : 'bg-gray-800 text-blue-200 border-blue-900/40 hover:border-blue-400' }}"
                            >
                                <div class="text-2xl mb-1">{{ $details['symbol'] }}</div>
                                <div class="text-sm font-semibold">{{ $crypto }}</div>
                                <div class="text-xs text-gray-500">{{ $details['name'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    
                    
                    @if($selectedCrypto && $cryptoAmount > 0)
                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-yellow-300">
                                    {{ $cryptoAmount }} {{ $selectedCrypto }}
                                </div>
                                <div class="text-sm text-blue-200">
                                    Amount to pay in selected crypto
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($selectedGateway === 'wallet')
                <div class="mt-6 p-4 border border-blue-900/30 bg-gradient-to-r from-blue-900/20 to-gray-900/10 rounded-xl">
                    <h5 class="font-semibold text-blue-200 mb-4 drop-shadow">Wallet Balance</h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-blue-200">Current Balance:</span>
                            <span class="text-lg font-semibold text-white">
                                ${{ number_format($walletBalance, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-200">Payment Amount:</span>
                            <span class="text-lg font-semibold text-white">
                                ${{ number_format($paymentAmount, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-blue-200">Remaining Balance:</span>
                            <span class="text-lg font-semibold {{ $walletSufficient ? 'text-green-400' : 'text-red-400' }}">
                                ${{ number_format($walletBalance - $paymentAmount, 2) }}
                            </span>
                        </div>
                        @if(!$walletSufficient)
                            <div class="mt-3 p-3 bg-gradient-to-r from-red-900/20 to-yellow-900/10 rounded-lg">
                                <div class="text-red-400 text-sm">
                                    Insufficient wallet balance. Please top up your wallet or choose another payment method.
                                </div>
                                <a href="#" class="text-yellow-300 hover:text-yellow-400 text-sm underline mt-1 inline-block">
                                    Top Up Wallet
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            <div class="mt-8">
                <button
                    wire:click="processPayment"
                    wire:loading.attr="disabled"
                    {{ !$walletSufficient && $selectedGateway === 'wallet' ? 'disabled' : '' }}
                    class="w-full py-3 text-lg font-semibold rounded-xl bg-gradient-to-r from-blue-600 via-blue-400 to-yellow-400 text-white shadow-lg hover:scale-105 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="processPayment">
                        Pay ${{ number_format($paymentAmount, 2) }}
                    </span>
                    <span wire:loading wire:target="processPayment" class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-yellow-400 mr-2"></div>
                        Processing...
                    </span>
                </button>
            </div>
        </section>

    @elseif($paymentStep === 'processing')
        <section class="text-center py-12">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-500 mx-auto mb-4"></div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Processing Payment</h4>
            <p class="text-gray-600 dark:text-gray-400">
                Please wait while we process your payment. This may take a few moments.
            </p>
            @if($selectedGateway === 'nowpayments')
                <div class="mt-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                    <p class="text-sm text-orange-600 dark:text-orange-400">
                        For cryptocurrency payments, you will be redirected to complete the payment.
                    </p>
                </div>
            @endif
        </section>
    @elseif($paymentStep === 'completed')
        <section class="text-center py-12">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h4 class="text-xl font-bold text-green-600 dark:text-green-400 mb-2">Payment Successful!</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Your payment has been processed successfully. You will be redirected shortly.
            </p>
            @if($order)
                <a href="{{ route('orders.show', $order) }}" class="btn-primary">
                    View Order Details
                </a>
            @endif
        </section>
    @elseif($paymentStep === 'failed')
        <section class="text-center py-12">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h4 class="text-xl font-bold text-red-600 dark:text-red-400 mb-2">Payment Failed</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                There was an issue processing your payment. Please try again or use a different payment method.
            </p>
            <button
                wire:click="retryPayment"
                class="btn-primary"
            >
                Try Again
            </button>
        </section>
    @endif

    <!-- Security notice -->
    <footer class="mt-8 px-6 pb-6">
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-start space-x-2">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
            </svg>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <strong>Secure Payment:</strong> All payment information is encrypted and processed securely. We never store your credit card details.
            </div>
        </div>
    </footer>

    <!-- Auto-redirect script -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('redirectAfterPayment', (event) => {
                setTimeout(() => {
                    window.location.href = event.url;
                }, event.delay || 2000);
            });
            // Auto-focus next field on card form
            Livewire.on('focusField', (event) => {
                const field = document.querySelector(`[wire\\:model*="${event.field}"]`);
                if (field) {
                    field.focus();
                }
            });
        });
        // Format card number input
        function formatCardNumber(input) {
            let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ');
            input.value = formattedValue;
        }
    </script>
</section>
</div>

