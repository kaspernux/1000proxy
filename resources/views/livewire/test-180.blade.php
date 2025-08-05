<main class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
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

    <!-- Progress Header -->
    <div class="max-w-6xl mx-auto mb-12 relative z-10">
        <div class="text-center mb-8">
            <!-- Breadcrumb -->
            <nav class="flex justify-center items-center space-x-2 text-sm mb-6">
                <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <a href="/cart" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Cart</a>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-blue-400 font-medium">Checkout</span>
            </nav>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-4 leading-tight">
                <span class="bg-gradient-to-r from-blue-400 via-yellow-400 to-blue-500 bg-clip-text text-transparent">
                    Secure Checkout
                </span>
            </h1>
            <p class="text-lg md:text-xl text-gray-300 font-light">Complete your proxy order in just a few simple steps</p>
        </div>
        
        <!-- Enhanced Progress Steps -->
        <div class="relative">
            <div class="flex items-center justify-center">
                @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center">
                    <div class="relative">
                        <!-- Step Circle -->
                        <div class="flex items-center justify-center w-16 h-16 rounded-full font-bold text-lg shadow-2xl transition-all duration-300
                            {{ $currentStep >= $i ? 'bg-gradient-to-r from-blue-500 to-yellow-500 text-white scale-110' : 'bg-white/10 backdrop-blur-sm text-white/60 border border-white/20' }}">
                            @if($currentStep > $i)
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        
                        <!-- Step Label -->
                        <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-center min-w-max">
                            <span class="text-sm font-medium {{ $currentStep >= $i ? 'text-white' : 'text-gray-400' }}">
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
                            <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-blue-400 animate-ping opacity-50"></div>
                        @endif
                    </div>

                    <!-- Enhanced Connector Line -->
                    @if($i < $totalSteps)
                        <div class="w-24 h-1 mx-4 rounded-full transition-all duration-500 {{ $currentStep > $i ? 'bg-gradient-to-r from-blue-500 to-yellow-500' : 'bg-white/20' }}"></div>
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
                                   wire:model="coupon_code"
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
                                <span class="text-green-400 font-medium">Coupon Applied: {{ $applied_coupon }}</span>
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

                </div>
            </div>
        </div>
    </div>
</main>
