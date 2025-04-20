<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class WalletTransactionController extends Controller
{
    public function index() {
        $transactions = WalletTransaction::whereHas('wallet', function ($query) {
            $query->where('customer_id', Auth::id());
        })->latest()->paginate(20);

        return view('wallets.transactions', compact('transactions'));
    }

    public function show($id) {
        $transaction = WalletTransaction::with('wallet.customer')->findOrFail($id);

        abort_if($transaction->wallet->customer_id !== Auth::id(), 403);

        return view('wallets.transaction-detail', compact('transaction'));
    }
    
    public function create()
    {
        return view('wallet.transaction_create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.00001',
            'type' => 'required|in:deposit,withdrawal,adjustment',
            'description' => 'nullable|string|max:255',
        ]);

        $transaction = WalletTransaction::create($request->all());

        return redirect()->route('wallet.transactions.index')->with('success', 'Transaction created successfully!');
    }
    public function update(Request $request, WalletTransaction $transaction)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.00001',
            'type' => 'required|in:deposit,withdrawal,adjustment',
            'description' => 'nullable|string|max:255',
        ]);

        $transaction->update($request->all());

        return redirect()->route('wallet.transactions.index')->with('success', 'Transaction updated successfully!');
    }
    public function download(WalletTransaction $transaction)
    {
        $filePath = storage_path('app/' . $transaction->qr_code_path);
        return response()->download($filePath);
    }
}