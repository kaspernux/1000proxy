<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CryptoAddressService;
use App\Models\Wallet;
use App\Models\Customer;
use App\Http\Resources\WalletTransactionResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource;
use Illuminate\Support\Facades\Redirect;

class WalletTransactionController extends Controller
{
    public function index()
    {
        $transactions = WalletTransaction::whereHas('wallet', function ($query) {
            $query->where('customer_id', Auth::id());
        })->latest()->paginate(20);

        return view('wallets.transactions', compact('transactions'));
    }

    public function show($id)
    {
        $transaction = WalletTransaction::with('wallet.customer')->findOrFail($id);

        abort_if($transaction->wallet->customer_id !== Auth::id(), 403);

        return view('wallets.transaction-detail', compact('transaction'));
    }


    public function create()
    {
        return view('wallet.transaction_create');
    }

    public function store(Request $request, CryptoAddressService $cryptoService)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.00001',
            'type' => 'required|in:deposit,withdrawal,adjustment',
            'currency' => 'required_if:type,deposit|in:btc,xmr,sol',
            'description' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'wallet_id', 'amount', 'type', 'currency', 'description'
        ]);

        if ($request->type === 'deposit') {
            // Generate deposit address dynamically
            $data['address'] = $cryptoService->generateAddress($data['currency']);
            $data['status'] = 'pending'; // deposits are initially pending
        } else {
            $data['status'] = 'completed';
        }

        $data['customer_id'] = Auth::id(); // ensure customer ID is linked
        $data['reference'] = 'ADMIN_' . strtoupper(\Str::random(10));

        WalletTransaction::create($data);

        return redirect()->route('wallet.transactions.index')
            ->with('success', 'Transaction created successfully!');
    }

    public function update(Request $request, WalletTransaction $transaction)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.00001',
            'type' => 'required|in:deposit,withdrawal,adjustment',
            'description' => 'nullable|string|max:255',
        ]);

        $transaction->update($request->only(['amount', 'type', 'description']));

        return redirect()->route('wallet.transactions.index')
            ->with('success', 'Transaction updated successfully!');
    }

    public function download(WalletTransaction $transaction)
    {
        $filePath = storage_path('app/' . $transaction->qr_code_path);
        return response()->download($filePath);
    }
}
