<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Transactions extends Component
{
    use WithPagination;

    public function render()
{
    $transactions = \App\Models\WalletTransaction::whereIn(
        'wallet_id',
        Auth::user()->wallets()->pluck('id')
    )->latest()->paginate(10);

    return view('livewire.transactions', compact('transactions'));
}
    public function download($transactionId)
    {
        $transaction = \App\Models\WalletTransaction::findOrFail($transactionId);
        $filePath = storage_path('app/' . $transaction->qr_code_path);

        return response()->download($filePath);
    }

}
