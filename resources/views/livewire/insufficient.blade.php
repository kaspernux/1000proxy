@extends('layouts.app')

@section('content')
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
     class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-r from-green-900 to-green-600 px-6">

    <div class="bg-white text-green-900 text-center rounded-xl shadow-2xl p-10 w-full max-w-md">
        <h2 class="text-3xl font-bold font-mono mb-4 flex items-center gap-3"><x-custom-icon name="x-circle" class="w-8 h-8 text-red-400" /> Insufficient Balance</h2>

        <p class="text-lg font-mono mb-6">
            You do not have enough <strong class="text-yellow-600">{{ strtoupper($currency) }}</strong> in your wallet to complete this transaction.
        </p>

        <div class="text-sm font-semibold bg-yellow-100 text-yellow-800 px-4 py-2 rounded-md inline-block">
            Redirecting to top-up page in <span x-text="countdown">5</span> second<span x-text="countdown !== 1 ? 's' : ''"></span>...
        </div>

        <div class="mt-6">
            <a href="{{ route('wallet.topup', $currency) }}"
               class="inline-block bg-green-600 hover:bg-yellow-600 text-white font-bold font-mono px-6 py-2 rounded-lg transition">
                Go Top-Up Now
            </a>
        </div>
    </div>
</div>
@endsection
