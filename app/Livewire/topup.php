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
        // Validate currency immediately on mount
        $currency = strtolower($currency);
        if (!in_array($currency, ['btc', 'xmr', 'sol'])) {
            abort(404, 'Unsupported currency');
        }

        $this->currency = $currency;
    }

    protected function rules()
    {
        return [
            'currency' => 'required|in:btc,xmr,sol',
            'amount' => 'required|numeric|min:0.00000001',
            'reference' => 'nullable|string|unique:wallet_transactions,reference',
        ];
    }

    public function topUp()
    {
        $this->validate();

        if (empty($this->reference)) {
            $this->reference = 'topup_' . strtoupper(Str::random(8));
        }

        $wallet = Auth::user()->getWallet($this->currency);
        $wallet->deposit($this->amount, $this->reference, [
            'description' => "Top-Up via {$this->currency}",
        ]);

        session()->flash('success', strtoupper($this->currency) . " wallet topped up successfully.");
        $this->reset(['amount', 'reference']);
    }

    public function render()
    {
        return view('livewire.topup');
    }
}
