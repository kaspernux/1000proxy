@extends('layouts.app')

@section('content')
    <x-slot:title>Checkout - 1000 PROXIES</x-slot:title>
<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="container mx-auto px-4 max-w-7xl">

        {{-- Flash Messages --}}
        @if (session('success'))
        <div class="bg-green-500 text-white p-3 rounded mb-4 alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if (session('warning'))
        <div class="bg-yellow-500 text-white p-3 rounded mb-4 alert alert-warning">
            {{ session('warning') }}
        </div>
        @endif

        @if (session('error'))
        <div class="bg-red-500 text-white p-3 rounded mb-4 alert alert-danger">
            {{ session('error') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="bg-red-500 text-white p-3 rounded mb-4 alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <h1 class="text-3xl sm:text-4xl font-bold text-white my-6 sm:my-10 text-left">
            Checkout
        </h1>

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
            @csrf
            <div class="grid grid-cols-12 gap-4">
                <div class="md:col-span-12 lg:col-span-8 col-span-12">
                    <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
                        
                        <!-- Customer Details -->
                        <div class="mb-6">
                            <h2 class="text-xl font-bold underline text-gray-700 dark:text-white mb-2">
                                Customer Details
                            </h2>
                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="name">
                                    Username or Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $customer->name) }}"
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white @error('name') border-red-500 @else border-gray-300 @enderror"
                                    placeholder="Enter your name"
                                    required>
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="email">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email', $customer->email) }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white @error('email') border-red-500 @enderror"
                                    placeholder="Enter your email"
                                    required>
                                @error('email')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="phone">
                                    Phone Number
                                </label>
                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone', $customer->phone) }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white @error('phone') border-red-500 @enderror"
                                    placeholder="Enter your phone number">
                                @error('phone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="telegram_id">
                                    Telegram ID
                                </label>
                                <input
                                    type="text"
                                    id="telegram_id"
                                    name="telegram_id"
                                    value="{{ old('telegram_id', $customer->telegram_id) }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white @error('telegram_id') border-red-500 @enderror"
                                    placeholder="Enter your Telegram ID">
                                @error('telegram_id')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="mb-6">
                            <h2 class="text-xl font-bold underline text-gray-700 dark:text-white mb-4">
                                Payment Method <span class="text-red-500">*</span>
                            </h2>
                            <div class="space-y-3">
                                @foreach($payment_methods as $method)
                                <div class="border border-gray-300 rounded-lg p-4 dark:border-slate-600">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="{{ $method->slug }}"
                                            class="mr-3 focus:ring-green-500 @error('payment_method') border-red-500 @enderror"
                                            {{ old('payment_method') === $method->slug ? 'checked' : '' }}
                                            required>
                                        <div class="flex items-center">
                                            @if($method->icon)
                                                <img src="{{ asset('storage/' . $method->icon) }}" alt="{{ $method->name }}" class="w-8 h-8 mr-3">
                                            @endif
                                            <div>
                                                <div class="font-semibold text-gray-700 dark:text-white">{{ $method->name }}</div>
                                                @if($method->description)
                                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $method->description }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @error('payment_method')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mb-6">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="terms_accepted"
                                    value="1"
                                    class="mr-3 focus:ring-green-500 @error('terms_accepted') border-red-500 @enderror"
                                    {{ old('terms_accepted') ? 'checked' : '' }}
                                    required>
                                <span class="text-gray-700 dark:text-white">
                                    I agree to the <a href="#" class="text-green-600 hover:underline">Terms and Conditions</a> and <a href="#" class="text-green-600 hover:underline">Privacy Policy</a> <span class="text-red-500">*</span>
                                </span>
                            </label>
                            @error('terms_accepted')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="md:col-span-12 lg:col-span-4 col-span-12">
                    <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900 sticky top-6">
                        <h2 class="text-xl font-bold text-gray-700 dark:text-white mb-4">Order Summary</h2>
                        
                        <!-- Order Items -->
                        <div class="space-y-4 mb-6">
                            @foreach($order_items as $item)
                                @php
                                    $plan = App\Models\ServerPlan::find($item['server_plan_id']);
                                @endphp
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-700 dark:text-white">{{ $plan->name }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ $plan->server->location ?? 'N/A' }} - {{ $plan->server->brand->name ?? 'N/A' }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            Quantity: {{ $item['quantity'] }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-700 dark:text-white">
                                            ${{ number_format($plan->price * $item['quantity'], 2) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="border-gray-300 dark:border-slate-600 mb-4">

                        <!-- Totals -->
                        <div class="space-y-2 mb-6">
                            <div class="flex justify-between text-gray-700 dark:text-white">
                                <span>Subtotal:</span>
                                <span>${{ number_format($grand_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-700 dark:text-white">
                                <span>Tax:</span>
                                <span>$0.00</span>
                            </div>
                            <hr class="border-gray-300 dark:border-slate-600">
                            <div class="flex justify-between text-lg font-bold text-gray-700 dark:text-white">
                                <span>Total:</span>
                                <span>${{ number_format($grand_amount, 2) }}</span>
                            </div>
                        </div>

                        <!-- Place Order Button -->
                        <button
                            type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            id="place-order-btn">
                            <span class="loading-text">Place Order</span>
                            <span class="loading-spinner hidden">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>

                        <!-- Security Notice -->
                        <div class="mt-4 text-center">
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                <i class="fas fa-lock mr-1"></i>
                                Your payment information is secure and encrypted
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkout-form');
    const button = document.getElementById('place-order-btn');
    const loadingText = button.querySelector('.loading-text');
    const loadingSpinner = button.querySelector('.loading-spinner');

    form.addEventListener('submit', function() {
        // Disable button and show loading state
        button.disabled = true;
        loadingText.classList.add('hidden');
        loadingSpinner.classList.remove('hidden');
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>
@endpush
@endsection
