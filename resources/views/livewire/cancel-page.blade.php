<main class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-red-900/30 to-gray-800 py-12 px-4 sm:px-8 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-red-500/20 to-orange-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-red-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-red-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <section class="w-full max-w-lg mx-auto bg-white/10 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 flex flex-col items-center p-8 gap-6 relative z-10 hover:shadow-3xl transition-all duration-500">
        <div class="flex flex-col items-center gap-2">
            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-red-500/30 to-orange-500/30 backdrop-blur-md text-red-300 mb-2 shadow-2xl border border-red-400/30 animate-pulse">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-400 text-center uppercase tracking-wide">Payment Failed</h1>
            <p class="text-lg text-gray-300 text-center font-medium">Your order was cancelled due to a payment issue.</p>
        </div>
        <div class="w-full flex flex-col items-center gap-2 mt-4">
            <a href="/servers" class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 hover:from-blue-600 hover:to-green-600 text-white font-bold rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 text-center w-full transform hover:scale-105">Return to Products</a>
            <a href="/account/orders" class="inline-block px-6 py-3 bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-orange-600 hover:to-yellow-600 text-white font-bold rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 text-center w-full transform hover:scale-105">View My Orders</a>
        </div>
    </section>
</main>
