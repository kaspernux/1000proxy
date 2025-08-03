@extends('layouts.app')

@section('content')
<main class="min-h-screen bg-gradient-to-br from-green-900 via-green-800 to-green-600 py-10 px-4 sm:px-6 lg:px-8">
    <!-- Progress Header -->
    <div class="max-w-6xl mx-auto mb-12">
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                <span class="bg-gradient-to-r from-yellow-400 to-yellow-200 bg-clip-text text-transparent">
                    Secure Checkout
                </span>
            </h1>
            <p class="text-xl text-green-100">Complete your order in just a few steps</p>
        </div>
        
        <!-- Enhanced Progress Steps -->
        <div class="relative">
            <div class="flex items-center justify-center">
                @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center">
                    <div class="relative">
                        <!-- Step Circle -->
                        <div class="flex items-center justify-center w-16 h-16 rounded-full font-bold text-lg shadow-2xl transition-all duration-300
                            {{ $currentStep >= $i ? 'bg-gradient-to-r from-green-500 to-green-400 text-white scale-110' : 'bg-white/20 text-white/60' }}">
                            @if($currentStep > $i)
                                <x-custom-icon name="check-circle" class="w-8 h-8" />
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        
                        <!-- Step Label -->
                        <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-center min-w-max">
                            <span class="text-sm font-medium {{ $currentStep >= $i ? 'text-white' : 'text-white/60' }}">
                                @switch($i)
                                    @case(1) Cart Review @break
                                    @case(2) Billing Info @break
                                    @case(3) Payment @break
                                    @case(4) Confirmation @break
                                @endswitch
                            </span>
                        </div>

                        <!-- Animated Ring for Current Step -->
                        @if($currentStep === $i)
                            <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-yellow-400 animate-ping"></div>
                        @endif
                    </div>

                    <!-- Connector Line -->
                    @if($i < $totalSteps)
                        <div class="w-24 h-1 mx-4 rounded-full transition-all duration-500 {{ $currentStep > $i ? 'bg-gradient-to-r from-green-500 to-green-400' : 'bg-white/20' }}"></div>
                    @endif
                </div>
                @endfor
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Checkout Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Step 1: Enhanced Cart Review -->
                @if($currentStep === 1)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-8 flex items-center">
                        <x-custom-icon name="shopping-cart" class="w-8 h-8 mr-4 text-yellow-400" />
                        Review Your Order
                    </h2>

                    <div class="space-y-6">
                        @foreach($cart_items as $item)
                        <div class="group bg-white/5 rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-all duration-300" 
                             wire:key="cart-{{ $item['server_plan_id'] }}">
                            <div class="flex items-center space-x-6">
                                <!-- Product Image -->
                                <div class="relative">
                                    <img src="{{ url('storage/' . $item['product_image']) }}"
                                         alt="{{ $item['name'] }}"
                                         class="w-20 h-20 object-cover rounded-xl border border-yellow-400/50">
                                    <div class="absolute -top-2 -right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        {{ $item['quantity'] }}x
                                    </div>
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-white group-hover:text-yellow-400 transition-colors">
                                        {{ $item['name'] }}
                                    </h3>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <span class="text-green-200 text-sm">Quantity: {{ $item['quantity'] }}</span>
                                        <span class="text-white/60">•</span>
                                        <span class="text-green-200 text-sm">${{ number_format($item['price'], 2) }} each</span>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-yellow-400">
                                        ${{ number_format($item['total_amount'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Enhanced Coupon Section -->
                    <div class="mt-8 bg-gradient-to-r from-yellow-600/10 to-yellow-500/5 rounded-2xl p-6 border border-yellow-500/30">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <x-custom-icon name="ticket" class="w-6 h-6 mr-3 text-yellow-400" />
                            Promo Code
                        </h3>
                        <div class="flex space-x-3">
                            <input type="text"
                                   wire:model="couponCode"
                                   placeholder="Enter your promo code"
                                   class="flex-1 px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                            <button wire:click="applyCoupon"
                                    class="px-6 py-3 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded-xl transition-all duration-200 disabled:opacity-50"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                                <span wire:loading wire:target="applyCoupon" class="flex items-center">
                                    <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                                    Applying...
                                </span>
                            </button>
                        </div>
                        
                        @if($applied_coupon)
                        <div class="mt-4 bg-green-600/20 border border-green-500/30 rounded-xl p-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <x-custom-icon name="check-circle" class="w-5 h-5 text-green-400 mr-3" />
                                <span class="text-green-400 font-medium">Coupon Applied: {{ $applied_coupon['code'] }}</span>
                            </div>
                            <button wire:click="removeCoupon" class="text-red-400 hover:text-red-300 font-medium">
                                Remove
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="flex justify-end mt-8">
                        <button wire:click="nextStep"
                                class="px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center">
                            Continue to Billing
                            <x-custom-icon name="arrow-right" class="w-5 h-5 ml-3" />
                        </button>
                    </div>
                </div>
                @endif

                <!-- Step 2: Enhanced Billing Information -->
                @if($currentStep === 2)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-8 flex items-center">
                        <x-custom-icon name="user" class="w-8 h-8 mr-4 text-blue-400" />
                        Billing Information
                    </h2>

                    <form wire:submit.prevent="nextStep" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-white font-medium mb-3">First Name *</label>
                                <input type="text"
                                       wire:model="first_name"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="Enter your first name">
                                @error('first_name') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-white font-medium mb-3">Last Name *</label>
                                <input type="text"
                                       wire:model="last_name"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="Enter your last name">
                                @error('last_name') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">Email Address *</label>
                            <input type="email"
                                   wire:model="email"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your email address">
                            @error('email') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">Address *</label>
                            <input type="text"
                                   wire:model="address"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your address">
                            @error('address') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-white font-medium mb-3">City *</label>
                                <input type="text"
                                       wire:model="city"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="City">
                                @error('city') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-white font-medium mb-3">State/Province</label>
                                <input type="text"
                                       wire:model="state"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="State/Province">
                            </div>

                            <div>
                                <label class="block text-white font-medium mb-3">ZIP/Postal Code *</label>
                                <input type="text"
                                       wire:model="zip_code"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="ZIP Code">
                                @error('zip_code') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">Country *</label>
                            <select wire:model="country"
                                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="DE">Germany</option>
                                <option value="FR">France</option>
                                <option value="AU">Australia</option>
                                <!-- Add more countries as needed -->
                            </select>
                            @error('country') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button"
                                    wire:click="previousStep"
                                    class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all duration-200">
                                <x-custom-icon name="arrow-left" class="w-5 h-5 mr-2 inline" />
                                Back to Cart
                            </button>
                            <button type="submit"
                                    class="px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center">
                                Continue to Payment
                                <x-custom-icon name="arrow-right" class="w-5 h-5 ml-3" />
                            </button>
                        </div>
                    </form>
                <!-- Step 3: Enhanced Payment Method -->
                @if($currentStep === 3)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-8 flex items-center">
                        <x-custom-icon name="credit-card" class="w-8 h-8 mr-4 text-green-400" />
                        Payment Method
                    </h2>

                    <!-- Payment Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        @foreach(['bitcoin', 'monero', 'paypal', 'stripe'] as $method)
                        <button wire:click="$set('selectedPaymentMethod', '{{ $method }}')"
                                class="relative p-6 rounded-2xl border-2 transition-all duration-300 group
                                {{ $selectedPaymentMethod === $method 
                                    ? 'border-green-500 bg-green-500/20' 
                                    : 'border-white/20 bg-white/5 hover:border-green-400/50 hover:bg-green-400/10' }}">
                            
                            <!-- Payment Method Icon & Name -->
                            <div class="text-center">
                                @switch($method)
                                    @case('bitcoin')
                                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-bold text-xl">₿</span>
                                        </div>
                                        <h3 class="text-white font-medium">Bitcoin</h3>
                                        @break
                                    @case('monero')
                                        <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-bold text-xl">ɱ</span>
                                        </div>
                                        <h3 class="text-white font-medium">Monero</h3>
                                        @break
                                    @case('paypal')
                                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-bold text-lg">PP</span>
                                        </div>
                                        <h3 class="text-white font-medium">PayPal</h3>
                                        @break
                                    @case('stripe')
                                        <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <x-custom-icon name="credit-card" class="w-6 h-6 text-white" />
                                        </div>
                                        <h3 class="text-white font-medium">Credit Card</h3>
                                        @break
                                @endswitch
                            </div>

                            <!-- Selected Indicator -->
                            @if($selectedPaymentMethod === $method)
                                <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                    <x-custom-icon name="check" class="w-4 h-4 text-white" />
                                </div>
                            @endif
                        </button>
                        @endforeach
                    </div>

                    <!-- Payment Method Details -->
                    @if($selectedPaymentMethod)
                        <div class="bg-white/5 rounded-2xl p-6 mb-8 border border-white/10">
                            @switch($selectedPaymentMethod)
                                @case('bitcoin')
                                    <h4 class="text-xl font-bold text-orange-400 mb-4">Bitcoin Payment</h4>
                                    <p class="text-green-200 mb-4">You will be redirected to complete your Bitcoin payment securely.</p>
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>Secure and anonymous cryptocurrency payment</span>
                                    </div>
                                    @break
                                @case('monero')
                                    <h4 class="text-xl font-bold text-orange-500 mb-4">Monero Payment</h4>
                                    <p class="text-green-200 mb-4">Complete your payment with Monero for maximum privacy.</p>
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>Private and untraceable cryptocurrency payment</span>
                                    </div>
                                    @break
                                @case('paypal')
                                    <h4 class="text-xl font-bold text-blue-400 mb-4">PayPal Payment</h4>
                                    <p class="text-green-200 mb-4">Pay securely with your PayPal account or credit card.</p>
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>Buyer protection and secure payment processing</span>
                                    </div>
                                    @break
                                @case('stripe')
                                    <h4 class="text-xl font-bold text-purple-400 mb-4">Credit Card Payment</h4>
                                    <p class="text-green-200 mb-4">Pay with your credit or debit card through our secure payment processor.</p>
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>PCI DSS compliant secure payment processing</span>
                                    </div>
                                    @break
                            @endswitch
                        </div>
                    @endif

                    <div class="flex justify-between mt-8">
                        <button wire:click="previousStep"
                                class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all duration-200">
                            <x-custom-icon name="arrow-left" class="w-5 h-5 mr-2 inline" />
                            Back to Billing
                        </button>
                        <button wire:click="processPayment"
                                wire:loading.attr="disabled"
                                @if(!$selectedPaymentMethod) disabled @endif
                                class="px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <x-custom-icon name="lock-closed" class="w-5 h-5 mr-3" wire:loading.remove wire:target="processPayment" />
                            <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-3" wire:loading wire:target="processPayment"></div>
                            <span wire:loading.remove wire:target="processPayment">Complete Order</span>
                            <span wire:loading wire:target="processPayment">Processing...</span>
                        </button>
                    </div>
                </div>
                @endif

                <!-- Step 4: Order Confirmation -->
                @if($currentStep === 4)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10 text-center">
                    <div class="mb-8">
                        <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                            <x-custom-icon name="check" class="w-12 h-12 text-white" />
                        </div>
                        <h2 class="text-4xl font-bold text-white mb-4">Order Confirmed!</h2>
                        <p class="text-xl text-green-200">Thank you for your purchase. Your order has been processed successfully.</p>
                    </div>

                    @if(isset($orderDetails))
                    <div class="bg-white/5 rounded-2xl p-6 mb-8 text-left">
                        <h3 class="text-xl font-bold text-white mb-4">Order Details</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-green-200">Order Number:</span>
                                <span class="text-white font-medium">#{{ $orderDetails['order_number'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-200">Total Amount:</span>
                                <span class="text-white font-medium">${{ number_format($orderDetails['total'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-200">Payment Method:</span>
                                <span class="text-white font-medium capitalize">{{ $selectedPaymentMethod ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="space-y-4">
                        <button wire:click="goToOrders"
                                class="w-full px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                            View My Orders
                        </button>
                        <button wire:click="continueShopping"
                                class="w-full px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all duration-200">
                            Continue Shopping
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Enhanced Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10 sticky top-8">
                    <h3 class="text-2xl font-bold text-white mb-6 flex items-center">
                        <x-custom-icon name="document-text" class="w-6 h-6 mr-3 text-yellow-400" />
                        Order Summary
                    </h3>

                    <!-- Cart Items -->
                    <div class="space-y-4 mb-6">
                        @foreach($cart_items as $item)
                        <div class="flex items-center space-x-3 p-4 bg-white/5 rounded-xl" wire:key="summary-{{ $item['server_plan_id'] }}">
                            <img src="{{ url('storage/' . $item['product_image']) }}"
                                 alt="{{ $item['name'] }}"
                                 class="w-12 h-12 object-cover rounded-lg">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-white font-medium text-sm truncate">{{ $item['name'] }}</h4>
                                <p class="text-green-200 text-xs">Qty: {{ $item['quantity'] }}</p>
                            </div>
                            <div class="text-yellow-400 font-bold text-sm">
                                ${{ number_format($item['total_amount'], 2) }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Price Breakdown -->
                    <div class="border-t border-white/20 pt-6 space-y-3">
                        <div class="flex justify-between text-green-200">
                            <span>Subtotal:</span>
                            <span>${{ number_format($cart_total, 2) }}</span>
                        </div>
                        
                        @if($applied_coupon)
                        <div class="flex justify-between text-green-400">
                            <span>Discount ({{ $applied_coupon['code'] }}):</span>
                            <span>-${{ number_format($applied_coupon['discount'], 2) }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between text-green-200">
                            <span>Tax:</span>
                            <span>${{ number_format($tax_amount ?? 0, 2) }}</span>
                        </div>

                        <div class="border-t border-white/20 pt-3">
                            <div class="flex justify-between text-white font-bold text-xl">
                                <span>Total:</span>
                                <span class="text-yellow-400">${{ number_format($final_total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Security Badges -->
                    <div class="mt-8 pt-6 border-t border-white/20">
                        <div class="text-center">
                            <h4 class="text-white font-medium mb-4">Secure Checkout</h4>
                            <div class="flex justify-center space-x-4">
                                <div class="flex items-center text-green-300 text-xs">
                                    <x-custom-icon name="shield-check" class="w-4 h-4 mr-1" />
                                    <span>SSL Encrypted</span>
                                </div>
                                <div class="flex items-center text-green-300 text-xs">
                                    <x-custom-icon name="lock-closed" class="w-4 h-4 mr-1" />
                                    <span>PCI Compliant</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
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