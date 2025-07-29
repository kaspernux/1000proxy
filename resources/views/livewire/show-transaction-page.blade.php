
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-6 bg-white dark:bg-gray-900 rounded-xl shadow">
    <h1 class="text-2xl font-bold text-green-700 dark:text-green-300 mb-6">Transaction Detail</h1>

    <div class="space-y-4 text-gray-800 dark:text-white">
        <p><strong>Reference:</strong> {{ $transaction->reference }}</p>
        <p><strong>Type:</strong> {{ ucfirst($transaction->type) }}</p>
        <p><strong>Status:</strong> {{ ucfirst($transaction->status) }}</p>
        <p><strong>Amount:</strong> {{ Number::currency($transaction->amount) }}</p>
        <p><strong>Date:</strong> {{ $transaction->created_at->format('d M Y, H:i') }}</p>
        <p><strong>Description:</strong> {{ $transaction->description ?? '—' }}</p>

        @if ($transaction->qr_code_path)
            <div class="mt-4">
                <p class="mb-2 font-semibold">QR Code:</p>
                <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" alt="QR Code" class="w-40 h-40 border rounded shadow">
                <div class="mt-2">
                    <a href="{{ route('wallet.transactions.show', $transaction->id) }}" class="text-sm text-yellow-600 hover:underline">Download QR</a>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('wallet.transactions.index') }}" class="text-sm font-bold text-green-600 hover:underline">← Back to Transactions</a>
    </div>
</div>
@endsection
