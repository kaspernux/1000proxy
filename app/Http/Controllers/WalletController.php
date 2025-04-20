<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;

class WalletController extends Controller
{
    public function index() {
        $wallets = Auth::user()->wallets()->with('transactions')->get();

        return view('wallets.index', compact('wallets'));
    }

    public function show($currency) {
        $wallet = Auth::user()->wallet($currency)->load('transactions');

        return view('wallets.show', compact('wallet'));
    }

    public function topUp(Request $request, $currency) {
        $request->validate([
            'amount' => 'required|numeric|min:0.0001',
            'reference' => 'required|string|unique:wallet_transactions,reference',
        ]);

        $wallet = Auth::user()->getWallet($currency);
        $wallet->deposit($request->amount, $request->reference, [
            'description' => 'User initiated top-up',
        ]);

        return redirect()->route('wallet.show', $currency)->with('success', 'Wallet topped up successfully.');
    }


    public function process(Request $request)
    {
        $request->validate([
            'currency' => 'required|in:btc,xmr,sol',
            'amount' => 'required|numeric|min:0.00001',
        ]);

        $wallet = Auth::user()->getWallet($request->currency);
        $wallet->deposit($request->amount, 'manual_' . now()->timestamp, [
            'description' => 'Manual crypto top-up'
        ]);

        return redirect()->route('wallet.topup')->with('success', 'Top-up successful!');
    }
    
    
    public function store(Request $request)
    {
        $request->validate([
            'currency' => 'required|in:btc,xmr,sol',
            'address' => 'required|string|max:255',
            'deposit_tag' => 'nullable|string|max:255',
            'network' => 'nullable|string|max:255',
        ]);

        $wallet = Auth::user()->wallets()->create($request->all());

        return redirect()->route('wallet.topup')->with('success', 'Wallet created successfully!');
    }
    
    public function update(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);

        $request->validate([
            'address' => 'required|string|max:255',
            'deposit_tag' => 'nullable|string|max:255',
            'network' => 'nullable|string|max:255',
        ]);

        $wallet->update($request->all());

        return redirect()->route('wallet.topup')->with('success', 'Wallet updated successfully!');
    }
    public function destroy(Wallet $wallet)
    {
        $this->authorize('delete', $wallet);
        $wallet->delete();

        return redirect()->route('wallet.topup')->with('success', 'Wallet deleted successfully!');
    }
    public function downloadQrCode(Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        $filePath = storage_path('app/' . $wallet->qr_code_path);
        return response()->download($filePath);
    }
    public function sync(Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        $wallet->syncWithBlockchain();

        return redirect()->route('wallet.topup')->with('success', 'Wallet synced successfully!');
    }
    public function generateQrCode(Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        $wallet->generateQrCode();

        return redirect()->route('wallet.topup')->with('success', 'QR code generated successfully!');
    }
    public function setDefault(Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        Auth::user()->wallets()->update(['is_default' => false]);
        $wallet->update(['is_default' => true]);

        return redirect()->route('wallet.topup')->with('success', 'Default wallet set successfully!');
    }
    public function unsetDefault(Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        $wallet->update(['is_default' => false]);

        return redirect()->route('wallet.topup')->with('success', 'Default wallet unset successfully!');
    }
    public function getWalletAddress(Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        return response()->json([
            'address' => $wallet->address,
            'deposit_tag' => $wallet->deposit_tag,
        ]);
    }
    public function getWalletBalance(Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        return response()->json([
            'balance' => $wallet->balance,
        ]);
    }
    public function insufficient($currency)
    {
        return redirect()->route('wallet.topup', $currency)
            ->with('warning', "You do not have enough {$currency} in your wallet. Please top up.");
    }
    public function getWalletTransactions(Wallet $wallet)
    {
        $this->authorize('view', $wallet);
        $transactions = $wallet->transactions()->latest()->paginate(20);
        return view('livewire.transactions', compact('transactions'));
    }
    

    public function getWalletTransactionDownload(Wallet $wallet, $transactionId)
    {
        $this->authorize('view', $wallet);
        $transaction = $wallet->transactions()->findOrFail($transactionId);
        $filePath = storage_path('app/' . $transaction->qr_code_path);
        return response()->download($filePath);
    }

    public function topUpForm($currency)
        {
            $wallet = Auth::user()->getWallet($currency);
            return view('livewire.topup', compact('wallet'));
    }

    public function toArray($request) {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'transactions' => WalletTransactionResource::collection($this->whenLoaded('transactions')),
            'created_at' => $this->created_at,
        ];
    }

    
}

