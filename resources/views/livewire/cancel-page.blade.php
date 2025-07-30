@extends('layouts.app')

@section('content')
<main class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-900 to-green-600 py-12 px-4 sm:px-8">
    <section class="w-full max-w-lg mx-auto bg-white/90 dark:bg-yellow-700/90 rounded-2xl shadow-2xl border-2 border-yellow-600 flex flex-col items-center p-8 gap-6">
        <div class="flex flex-col items-center gap-2">
            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-red-100 text-red-600 mb-2 shadow-lg">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-red-600 text-center uppercase tracking-wide">Payment Failed</h1>
            <p class="text-lg text-gray-700 dark:text-white text-center font-medium">Your order was cancelled due to a payment issue.</p>
        </div>
        <div class="w-full flex flex-col items-center gap-2 mt-4">
            <a href="/servers" class="inline-block px-6 py-3 bg-green-900 text-white font-bold rounded-lg shadow hover:bg-green-700 transition-colors text-center w-full">Return to Products</a>
            <a href="/account/orders" class="inline-block px-6 py-3 bg-yellow-600 text-green-900 font-bold rounded-lg shadow hover:bg-yellow-500 transition-colors text-center w-full">View My Orders</a>
        </div>
    </section>
</main>
@endsection
