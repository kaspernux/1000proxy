<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Topup extends Component
{
    public $currency;
    public $amount;
    public $reference;

    public function mount($currency)
    {
        $currency = strtolower($currency);
        if (!in_array($currency, ['btc', 'xmr', 'sol'])) {
            abort(404, 'Unsupported currency.');
        }

        $this->currency = $currency;
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

        $wallet = Auth::user()->wallet;

        $this->reference = $this->reference ?: 'topup_' . strtoupper(Str::random(10));

        $wallet->deposit($this->amount, $this->reference, [
            'description' => 'Top-up using ' . strtoupper($this->currency),
        ]);

        session()->flash('success', 'Deposit request submitted successfully.');
        $this->reset('amount', 'reference');
        $this->dispatch('submitEnded');
    }

    public function render()
    {
        return view('livewire.topup', [
            'wallet' => Auth::guard('customer')->user()->wallet,
        ]);

    }
}
