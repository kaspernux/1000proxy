@extends('layouts.app')

@section('content')
<main class="min-h-screen bg-gradient-to-br from-green-900 to-green-600 py-10 px-2 sm:px-6 lg:px-8 flex flex-col items-center">
    <section class="w-full max-w-6xl mx-auto">
        <header class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-2">Complete Your Order</h1>
            <p class="text-lg text-green-100">Secure checkout process</p>
        </header>
        <nav class="mb-10">
            <div class="flex items-center justify-center gap-4">
                @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full font-bold text-lg shadow-lg
                        {{ $currentStep >= $i ? 'bg-green-500 text-white' : 'bg-white/20 text-white/60' }}">
                        @if($currentStep > $i)
                            <x-custom-icon name="check-circle" class="w-5 h-5" />
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    @if($i < $totalSteps)
                        <div class="w-8 h-0.5 {{ $currentStep > $i ? 'bg-green-500' : 'bg-white/20' }} mx-2"></div>
                    @endif
                </div>
                @endfor
            </div>
            <div class="flex flex-wrap justify-center gap-8 mt-2">
                <span class="text-sm {{ $currentStep >= 1 ? 'text-white' : 'text-white/60' }}">Cart Review</span>
                <span class="text-sm {{ $currentStep >= 2 ? 'text-white' : 'text-white/60' }}">Billing Info</span>
                <span class="text-sm {{ $currentStep >= 3 ? 'text-white' : 'text-white/60' }}">Payment</span>
                <span class="text-sm {{ $currentStep >= 4 ? 'text-white' : 'text-white/60' }}">Confirmation</span>
            </div>
        </nav>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <section class="lg:col-span-2 flex flex-col gap-8">
                {{-- Step 1: Cart Review --}}
                @if($currentStep === 1)
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 shadow-lg">
                    <h2 class="text-2xl font-bold text-white mb-6">Review Your Order</h2>

                    <div class="space-y-4">
                        @foreach($cart_items as $item)
                        <div class="flex items-center space-x-4 p-4 bg-white/5 rounded-lg" wire:key="cart-{{ $item['server_plan_id'] }}">
                            <img src="{{ url('storage/' . $item['product_image']) }}"
                                 alt="{{ $item['name'] }}"
                                 class="w-16 h-16 object-cover rounded-lg">
                            <div class="flex-1">
                                <h3 class="text-white font-semibold">{{ $item['name'] }}</h3>
                                <p class="text-white/70 text-sm">Quantity: {{ $item['quantity'] }}</p>
                                <p class="text-green-400 font-bold">${{ number_format($item['total_amount'], 2) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Coupon Code Section --}}
                    <div class="mt-6 p-4 bg-white/5 rounded-lg">
                        <h3 class="text-white font-semibold mb-3">Coupon Code</h3>
                        <div class="flex space-x-2">
                            <input type="text"
                                   wire:model="couponCode"
                                   placeholder="Enter coupon code"
                                   class="flex-1 px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500">
                            <button wire:click="applyCoupon"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                                <span wire:loading wire:target="applyCoupon">Applying...</span>
                            </button>
                        </div>
                        @if($applied_coupon)
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-green-400">Coupon Applied: {{ $applied_coupon['code'] }}</span>
                            <button wire:click="removeCoupon" class="text-red-400 hover:text-red-300">Remove</button>
                        </div>
                        @endif
                    </div>

                    <div class="flex justify-end mt-6">
                        <button wire:click="nextStep"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
                            Continue to Billing
                        </button>
                    </div>
                </div>
                @endif

                {{-- Step 2: Billing Information --}}
                @if($currentStep === 2)
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 shadow-lg">
                    <h2 class="text-2xl font-bold text-white mb-6">Billing Information</h2>

                    <form wire:submit.prevent="nextStep" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-white font-medium mb-2">First Name *</label>
                                <input type="text"
                                       wire:model="first_name"
                                       class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                       required>
                                @error('first_name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-white font-medium mb-2">Last Name *</label>
                                <input type="text"
                                       wire:model="last_name"
                                       class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                       required>
                                @error('last_name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-2">Email Address *</label>
                            <input type="email"
                                   wire:model="email"
                                   class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                   required>
                            @error('email') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-2">Phone Number *</label>
                            <input type="tel"
                                   wire:model="phone"
                                   class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                   required>
                            @error('phone') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-2">Company (Optional)</label>
                            <input type="text"
                                   wire:model="company"
                                   class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500">
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-2">Address *</label>
                            <input type="text"
                                   wire:model="address"
                                   class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                   required>
                            @error('address') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-white font-medium mb-2">City *</label>
                                <input type="text"
                                       wire:model="city"
                                       class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                       required>
                                @error('city') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-white font-medium mb-2">State/Province *</label>
                                <input type="text"
                                       wire:model="state"
                                       class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                       required>
                                @error('state') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-white font-medium mb-2">ZIP/Postal Code *</label>
                                <input type="text"
                                       wire:model="zip_code"
                                       class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                       required>
                                @error('zip_code') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-2">Country *</label>
                            <select wire:model="country"
                                    class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-500"
                                    required>
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="DE">Germany</option>
                                <option value="FR">France</option>
                                <option value="AU">Australia</option>
                                <option value="JP">Japan</option>
                                <!-- Add more countries as needed -->
                            </select>
                            @error('country') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-between mt-6">
                            <button type="button"
                                    wire:click="previousStep"
                                    class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                Back to Cart
                            </button>
                            <button type="submit"
                                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Continue to Payment
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Step 3: Payment Method --}}
                @if($currentStep === 3)
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 shadow-lg">
                    <h2 class="text-2xl font-bold text-white mb-6">Payment Method</h2>

                    {{-- Payment Method Selection --}}
                    <div class="space-y-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            @foreach($availablePaymentMethods as $method)
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-white/5
                                {{ $selectedPaymentMethod === $method['key'] ? 'border-green-500 bg-green-500/10' : 'border-gray-300' }}">
                                <input type="radio"
                                       wire:model="selectedPaymentMethod"
                                       value="{{ $method['key'] }}"
                                       class="sr-only">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ asset('images/payment/' . $method['key'] . '.png') }}"
                                         alt="{{ $method['name'] }}"
                                         class="w-8 h-8">
                                    <span class="text-white font-medium">{{ $method['name'] }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Wallet Balance Option --}}
                    @if($walletBalance > 0)
                    <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-white font-semibold">Use Wallet Balance</h4>
                                <p class="text-white/70 text-sm">Available: ${{ number_format($walletBalance, 2) }}</p>
                            </div>
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model="useWalletBalance"
                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <span class="ml-2 text-white">Use wallet balance</span>
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="flex justify-between mt-6">
                        <button wire:click="previousStep"
                                class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            Back to Billing
                        </button>
                        <button wire:click="processPayment"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="processPayment">Complete Order</span>
                            <span wire:loading wire:target="processPayment">Processing...</span>
                        </button>
                    </div>
                </div>
                @endif

                {{-- Step 4: Order Confirmation --}}
                @if($currentStep === 4)
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 text-center shadow-lg">
                    <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white mb-4">Order Confirmed!</h2>
                    <p class="text-white/80 mb-6">Thank you for your purchase. Your order has been processed successfully.</p>

                    @if($confirmedOrder)
                    <div class="bg-white/5 rounded-lg p-4 mb-6">
                        <p class="text-white"><strong>Order ID:</strong> {{ $confirmedOrder['order_number'] }}</p>
                        <p class="text-white"><strong>Total:</strong> ${{ number_format($confirmedOrder['grand_total'], 2) }}</p>
                        <p class="text-white"><strong>Status:</strong> {{ ucfirst($confirmedOrder['status']) }}</p>
                    </div>
                    @endif

                    <div class="space-y-3">
                        <a href="/customer/orders" wire:navigate
                           class="block w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            View Order Details
                        </a>
                        <a href="/servers" wire:navigate
                           class="block w-full px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            Continue Shopping
                        </a>
                    </div>
                </div>
                @endif
            </section>

            {{-- Order Summary Sidebar --}}
            <aside class="lg:col-span-1">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 sticky top-8 shadow-lg">
                    <h3 class="text-xl font-bold text-white mb-4">Order Summary</h3>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-white">
                            <span>Subtotal:</span>
                            <span>${{ number_format($order_summary['subtotal'] ?? 0, 2) }}</span>
                        </div>

                        @if(isset($order_summary['coupon_discount']) && $order_summary['coupon_discount'] > 0)
                        <div class="flex justify-between text-green-400">
                            <span>Coupon Discount:</span>
                            <span>-${{ number_format($order_summary['coupon_discount'], 2) }}</span>
                        </div>
                        @endif

                        @if(isset($order_summary['wallet_used']) && $order_summary['wallet_used'] > 0)
                        <div class="flex justify-between text-blue-400">
                            <span>Wallet Balance Used:</span>
                            <span>-${{ number_format($order_summary['wallet_used'], 2) }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between text-white">
                            <span>Tax:</span>
                            <span>${{ number_format($order_summary['tax_amount'] ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <div class="border-t border-white/20 pt-3">
                        <div class="flex justify-between text-white text-xl font-bold">
                            <span>Total:</span>
                            <span>${{ number_format($order_summary['grand_total'] ?? 0, 2) }}</span>
                        </div>
                    </div>

                    {{-- Security Badges --}}
                    <div class="mt-6 space-y-3">
                        <div class="flex items-center space-x-2 text-white/70 text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Secure 256-bit SSL encryption</span>
                        </div>
                        <div class="flex items-center space-x-2 text-white/70 text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>100% secure payments</span>
                        </div>
                        <div class="flex items-center space-x-2 text-white/70 text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                            </svg>
                            <span>Instant activation</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.delay wire:target="processPayment" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 text-center shadow-2xl">
            <svg class="animate-spin h-10 w-10 text-green-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-700 font-semibold">Processing your payment...</p>
            <p class="text-sm text-gray-500 mt-2">Please don't close this window</p>
        </div>
    </div>
</div>
@endsection