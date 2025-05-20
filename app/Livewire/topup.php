<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\WalletTransaction;
use App\Services\CryptoAddressService;

class Topup extends Component
{
    public $currency;
    public $amount;
    public $reference;
    public $depositAddress;

    protected $user;
    protected $wallet;

    public function mount($currency)
    {
        $currency = strtolower($currency);
        if (!in_array($currency, ['btc', 'xmr', 'sol'])) {
            abort(404, 'Unsupported currency.');
        }

        $this->currency = $currency;
        $this->user = Auth::guard('customer')->user();
        $this->wallet = $this->user->wallet;
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

        $this->reference = $this->reference ?: 'TOPUP_' . strtoupper(Str::random(12));
        $address = app(CryptoAddressService::class)->generateAddress($this->currency);

        // Store a pending wallet transaction
        WalletTransaction::create([
            'wallet_id' => $this->wallet->id,
            'customer_id' => $this->user->id,
            'type' => 'deposit',
            'status' => 'pending',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'address' => $address,
            'description' => 'Top-up request using ' . strtoupper($this->currency),
        ]);

        $this->depositAddress = $address;

        session()->flash('success', 'Deposit request initiated. Please send the exact amount to the generated address.');
        $this->reset('amount', 'reference');
        $this->dispatch('submitEnded');
    }

    public function render()
    {
        return view('livewire.topup', [
            'wallet' => $this->wallet,
            'depositAddress' => $this->depositAddress,
        ]);
    }
}
