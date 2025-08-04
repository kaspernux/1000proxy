<section class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-900 via-green-700 to-green-600 px-4 py-12">
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
         class="w-full max-w-md">
        <article class="bg-white/90 dark:bg-gray-900/90 text-green-900 dark:text-green-200 rounded-2xl shadow-2xl p-8 md:p-10 flex flex-col items-center">
            <header class="flex flex-col items-center mb-6">
                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-3">
                    <x-custom-icon name="x-circle" class="w-10 h-10 text-red-400" />
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold font-mono mb-2 tracking-tight">Insufficient Balance</h1>
            </header>
            <section class="mb-6 w-full">
                <p class="text-base md:text-lg font-mono text-center">
                    You do not have enough <span class="text-yellow-600 font-bold">{{ strtoupper($currency) }}</span> in your wallet to complete this transaction.
                </p>
            </section>
            <section class="mb-6 w-full flex flex-col items-center">
                <div class="text-sm font-semibold bg-yellow-100 text-yellow-800 px-4 py-2 rounded-md inline-block mb-2">
                    Redirecting to top-up page in <span x-text="countdown">5</span> second<span x-text="countdown !== 1 ? 's' : ''"></span>...
                </div>
                <a href="{{ route('wallet.topup', $currency) }}"
                   class="inline-block bg-gradient-to-r from-green-600 to-yellow-600 hover:from-yellow-600 hover:to-green-600 text-white font-bold font-mono px-6 py-2 rounded-lg shadow-md transition-all duration-200">
                    Go Top-Up Now
                </a>
            </section>
            <footer class="w-full mt-4 border-t border-green-100 dark:border-green-800 pt-4 text-xs text-gray-500 text-center">
                You will be redirected automatically, or you can top up manually.
            </footer>
        </article>
    </div>
</section>
