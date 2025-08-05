<section class="min-h-[60vh] flex items-center justify-center py-10 px-2 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <div class="w-full max-w-2xl bg-white/10 backdrop-blur-md rounded-2xl shadow-2xl p-8 md:p-10 border border-white/20 relative z-10 hover:shadow-3xl transition-all duration-500">
        <header class="mb-8 text-center">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md mx-auto mb-4 border border-blue-400/30">
                <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-2 tracking-tight">Transaction Detail</h1>
        </header>
        <article class="space-y-4 text-gray-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                    <span class="font-semibold text-blue-300">Reference:</span> 
                    <span class="font-mono text-gray-300">{{ $transaction->reference }}</span>
                </div>
                <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                    <span class="font-semibold text-blue-300">Type:</span> 
                    <span class="font-mono text-gray-300">{{ ucfirst($transaction->type) }}</span>
                </div>
                <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                    <span class="font-semibold text-blue-300">Status:</span> 
                    <span class="font-mono text-gray-300">{{ ucfirst($transaction->status) }}</span>
                </div>
                <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                    <span class="font-semibold text-blue-300">Amount:</span> 
                    <span class="font-mono text-gray-300">{{ Number::currency($transaction->amount) }}</span>
                </div>
                <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                    <span class="font-semibold text-blue-300">Date:</span> 
                    <span class="font-mono text-gray-300">{{ $transaction->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="sm:col-span-2 bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                    <span class="font-semibold text-blue-300">Description:</span> 
                    <span class="font-mono text-gray-300">{{ $transaction->description ?? '—' }}</span>
                </div>
            </div>

            @if ($transaction->qr_code_path)
                <div class="mt-6 flex flex-col items-center bg-white/5 backdrop-blur-md rounded-lg p-6 border border-white/10">
                    <p class="mb-4 font-semibold text-blue-300">QR Code:</p>
                    <div class="relative group">
                        <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" 
                             alt="QR Code" 
                             class="w-32 h-32 sm:w-40 sm:h-40 border border-white/30 rounded-lg shadow-lg mb-4 group-hover:scale-110 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <a href="{{ asset('storage/' . $transaction->qr_code_path) }}" 
                       download 
                       class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 hover:from-blue-600 hover:to-green-600 text-white font-bold rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 text-sm transform hover:scale-105">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download QR
                    </a>
                </div>
            @endif
        </article>
        <footer class="mt-8 text-center">
            <a href="{{ route('wallet.transactions.index') }}" 
               class="inline-flex items-center text-sm font-bold text-blue-300 hover:text-blue-200 transition-colors duration-300 group">
                <svg class="w-4 h-4 mr-2 group-hover:transform group-hover:-translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Transactions
            </a>
        </footer>
    </div>
</section>