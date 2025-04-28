<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TopupWallet extends Component
{
    public $currency;
    public $amount;
    public $reference;
    public $wallet;

    protected $listeners = ['refreshWallet' => '$refresh'];

    public function mount($currency)
    {
        $currency = strtolower($currency);

        if (!in_array($currency, ['btc', 'xmr', 'sol'])) {
            abort(404, 'Unsupported currency.');
        }

        $this->currency = $currency;
        $this->wallet = Auth::guard('customer')->user()->wallet;
    }

    protected function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.00000001',
            'reference' => 'nullable|string|unique:wallet_transactions,reference',
        ];
    }

    public function topUp()
    {
        $this->validate();

        $wallet = Auth::guard('customer')->user()->wallet;

        $this->reference = $this->reference ?: 'topup_' . strtoupper(Str::random(10));

        $wallet->deposit($this->amount, $this->reference, [
            'description' => 'Top-up using ' . strtoupper($this->currency),
        ]);

        session()->flash('success', '✅ Deposit request submitted successfully.');
        $this->reset('amount', 'reference');

        $this->dispatch('submitEnded'); // Alpine event for animation
        $this->dispatch('refreshWallet'); // Refresh wallet info
    }

    public function render()
    {
        $this->wallet = Auth::guard('customer')->user()->wallet->fresh();

        return view('livewire.topup-wallet', [
            'wallet' => $this->wallet,
        ]);
    }
}
