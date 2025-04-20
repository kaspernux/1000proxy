<div class="w-full py-10 bg-gradient-to-r from-green-900 to-green-600 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 md:px-8 lg:px-10">
        <h2 class="text-4xl font-bold font-mono text-white text-center mb-8">Wallet <span class="text-yellow-400">Transactions</span></h2>

        <div class="overflow-hidden rounded-lg shadow-lg bg-white dark:bg-gray-900">
            <table class="min-w-full table-auto">
                <thead class="bg-green-800 text-white font-mono text-sm">
                    <tr>
                        <th class="px-4 py-3 text-left">Reference</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">QR Code</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 dark:text-white font-mono text-sm divide-y divide-green-200">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-green-100 dark:hover:bg-green-800">
                            <td class="px-4 py-3">{{ $transaction->reference }}</td>
                            <td class="px-4 py-3 capitalize">{{ $transaction->type }}</td>
                            <td class="px-4 py-3 font-bold {{ $transaction->amount < 0 ? 'text-red-500' : 'text-green-500' }}">
                                {{ $transaction->amount }}
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
                                        <span class="text-gray-700">{{ ucfirst($transaction->status) }}</span>
                                @endswitch
                            </td>
                            <td class="px-4 py-3">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3">
                                @if($transaction->qr_code_path)
                                    <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" class="w-12 h-12 rounded border" alt="QR">
                                @else
                                    <span class="text-gray-400 italic">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center px-4 py-6 text-white font-semibold">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 bg-white dark:bg-gray-900">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
