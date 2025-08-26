@if(isset($transactions))
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
                                    @php
                                        $orderId = $transaction->invoice->order_id ?? ($transaction->metadata['order_id'] ?? null);
                                        if (!$orderId && !empty($transaction->reference)) {
                                            if (preg_match('/^order[_-](\d+)/i', $transaction->reference, $m)) {
                                                $orderId = (int)($m[1] ?? 0) ?: null;
                                            }
                                        }
                                    @endphp
                                    @if($orderId)
                                        <a href="{{ route('customer.invoice.download', ['order' => $orderId]) }}" class="text-blue-400 hover:underline">Download Invoice (PDF)</a>
                                    @else
                                        <span class="text-gray-400">No Invoice</span>
                                    @endif
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
