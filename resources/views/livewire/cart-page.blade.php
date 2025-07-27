@extends('layouts.app')

@section('content')
<div class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12 px-4 sm:px-6 md:px-8 lg:px-10">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white text-left">Shopping Cart</h1>
            @if(count($order_items) > 0)
                <div class="text-white/80">
                    <span class="text-lg">{{ count($order_items) }} item(s) in cart</span>
                </div>
            @endif
        </div>

        {{-- Success Messages --}}
        @if (session()->has('success'))
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="w-full lg:w-2/3">
                {{-- Cart Items Table --}}
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 mb-6 overflow-x-auto">
                    @if(count($order_items) > 0)
                        {{-- Table Header --}}
                        <div class="hidden md:grid grid-cols-12 gap-4 pb-4 border-b border-white/20 text-white font-semibold">
                            <div class="col-span-6">Product</div>
                            <div class="col-span-2 text-center">Price</div>
                            <div class="col-span-2 text-center">Quantity</div>
                            <div class="col-span-2 text-center">Total</div>
                        </div>

                        {{-- Cart Items --}}
                        <div class="space-y-4 mt-4">
                            @foreach ($order_items as $item)
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center p-4 bg-white/5 rounded-lg hover:bg-white/10 transition duration-200" wire:key='{{ $item["server_plan_id"] }}'>
                                {{-- Product Info --}}
                                <div class="col-span-1 md:col-span-6">
                                    <div class="flex items-center space-x-4">
                                        <img class="h-16 w-16 object-cover rounded-lg"
                                             src="{{ url('storage/' . $item['product_image']) }}"
                                             alt="{{ $item['name'] }}">
                                        <div class="flex-1">
                                            <h3 class="text-white font-semibold">{{ $item['name'] }}</h3>
                                            <p class="text-white/70 text-sm">{{ $item['plan_type'] ?? 'Server Plan' }}</p>
                                            {{-- Save for Later --}}
                                            <button wire:click="saveForLater({{ $item['server_plan_id'] }})"
                                                    class="text-yellow-400 hover:text-yellow-300 text-sm mt-1">
                                                Save for later
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Price --}}
                                <div class="col-span-1 md:col-span-2 text-center">
                                    <span class="text-white font-semibold">{{ Number::currency($item['price']) }}</span>
                                </div>

                                {{-- Quantity Controls --}}
                                <div class="col-span-1 md:col-span-2 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <button wire:click='decreaseQty({{ $item["server_plan_id"] }})'
                                                class="w-8 h-8 bg-white/20 text-white rounded-lg hover:bg-white/30 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        <span class="w-8 text-center text-white font-semibold">{{ $item['quantity'] }}</span>
                                        <button wire:click='increaseQty({{ $item["server_plan_id"] }})'
                                                class="w-8 h-8 bg-white/20 text-white rounded-lg hover:bg-white/30 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Total --}}
                                <div class="col-span-1 md:col-span-2 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <span class="text-white font-bold">{{ Number::currency($item['total_amount']) }}</span>
                                        <button wire:click='removeItem({{ $item["server_plan_id"] }})'
                                                class="text-red-400 hover:text-red-300 transition">
                                            <x-custom-icon name="x-circle" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Cart Actions --}}
                        <div class="flex flex-col md:flex-row justify-between items-center mt-6 pt-6 border-t border-white/20">
                            <button wire:click="clearCart"
                                    class="text-red-400 hover:text-red-300 font-medium mb-4 md:mb-0 flex items-center">
                                <x-custom-icon name="x-circle" class="h-4 w-4 mr-2" />
                                Clear entire cart
                            </button>
                            <a href="/" wire:navigate
                               class="bg-white/20 text-white px-6 py-3 rounded-lg hover:bg-white/30 transition duration-200 flex items-center">
                                <x-custom-icon name="shopping-cart" class="h-4 w-4 mr-2" />
                                Continue Shopping
                            </a>
                        </div>
                    @else
                        {{-- Empty Cart --}}
                        <div class="text-center py-12">
                            <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.293 2.293A1 1 0 004 16h16M9 19a2 2 0 100 4 2 2 0 000-4zM20 19a2 2 0 100 4 2 2 0 000-4z"></path>
                            </svg>
                            <h3 class="text-2xl font-bold text-white mb-2">Your cart is empty</h3>
                            <p class="text-white/70 mb-6">Start shopping to add items to your cart</p>
                            <a href="/" wire:navigate
                               class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition duration-200 inline-block">
                                Start Shopping
                            </a>
                        </div>
                    @endif
                </div>

            </div>

            <!-- Enhanced Summary Section -->
            <div class="w-full lg:w-1/3">
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-white mb-6">Order Summary</h2>

                    {{-- Coupon Section --}}
                    <div class="mb-6 p-4 bg-white/5 rounded-lg">
                        <h3 class="text-white font-semibold mb-3">Have a coupon?</h3>
                        <div class="space-y-3">
                            <input type="text"
                                   wire:model="couponCode"
                                   placeholder="Enter coupon code"
                                   class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-500">
                            <button wire:click="applyCoupon"
                                    class="w-full bg-yellow-600 text-white py-2 rounded-lg hover:bg-yellow-700 transition duration-200">
                                <span wire:loading.remove wire:target="applyCoupon">Apply Coupon</span>
                                <span wire:loading wire:target="applyCoupon">Applying...</span>
                            </button>
                        </div>
                        @if($applied_coupon)
                        <div class="mt-3 p-2 bg-green-500/20 border border-green-500 rounded text-green-100 text-sm">
                            Coupon "{{ $applied_coupon['code'] }}" applied - {{ $applied_coupon['discount'] }}% off
                        </div>
                        @endif
                    </div>

                    {{-- Price Breakdown --}}
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-white/80">
                            <span>Subtotal ({{ count($order_items) }} items)</span>
                            <span>{{ Number::currency($grand_amount) }}</span>
                        </div>

                        @if($applied_coupon)
                        <div class="flex justify-between text-green-400">
                            <span>Coupon Discount</span>
                            <span>-{{ Number::currency($couponDiscount ?? 0) }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between text-white/80">
                            <span>Estimated Tax</span>
                            <span>{{ Number::currency($estimatedTax ?? 0) }}</span>
                        </div>

                        <div class="flex justify-between text-white/80">
                            <span>Shipping</span>
                            <span class="text-green-400">Free</span>
                        </div>

                        <hr class="border-white/20">

                        <div class="flex justify-between text-xl font-bold text-white">
                            <span>Total</span>
                            <span>{{ Number::currency($finalAmount ?? $grand_amount) }}</span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    @if (count($order_items) > 0)
                    <div class="space-y-3">
                        <a href="/checkout" wire:navigate
                           class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition duration-200 flex items-center justify-center">
                            <x-custom-icon name="credit-card" class="h-5 w-5 mr-2" />
                            Proceed to Checkout
                        </a>
                        <button wire:click="saveCart"
                                class="w-full bg-white/20 text-white py-3 px-6 rounded-lg font-semibold hover:bg-white/30 transition duration-200 flex items-center justify-center">
                            <x-custom-icon name="heart" class="h-5 w-5 mr-2" />
                            Save Cart for Later
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Recently Viewed --}}
                @if(isset($recentlyViewed) && count($recentlyViewed) > 0)
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Recently Viewed</h3>
                    <div class="space-y-3">
                        @foreach($recentlyViewed as $product)
                        <div class="flex items-center space-x-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition">
                            <img src="{{ $product['image'] }}"
                                 alt="{{ $product['name'] }}"
                                 class="w-12 h-12 object-cover rounded">
                            <div class="flex-1">
                                <h4 class="text-white font-medium text-sm">{{ $product['name'] }}</h4>
                                <p class="text-white/70 text-xs">{{ Number::currency($product['price']) }}</p>
                            </div>
                            <button class="text-green-400 hover:text-green-300 text-xs">
                                Add to Cart
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Recommended Products --}}
        @if(isset($recommendedProducts) && count($recommendedProducts) > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-white mb-6">You might also like</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($recommendedProducts as $product)
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 hover:bg-white/15 transition duration-200">
                    <img src="{{ $product['image'] }}"
                         alt="{{ $product['name'] }}"
                         class="w-full h-32 object-cover rounded-lg mb-3">
                    <h3 class="text-white font-semibold mb-2">{{ $product['name'] }}</h3>
                    <p class="text-white/70 text-sm mb-3">{{ $product['description'] }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-green-400 font-bold">{{ Number::currency($product['price']) }}</span>
                        <button wire:click="addToCart({{ $product['id'] }})"
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                            Add to Cart
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Saved for Later --}}
        @if(isset($savedItems) && count($savedItems) > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-white mb-6">Saved for Later</h2>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($savedItems as $item)
                    <div class="bg-white/5 rounded-lg p-4">
                        <div class="flex items-center space-x-3 mb-3">
                            <img src="{{ $item['image'] }}"
                                 alt="{{ $item['name'] }}"
                                 class="w-16 h-16 object-cover rounded">
                            <div class="flex-1">
                                <h4 class="text-white font-semibold">{{ $item['name'] }}</h4>
                                <p class="text-white/70 text-sm">{{ Number::currency($item['price']) }}</p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button wire:click="moveToCart({{ $item['id'] }})"
                                    class="flex-1 bg-green-600 text-white py-2 rounded text-sm hover:bg-green-700 transition">
                                Move to Cart
                            </button>
                            <button wire:click="removeSavedItem({{ $item['id'] }})"
                                    class="px-3 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition">
                                Remove
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
@endsection
    </div>
</div>
