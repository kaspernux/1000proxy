@extends('layouts.app')

@section('content')
<div class="w-full font-mono bg-gradient-to-r from-red-900 to-red-600 py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="container mx-auto px-4 max-w-4xl">
        
        <div class="bg-white rounded-xl shadow p-8 dark:bg-slate-900 text-center">
            <!-- Cancel Icon -->
            <div class="mx-auto mb-6 w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>

            <!-- Cancel Message -->
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">
                Order Cancelled
            </h1>
            
            <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
                Your order has been cancelled. No payment has been processed.
            </p>

            <!-- Order Details -->
            <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Order Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Order Number:</p>
                        <p class="font-semibold text-gray-800 dark:text-white">#{{ $order->id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Order Date:</p>
                        <p class="font-semibold text-gray-800 dark:text-white">{{ $order->created_at->format('M d, Y - h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Total Amount:</p>
                        <p class="font-semibold text-gray-800 dark:text-white">${{ number_format($order->grand_amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Status:</p>
                        <p class="font-semibold text-red-600">Cancelled</p>
                    </div>
                </div>
            </div>

            <!-- Why was this cancelled? -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Why was this cancelled?</h3>
                <div class="text-left space-y-2">
                    <p class="text-gray-700 dark:text-gray-300">
                        • You chose to cancel the payment process
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        • The payment session expired
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        • There was an issue with the payment method
                    </p>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">What can you do next?</h3>
                <div class="text-left space-y-2">
                    <p class="text-gray-700 dark:text-gray-300">
                        <i class="fas fa-redo mr-2 text-blue-600"></i>
                        Try placing your order again with a different payment method
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        <i class="fas fa-shopping-cart mr-2 text-blue-600"></i>
                        Review your cart and make any necessary changes
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        <i class="fas fa-headset mr-2 text-blue-600"></i>
                        Contact our support team if you need assistance
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('checkout') }}" 
                   class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Try Again
                </a>
                <a href="{{ route('cart') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Review Cart
                </a>
                <a href="{{ route('servers') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
