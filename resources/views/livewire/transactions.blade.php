@if(isset($transaction))
    <!-- Single Transaction View -->
    <section class="w-full py-10 bg-gradient-to-r from-green-900 to-green-600 min-h-screen flex items-center justify-center">
        <div class="max-w-2xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-8 space-y-6">
                <h2 class="text-3xl font-extrabold font-mono text-green-900 dark:text-white text-center mb-6">Transaction Details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-base font-mono text-gray-700 dark:text-gray-100">
                    <div>
                        <dt class="font-semibold">Reference</dt>
                        <dd>{{ $transaction->reference }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Type</dt>
                        <dd>{{ ucfirst($transaction->type) }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Status</dt>
                        <dd>
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
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Amount</dt>
                        <dd class="font-bold {{ $transaction->amount < 0 ? 'text-red-500' : 'text-green-500' }}">{{ Number::currency($transaction->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Date</dt>
                        <dd>{{ $transaction->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-semibold">Description</dt>
                        <dd>{{ $transaction->description ?? '—' }}</dd>
                    </div>
                </dl>
                @if($transaction->qr_code_path)
                    <div class="flex flex-col items-center mt-6">
                        <p class="mb-2 font-semibold">QR Code</p>
                        <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" alt="QR Code" class="w-32 h-32 border rounded shadow mb-2">
                        <a href="{{ route('wallet.transactions.download', $transaction->id) }}" class="text-yellow-500 text-sm hover:underline mt-2 inline-block">Download QR</a>
                    </div>
                @endif
                <div class="flex justify-center">
                    <a href="{{ route('wallet.transactions.index') }}" class="inline-flex items-center gap-1 mt-6 text-sm font-bold text-yellow-500 hover:underline">
                        <x-heroicon-o-arrow-left class="w-4 h-4" /> Back to Transactions
                    </a>
                </div>
            </div>
        </div>
    </section>

@elseif(isset($transactions))
    <!-- Transaction List Table -->
    <section class="w-full py-10 bg-gradient-to-r from-green-900 to-green-600 min-h-screen">
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-x-auto">
                <h2 class="text-3xl sm:text-4xl font-extrabold font-mono text-green-900 dark:text-white text-center py-8">
                    Wallet <span class="text-yellow-400">Transactions</span>
                </h2>
                <table class="min-w-full table-auto whitespace-nowrap">
                    <thead class="bg-green-800 text-white font-mono text-xs sm:text-sm">
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
