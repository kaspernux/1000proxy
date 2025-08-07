@if(isset($transaction))
    <!-- Single Transaction View -->
    <section class="w-full py-10 bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 min-h-screen flex items-center justify-center relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
            <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
            <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
        </div>

        <div class="max-w-2xl w-full mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="bg-white/10 backdrop-blur-md rounded-2xl shadow-2xl p-8 space-y-6 border border-white/20 hover:shadow-3xl transition-all duration-500">
                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md mx-auto mb-4 border border-blue-400/30">
                    <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 text-center mb-6">Transaction Details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-base text-gray-200">
                    <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                        <dt class="font-semibold text-blue-300">Reference</dt>
                        <dd class="font-mono text-gray-300">{{ $transaction->reference }}</dd>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                        <dt class="font-semibold text-blue-300">Type</dt>
                        <dd class="font-mono text-gray-300">{{ ucfirst($transaction->type) }}</dd>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                        <dt class="font-semibold text-blue-300">Status</dt>
                        <dd>
                            @switch($transaction->status)
                                @case('completed')
                                    <span class="inline-flex items-center px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-xs font-bold border border-green-400/30">
                                        <x-heroicon-o-check-circle class="w-4 h-4 mr-1" /> Completed
                                    </span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-xs font-bold border border-yellow-400/30">
                                        <x-heroicon-o-clock class="w-4 h-4 mr-1" /> Pending
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-xs font-bold border border-red-400/30">
                                        <x-heroicon-o-x-circle class="w-4 h-4 mr-1" /> Failed
                                    </span>
                                    @break
                                @default
                                    <span class="text-gray-300">{{ ucfirst($transaction->status) }}</span>
                            @endswitch
                        </dd>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                        <dt class="font-semibold text-blue-300">Amount</dt>
                        <dd class="font-bold font-mono {{ $transaction->amount < 0 ? 'text-red-400' : 'text-green-400' }}">{{ Number::currency($transaction->amount) }}</dd>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                        <dt class="font-semibold text-blue-300">Date</dt>
                        <dd class="font-mono text-gray-300">{{ $transaction->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    <div class="sm:col-span-2 bg-white/5 backdrop-blur-md rounded-lg p-4 border border-white/10 hover:border-white/20 transition-all duration-300">
                        <dt class="font-semibold text-blue-300">Description</dt>
                        <dd class="font-mono text-gray-300">{{ $transaction->description ?? '—' }}</dd>
                    </div>
                </dl>
                @if($transaction->qr_code_path)
                    <div class="flex flex-col items-center mt-6 bg-white/5 backdrop-blur-md rounded-lg p-6 border border-white/10">
                        <p class="mb-4 font-semibold text-blue-300">QR Code</p>
                        <div class="relative group">
                            <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" 
                                 alt="QR Code" 
                                 class="w-32 h-32 border border-white/30 rounded-lg shadow-lg mb-4 group-hover:scale-110 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        <a href="{{ route('wallet.transactions.download', $transaction->id) }}" 
                           class="text-blue-300 text-sm hover:text-blue-200 transition-colors duration-300 inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download QR
                        </a>
                    </div>
                @endif
                <div class="flex justify-center">
                    <a href="{{ route('wallet.transactions.index') }}" 
                       class="inline-flex items-center gap-2 mt-6 text-sm font-bold text-blue-300 hover:text-blue-200 transition-colors duration-300 group">
                        <x-heroicon-o-arrow-left class="w-4 h-4 group-hover:transform group-hover:-translate-x-1 transition-transform duration-300" /> 
                        Back to Transactions
                    </a>
                </div>
            </div>
        </div>
    </section>

@elseif(isset($transactions))
    <!-- Transaction List Table -->
    <section class="w-full py-10 bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 min-h-screen relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
            <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
            <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 relative z-10">
            <div class="bg-white/10 backdrop-blur-md rounded-2xl shadow-2xl overflow-hidden border border-white/20">
                <div class="text-center py-8 border-b border-white/20">
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md mx-auto mb-4 border border-blue-400/30">
                        <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-4">
                        Wallet <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">Transactions</span>
                    </h2>
                    
                    <!-- Enhanced Transaction Management Link -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-6">
                        <div class="text-sm text-gray-300 bg-blue-500/10 backdrop-blur-md rounded-lg px-4 py-2 border border-blue-400/30">
                            <span class="font-semibold">Quick Overview</span> - Basic transaction history
                        </div>
                        <div class="flex items-center">
                            <div class="h-px bg-gradient-to-r from-transparent via-gray-400 to-transparent w-8"></div>
                            <span class="px-3 text-xs text-gray-400 font-medium">OR</span>
                            <div class="h-px bg-gradient-to-r from-transparent via-gray-400 to-transparent w-8"></div>
                        </div>
                        <a href="/account/wallet-management"
                           class="group inline-flex items-center gap-3 bg-gradient-to-r from-green-600/20 to-emerald-600/20 hover:from-green-600/30 hover:to-emerald-600/30 backdrop-blur-md text-green-300 hover:text-green-200 font-semibold px-6 py-3 rounded-xl border border-green-400/30 hover:border-green-400/50 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4"></path>
                            </svg>
                            <span>Advanced Transaction Manager</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="mt-4 text-xs text-gray-400 max-w-2xl mx-auto">
                        💡 <strong>Pro Tip:</strong> Use the Advanced Transaction Manager for detailed analytics, export options, advanced filtering, and comprehensive transaction management tools.
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto whitespace-nowrap">
                        <thead class="bg-gradient-to-r from-blue-600/30 to-purple-600/30 backdrop-blur-md text-white text-xs sm:text-sm border-b border-white/20">
                            <tr>
                            <th class="px-4 py-3 text-left">Reference</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Amount</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">QR</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 dark:text-white font-mono text-xs sm:text-sm divide-y divide-green-200">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-green-100 dark:hover:bg-green-800 transition">
                                <td class="px-4 py-3 break-all">{{ $transaction->reference }}</td>
                                <td class="px-4 py-3 capitalize">{{ $transaction->type }}</td>
                                <td class="px-4 py-3 font-bold {{ $transaction->amount < 0 ? 'text-red-500' : 'text-green-500' }}">
                                    {{ Number::currency($transaction->amount) }}
                                </td>
                                <td class="px-4 py-3">
                                    @switch($transaction->status)
                                        @case('completed')
                                            <span class="inline-flex items-center px-2 py-1 bg-green-200 text-green-800 rounded text-xs font-bold">
                                                <x-heroicon-o-check-circle class="w-4 h-4 mr-1" /> Completed
                                            </span>
                                            @break
                                        @case('pending')
                                            <span class="inline-flex items-center px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs font-bold">
                                                <x-heroicon-o-clock class="w-4 h-4 mr-1" /> Pending
                                            </span>
                                            @break
                                        @case('failed')
                                            <span class="inline-flex items-center px-2 py-1 bg-red-200 text-red-800 rounded text-xs font-bold">
                                                <x-heroicon-o-x-circle class="w-4 h-4 mr-1" /> Failed
                                            </span>
                                            @break
                                        @default
                                            <span class="text-gray-700 dark:text-gray-200">{{ ucfirst($transaction->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="px-4 py-3">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if($transaction->qr_code_path)
                                        <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" class="w-10 h-10 sm:w-12 sm:h-12 rounded border mx-auto" alt="QR">
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('wallet.transactions.show', $transaction->id) }}" class="text-yellow-500 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if(method_exists($transactions, 'links'))
                    <div class="p-4 bg-white dark:bg-gray-900 rounded-b-2xl flex justify-center">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
