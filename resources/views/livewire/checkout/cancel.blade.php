@extends('layouts.app')

@section('content')
<main class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-red-900 py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-red-600/15 to-orange-500/15 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
    </div>

    <!-- Floating shapes with enhanced animations -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-red-400/25 to-orange-400/25 rounded-full blur-3xl animate-bounce duration-[6000ms]"></div>
        <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-orange-400/20 to-red-400/15 rounded-full blur-3xl animate-pulse duration-[8000ms]"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-2xl animate-spin duration-[20000ms]"></div>
    </div>

    <div class="max-w-4xl mx-auto relative z-10">
        <!-- Breadcrumb -->
        <nav class="flex justify-center items-center space-x-2 text-sm mb-8">
            <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <a href="/checkout" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Checkout</a>
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span class="text-red-400 font-medium">Cancelled</span>
        </nav>
        
        <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10 text-center">
            <!-- Cancel Icon with Animation -->
            <div class="relative mx-auto mb-8">
                <div class="w-32 h-32 bg-gradient-to-r from-red-500 to-orange-400 rounded-full flex items-center justify-center mx-auto shadow-2xl">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <!-- Animated rings -->
                <div class="absolute inset-0 w-32 h-32 rounded-full border-4 border-red-400 animate-ping opacity-30"></div>
                <div class="absolute inset-2 w-28 h-28 rounded-full border-2 border-red-300 animate-pulse opacity-20"></div>
            </div>

            <!-- Cancel Message -->
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4 leading-tight">
                <span class="bg-gradient-to-r from-red-400 via-orange-400 to-red-500 bg-clip-text text-transparent">
                    Order Cancelled
                </span>
            </h1>
            
            <p class="text-xl text-red-200 mb-8 font-light">
                Your order has been cancelled. No payment has been processed.
            </p>

            <!-- Order Information -->
            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-8 mb-8 border border-white/10">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center justify-center">
                    <x-custom-icon name="document-text" class="w-6 h-6 mr-3 text-red-400" />
                    Order Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-sm text-red-200 mb-2">Order Number</p>
                        <p class="text-xl font-bold text-white">#{{ $order->id }}</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-sm text-red-200 mb-2">Order Date</p>
                        <p class="text-xl font-bold text-white">{{ $order->created_at->format('M d, Y - h:i A') }}</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-sm text-red-200 mb-2">Total Amount</p>
                        <p class="text-2xl font-bold text-yellow-400">${{ number_format($order->grand_amount, 2) }}</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-sm text-red-200 mb-2">Status</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500/20 text-red-400 border border-red-500/30">
                            <x-custom-icon name="x-circle" class="w-4 h-4 mr-2" />
                            Cancelled
                        </span>
                    </div>
                </div>
            </div>

            <!-- Why was this cancelled? -->
            <div class="bg-gradient-to-r from-yellow-600/10 to-yellow-500/5 rounded-2xl p-8 mb-8 border border-yellow-500/30">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center justify-center">
                    <x-custom-icon name="question-mark-circle" class="w-5 h-5 mr-3 text-yellow-400" />
                    Why was this cancelled?
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-white/5 rounded-xl">
                        <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-custom-icon name="user" class="w-6 h-6 text-yellow-400" />
                        </div>
                        <p class="text-yellow-200 text-sm">You chose to cancel the payment process</p>
                    </div>
                    <div class="text-center p-4 bg-white/5 rounded-xl">
                        <div class="w-12 h-12 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-custom-icon name="clock" class="w-6 h-6 text-orange-400" />
                        </div>
                        <p class="text-yellow-200 text-sm">The payment session expired</p>
                    </div>
                    <div class="text-center p-4 bg-white/5 rounded-xl">
                        <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-custom-icon name="exclamation-triangle" class="w-6 h-6 text-red-400" />
                        </div>
                        <p class="text-yellow-200 text-sm">There was an issue with the payment method</p>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-gradient-to-r from-blue-600/10 to-blue-500/5 rounded-2xl p-8 mb-8 border border-blue-500/30">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center justify-center">
                    <x-custom-icon name="lightbulb" class="w-5 h-5 mr-3 text-blue-400" />
                    What can you do next?
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-white/5 rounded-xl">
                        <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-custom-icon name="refresh" class="w-6 h-6 text-green-400" />
                        </div>
                        <p class="text-blue-200 text-sm">Try placing your order again with a different payment method</p>
                    </div>
                    <div class="text-center p-4 bg-white/5 rounded-xl">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-custom-icon name="shopping-cart" class="w-6 h-6 text-purple-400" />
                        </div>
                        <p class="text-blue-200 text-sm">Review your cart and make any necessary changes</p>
                    </div>
                    <div class="text-center p-4 bg-white/5 rounded-xl">
                        <div class="w-12 h-12 bg-cyan-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-custom-icon name="support" class="w-6 h-6 text-cyan-400" />
                        </div>
                        <p class="text-blue-200 text-sm">Contact our support team if you need assistance</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('checkout') }}" 
                   class="px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center justify-center">
                    <x-custom-icon name="refresh" class="w-5 h-5 mr-3" />
                    Try Again
                </a>
                <a href="{{ route('cart') }}" 
                   class="px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 flex items-center justify-center">
                    <x-custom-icon name="shopping-cart" class="w-5 h-5 mr-3" />
                    Review Cart
                </a>
                <a href="{{ route('servers') }}" 
                   class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all duration-200 flex items-center justify-center">
                    <x-custom-icon name="arrow-left" class="w-5 h-5 mr-3" />
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</main>
@endsection
