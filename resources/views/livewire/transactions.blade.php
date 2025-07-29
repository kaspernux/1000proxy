@extends('layouts.app')

@section('content')
@if(isset($transaction))
    {{-- SINGLE TRANSACTION VIEW --}}
    <div class="w-full py-10 bg-gradient-to-r from-green-900 to-green-600 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-10">
            <h2 class="text-3xl font-bold font-mono text-white text-center mb-8">Transaction Details</h2>

            <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6 space-y-4 text-sm font-mono text-gray-700 dark:text-white">
                <p><strong>Reference:</strong> {{ $transaction->reference }}</p>
                <p><strong>Type:</strong> {{ ucfirst($transaction->type) }}</p>
                <p><strong>Status:</strong> {{ ucfirst($transaction->status) }}</p>
                <p><strong>Amount:</strong> {{ Number::currency($transaction->amount) }}</p>
                <p><strong>Date:</strong> {{ $transaction->created_at->format('d M Y, H:i') }}</p>
                <p><strong>Description:</strong> {{ $transaction->description ?? '\u2014' }}</p>

                @if($transaction->qr_code_path)
                    <div class="mt-4">
                        <p class="mb-2 font-semibold">QR Code:</p>
                        <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" alt="QR Code" class="w-32 h-32 border rounded shadow">
                        <a href="{{ route('wallet.transactions.download', $transaction->id) }}" class="text-yellow-500 text-sm hover:underline mt-2 inline-block">Download QR</a>
                    </div>
                @endif

                <a href="{{ route('wallet.transactions.index') }}" class="inline-block mt-6 text-sm font-bold text-yellow-400 hover:underline">\u2190 Back to Transactions</a>
            </div>
        </div>
    </div>

@elseif(isset($transactions))
    {{-- TRANSACTION LIST TABLE --}}
    <div class="w-full py-10 bg-gradient-to-r from-green-900 to-green-600 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">
            <h2 class="text-4xl font-bold font-mono text-white text-center mb-8">
                Wallet <span class="text-yellow-400">Transactions</span>
            </h2>

            <div class="overflow-x-auto rounded-lg shadow-lg bg-white dark:bg-gray-900">
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
                            <tr class="hover:bg-green-100 dark:hover:bg-green-800">
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
                                            <span class="text-gray-700">{{ ucfirst($transaction->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="px-4 py-3">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if($transaction->qr_code_path)
                                        <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" class="w-10 h-10 sm:w-12 sm:h-12 rounded border" alt="QR">
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td class="px-4 py-3">{{ $transaction->reference }}</td>
                                <td class="px-4 py-3">{{ ucfirst($transaction->type) }}</td>
                                <td class="px-4 py-3">{{ ucfirst($transaction->status) }}</td>
                                <td class="px-4 py-3">{{ Number::currency($transaction->amount) }}</td>
                                <td class="px-4 py-3">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('wallet.transactions.show', $transaction->id) }}" class="text-yellow-500 hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endif
@endsection

                @if(method_exists($transactions, 'links'))
                    <div class="p-4 bg-white dark:bg-gray-900">
                        {{ $transactions->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
@endif


