<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use App\Models\Customer;
use App\Models\WalletTransaction;
use App\Http\Resources\WalletTransactionResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class WalletController extends Controller
{
    public function index()
    {
        $wallet = Auth::user()->wallet()->with('transactions')->first();

        return view('wallets.index', compact('wallet'));
    }

    public function show()
    {
        $wallet = Auth::user()->wallet()->with('transactions')->first();

        return view('wallets.show', compact('wallet'));
    }

    public function topUp(Request $request, $currency)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.0001',
            'reference' => 'required|string|unique:wallet_transactions,reference',
        ]);

        $wallet = Auth::user()->wallet;
        $wallet->deposit($request->amount, $request->reference, [
            'description' => "Top-up using " . strtoupper($currency),
        ]);

        return redirect()->route('wallet.topup', $currency)->with('success', 'Wallet topped up successfully.');
    }

    public function process(Request $request)
    {
        $request->validate([
            'currency' => 'required|in:btc,xmr,sol',
            'amount' => 'required|numeric|min:0.00001',
        ]);

        $wallet = Auth::user()->wallet;
        $wallet->deposit($request->amount, 'manual_' . now()->timestamp, [
            'description' => 'Manual crypto top-up',
        ]);

        return redirect()->route('wallet.topup', $request->currency)->with('success', 'Top-up successful!');
    }

    public function sync()
    {
        $wallet = Auth::user()->wallet;
        $wallet->syncWithBlockchain();

        return back()->with('success', 'Wallet balance synced successfully.');
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
    public function getWalletAddress()
    {
        $wallet = Auth::user()->wallet;

        return response()->json([
            'btc_address' => $wallet->btc_address,
            'xmr_address' => $wallet->xmr_address,
            'sol_address' => $wallet->sol_address,
        ]);
    }

    public function getWalletBalance()
    {
        $wallet = Auth::user()->wallet;

        return response()->json([
            'balance' => $wallet->balance,
        ]);
    }
    public function insufficient($currency)
    {
        return redirect()->route('wallet.topup', $currency)
            ->with('warning', "You do not have enough {$currency} in your wallet. Please top up.");
    }
    public function getWalletTransactions()
    {
        $wallet = Auth::user()->wallet;
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return view('livewire.transactions', compact('transactions'));
    }

    public function getWalletTransactionDownload(WalletTransaction $transaction)
    {
        $wallet = Auth::user()->wallet;

        if ($transaction->wallet_id !== $wallet->id) {
            abort(403);
        }

        $filePath = storage_path('app/' . $transaction->qr_code_path);
        return response()->download($filePath);
    }

    public function topUpForm($currency)
    {
        $wallet = Auth::user()->wallet;

        $qrPaths = [
            'BTC' => $wallet && filled($wallet->btc_qr) ? Storage::disk('public')->url($wallet->btc_qr) : '',
            'XMR' => $wallet && filled($wallet->xmr_qr) ? Storage::disk('public')->url($wallet->xmr_qr) : '',
            'SOL' => $wallet && filled($wallet->sol_qr) ? Storage::disk('public')->url($wallet->sol_qr) : '',
        ];

        return view('livewire.topup', [
            'wallet' => $wallet,
            'qrPaths' => $qrPaths,
            'currency' => $request->currency ?? 'BTC',
        ]);
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

