<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Wallet Balance Card -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-white/90">Current Balance</h3>
                    <p class="text-3xl font-bold">${{ number_format($this->walletBalance, 2) }}</p>
                    <p class="text-sm text-white/80 mt-1">Available for purchases</p>
                </div>
                <div class="text-right">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <x-heroicon-o-wallet class="w-8 h-8 text-white" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-plus class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">Add Funds</h4>
                        <p class="text-sm text-gray-500">Top up your wallet balance</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-arrow-up-tray class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">Withdraw</h4>
                        <p class="text-sm text-gray-500">
                            @if($this->walletBalance >= 10)
                                Withdraw your funds
                            @else
                                Minimum $10 required
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-document-text class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">History</h4>
                        <p class="text-sm text-gray-500">View transaction history</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Deposits</p>
                        <p class="text-lg font-semibold text-gray-900">
                            ${{ number_format(\Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('type', 'deposit')->where('status', 'completed')->sum('amount'), 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <x-heroicon-o-shopping-cart class="w-4 h-4 text-blue-600" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Purchases</p>
                        <p class="text-lg font-semibold text-gray-900">
                            ${{ number_format(\Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('type', 'purchase')->sum('amount'), 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <x-heroicon-o-arrow-up-tray class="w-4 h-4 text-yellow-600" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Withdrawals</p>
                        <p class="text-lg font-semibold text-gray-900">
                            ${{ number_format(\Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('type', 'withdrawal')->where('status', 'completed')->sum('amount'), 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <x-heroicon-o-clock class="w-4 h-4 text-purple-600" />
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Pending</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ \Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('status', 'pending')->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Transactions</h3>
                <div class="space-y-4">
                    @php
                        $recentTransactions = \Illuminate\Support\Facades\DB::table('wallet_transactions')
                            ->where('customer_id', auth()->guard('customer')->id())
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp

                    @forelse($recentTransactions as $transaction)
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 last:border-b-0">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                        {{ $transaction->type === 'deposit' ? 'bg-green-100' :
                                           ($transaction->type === 'withdrawal' ? 'bg-yellow-100' :
                                            ($transaction->type === 'purchase' ? 'bg-blue-100' : 'bg-gray-100')) }}">
                                        @if($transaction->type === 'deposit')
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                                        @elseif($transaction->type === 'withdrawal')
                                            <x-heroicon-o-arrow-up-tray class="w-4 h-4 text-yellow-600" />
                                        @elseif($transaction->type === 'purchase')
                                            <x-heroicon-o-shopping-cart class="w-4 h-4 text-blue-600" />
                                        @else
                                            <x-heroicon-o-currency-dollar class="w-4 h-4 text-gray-600" />
                                        @endif
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $transaction->description ?? ucfirst($transaction->type) }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($transaction->created_at)->format('M j, Y H:i') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium
                                    {{ $transaction->type === 'deposit' || $transaction->type === 'refund' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->type === 'deposit' || $transaction->type === 'refund' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' :
                                           ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                            ($transaction->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No transactions</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding funds to your wallet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Supported Payment Methods</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl mb-2">₿</div>
                        <p class="text-sm font-medium text-gray-900">Bitcoin</p>
                        <p class="text-xs text-gray-500">BTC Network</p>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl mb-2">ⓧ</div>
                        <p class="text-sm font-medium text-gray-900">Monero</p>
                        <p class="text-xs text-gray-500">XMR Network</p>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl mb-2">◎</div>
                        <p class="text-sm font-medium text-gray-900">Solana</p>
                        <p class="text-xs text-gray-500">SOL Network</p>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl mb-2">₮</div>
                        <p class="text-sm font-medium text-gray-900">USDT</p>
                        <p class="text-xs text-gray-500">TRC20</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallet Security Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-4">Wallet Security & Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-blue-800 mb-2">Security Features</h4>
                    <ul class="space-y-1 text-sm text-blue-700">
                        <li>• All transactions are encrypted and secured</li>
                        <li>• Automatic fraud detection and prevention</li>
                        <li>• Real-time transaction monitoring</li>
                        <li>• Two-factor authentication protection</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-blue-800 mb-2">Transaction Limits</h4>
                    <ul class="space-y-1 text-sm text-blue-700">
                        <li>• Minimum deposit: $5.00</li>
                        <li>• Maximum deposit: $1,000.00</li>
                        <li>• Minimum withdrawal: $10.00</li>
                        <li>• Processing time: 24-48 hours</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
