
@extends('layouts.app')

@section('content')

<section class="min-h-[60vh] flex items-center justify-center py-10 px-2">
    <div class="w-full max-w-2xl bg-white/90 dark:bg-green-900 rounded-2xl shadow-xl p-8 md:p-10">
        <header class="mb-8 text-center">
            <h1 class="text-2xl md:text-3xl font-extrabold text-green-700 dark:text-green-300 mb-2 tracking-tight drop-shadow-lg">Transaction Detail</h1>
        </header>
        <article class="space-y-4 text-green-900 dark:text-green-100">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><span class="font-semibold">Reference:</span> <span class="font-mono">{{ $transaction->reference }}</span></div>
                <div><span class="font-semibold">Type:</span> <span class="font-mono">{{ ucfirst($transaction->type) }}</span></div>
                <div><span class="font-semibold">Status:</span> <span class="font-mono">{{ ucfirst($transaction->status) }}</span></div>
                <div><span class="font-semibold">Amount:</span> <span class="font-mono">{{ Number::currency($transaction->amount) }}</span></div>
                <div><span class="font-semibold">Date:</span> <span class="font-mono">{{ $transaction->created_at->format('d M Y, H:i') }}</span></div>
                <div class="sm:col-span-2"><span class="font-semibold">Description:</span> <span class="font-mono">{{ $transaction->description ?? '—' }}</span></div>
            </div>

            @if ($transaction->qr_code_path)
                <div class="mt-6 flex flex-col items-center">
                    <p class="mb-2 font-semibold text-green-700 dark:text-green-300">QR Code:</p>
                    <img src="{{ asset('storage/' . $transaction->qr_code_path) }}" alt="QR Code" class="w-32 h-32 sm:w-40 sm:h-40 border border-green-100 dark:border-green-800 rounded-lg shadow mb-2">
                    <a href="{{ asset('storage/' . $transaction->qr_code_path) }}" download class="inline-block mt-2 px-6 py-2 bg-gradient-to-r from-green-600 to-yellow-500 text-white font-bold rounded-lg shadow hover:from-yellow-600 hover:to-green-600 transition-all duration-200 text-sm">Download QR</a>
                </div>
            @endif
        </article>
        <footer class="mt-8 text-center">
            <a href="{{ route('wallet.transactions.index') }}" class="text-sm font-bold text-green-700 dark:text-green-300 hover:underline">← Back to Transactions</a>
        </footer>
    </div>
</section>
@endsection
