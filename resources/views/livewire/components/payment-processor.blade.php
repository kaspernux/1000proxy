
<section class="payment-processor bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-lg mx-auto my-8 p-0 border border-gray-200 dark:border-gray-800">
    <!-- Payment Progress Indicator -->
    <header class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-xl font-extrabold text-blue-700 dark:text-blue-400">Payment Process</h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ $paymentProgress['progress'] }}% Complete
            </span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div
                class="bg-blue-500 h-2 rounded-full transition-all duration-500 ease-out"
                style="width: {{ $paymentProgress['progress'] }}%"
            ></div>
        </div>
        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ $paymentProgress['steps'][$paymentProgress['current']] }}
        </div>
    </header>

    <!-- Order Summary -->
    @if($order)
        <section class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-800">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Order Summary</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Order #{{ $order->id }}</span>
                    <span class="font-semibold">${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->tax_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Tax</span>
                        <span>${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                @endif
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-bold text-lg border-t pt-2">
                    <span>Total</span>
                    <span>${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </section>
    @endif


    <!-- Payment Step Content -->
    @if($paymentStep === 'select_gateway')
        <section class="px-6 py-6">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Select Payment Method</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($availableGateways as $gatewayKey => $gateway)
                    @if($gateway['enabled'])
                        <div
                            wire:click="selectGateway('{{ $gatewayKey }}')"
                            class="payment-gateway-option p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 {{ $selectedGateway === $gatewayKey ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="text-2xl">{{ $gateway['icon'] }}</span>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $gateway['name'] }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $gateway['description'] }}
                                    </div>
                                    @if($gateway['fee'] > 0)
                                        <div class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                            {{ $gateway['fee'] }}% processing fee
                                        </div>
                                    @endif
                                </div>
                                @if($selectedGateway === $gatewayKey)
                                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            <!-- Gateway-specific forms -->
            @if($selectedGateway === 'stripe')
                <div class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                    <h5 class="font-semibold text-gray-900 dark:text-white mb-4">Card Information</h5>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Cardholder Name
                            </label>
                            <input
                                type="text"
                                wire:model.live="cardholderName"
                                placeholder="John Doe"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                            >
                            @error('cardholderName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Card Number
                            </label>
                            <input
                                type="text"
                                wire:model.live="cardNumber"
                                placeholder="1234 5678 9012 3456"
                                maxlength="19"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white font-mono"
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
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
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
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
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
                <div class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                    <h5 class="font-semibold text-gray-900 dark:text-white mb-4">Select Cryptocurrency</h5>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($cryptoCurrencies as $crypto => $details)
                            <div
                                wire:click="selectCrypto('{{ $crypto }}')"
                                class="crypto-option p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 {{ $selectedCrypto === $crypto ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
                            >
                                <div class="text-center">
                                    <div class="text-2xl mb-1">{{ $details['symbol'] }}</div>
                                    <div class="text-sm font-semibold">{{ $crypto }}</div>
                                    <div class="text-xs text-gray-500">{{ $details['name'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($selectedCrypto && $cryptoAmount > 0)
                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $cryptoAmount }} {{ $selectedCrypto }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    ≈ ${{ number_format($paymentAmount, 2) }} USD
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($selectedGateway === 'wallet')
                <div class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                    <h5 class="font-semibold text-gray-900 dark:text-white mb-4">Wallet Balance</h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Current Balance:</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($walletBalance, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Payment Amount:</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($paymentAmount, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-gray-600 dark:text-gray-400">Remaining Balance:</span>
                            <span class="text-lg font-semibold {{ $walletSufficient ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($walletBalance - $paymentAmount, 2) }}
                            </span>
                        </div>
                        @if(!$walletSufficient)
                            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <div class="text-red-600 dark:text-red-400 text-sm">
                                    <strong>Insufficient Balance:</strong> You need ${{ number_format($paymentAmount - $walletBalance, 2) }} more to complete this payment.
                                </div>
                                <a href="#" class="text-blue-500 hover:text-blue-700 text-sm underline mt-1 inline-block">
                                    Top up your wallet
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            <div class="mt-6">
                <button
                    wire:click="processPayment"
                    wire:loading.attr="disabled"
                    {{ !$walletSufficient && $selectedGateway === 'wallet' ? 'disabled' : '' }}
                    class="w-full btn-primary py-3 text-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="processPayment">
                        Pay ${{ number_format($paymentAmount, 2) }}
                    </span>
                    <span wire:loading wire:target="processPayment" class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
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
