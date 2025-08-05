<section class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-red-900/30 to-gray-800 px-4 py-12 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-red-500/20 to-orange-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-red-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-red-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <div x-data="{ countdown: 5 }"
         x-init="
            let timer = setInterval(() => {
                if (countdown <= 1) {
                    clearInterval(timer);
                    window.location.href = '{{ route('wallet.topup', $currency) }}';
                } else {
                    countdown--;
                }
            }, 1000);
         "
         class="w-full max-w-md relative z-10">
        <article class="bg-white/10 backdrop-blur-md text-white rounded-2xl shadow-2xl p-8 md:p-10 flex flex-col items-center border border-white/20 hover:shadow-3xl transition-all duration-500">
            <header class="flex flex-col items-center mb-6">
                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-red-500/30 to-orange-500/30 backdrop-blur-md mb-3 border border-red-400/30 animate-pulse">
                    <x-custom-icon name="x-circle" class="w-10 h-10 text-red-300" />
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-400 mb-2 tracking-tight">Insufficient Balance</h1>
            </header>
            <section class="mb-6 w-full">
                <p class="text-base md:text-lg text-center text-gray-300">
                    You do not have enough <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400 font-bold">{{ strtoupper($currency) }}</span> in your wallet to complete this transaction.
                </p>
            </section>
            <section class="mb-6 w-full flex flex-col items-center">
                <div class="text-sm font-semibold bg-yellow-500/20 text-yellow-300 px-4 py-3 rounded-lg inline-block mb-4 border border-yellow-400/30 backdrop-blur-md">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Redirecting to top-up page in <span x-text="countdown">5</span> second<span x-text="countdown !== 1 ? 's' : ''"></span>...
                </div>
                <a href="{{ route('wallet.topup', $currency) }}"
                   class="inline-block bg-gradient-to-r from-green-600 to-blue-600 hover:from-blue-600 hover:to-green-600 text-white font-bold px-6 py-3 rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Go Top-Up Now
                </a>
            </section>
            <footer class="w-full mt-4 border-t border-white/20 pt-4 text-xs text-gray-400 text-center">
                You will be redirected automatically, or you can top up manually.
            </footer>
        </article>
    </div>
</section>
