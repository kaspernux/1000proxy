@extends('layouts.app')

@section('content')
<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="container mx-auto px-4 max-w-4xl">
        
        <div class="bg-white rounded-xl shadow p-8 dark:bg-slate-900 text-center">
            <!-- Success Icon -->
            <div class="mx-auto mb-6 w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Success Message -->
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">
                Order Successful!
            </h1>
            
            <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
                Thank you for your purchase! Your order has been processed successfully.
            </p>

            <!-- Order Details -->
            <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Order Details</h2>
                
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
                        <p class="text-sm text-gray-600 dark:text-gray-300">Payment Status:</p>
                        <p class="font-semibold text-green-600">{{ ucfirst($order->payment_status) }}</p>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Items Ordered</h3>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                    <div class="flex justify-between items-center text-left">
                        <div>
                            <p class="font-medium text-gray-800 dark:text-white">{{ $item->serverPlan->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $item->serverPlan->server->location ?? 'N/A' }} - Quantity: {{ $item->quantity }}
                            </p>
                        </div>
                        <p class="font-semibold text-gray-800 dark:text-white">${{ number_format($item->total_amount, 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">What's Next?</h3>
                <div class="text-left space-y-2">
                    <p class="text-gray-700 dark:text-gray-300">
                        <i class="fas fa-envelope mr-2 text-blue-600"></i>
                        You'll receive an order confirmation email shortly
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        <i class="fas fa-cog mr-2 text-blue-600"></i>
                        Your proxy configurations will be available in your account within 5-10 minutes
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        <i class="fas fa-headset mr-2 text-blue-600"></i>
                        Need help? Contact our support team 24/7
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('my-orders.show', $order->id) }}" 
                   class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    View Order Details
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
