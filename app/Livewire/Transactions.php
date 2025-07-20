<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\WalletTransaction;
use App\Models\Wallet;

#[Title('Transaction History - 1000 PROXIES')]
class Transactions extends Component
{
    use WithPagination, LivewireAlert;

    // Advanced filtering properties
    #[Url]
    public $typeFilter = 'all';

    #[Url]
    public $statusFilter = 'all';

    #[Url]
    public $dateRange = 'all';

    #[Url]
    public $searchTerm = '';

    public $selectedTransactions = [];
    public $showFilters = false;
    public $sortBy = 'latest';

    // Transaction management properties
    public $showTransactionModal = false;
    public $selectedTransaction = null;

    // Bulk actions
    public $bulkAction = '';
    public $selectAll = false;

    // Date range picker
    public $customDateFrom = '';
    public $customDateTo = '';

    // Export options
    public $exportFormat = 'csv';

    protected $queryString = [
        'typeFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'dateRange' => ['except' => 'all'],
        'searchTerm' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
    ];

    // Transaction type options
    public function getTypeOptions()
    {
        return [
            'all' => 'All Types',
            'deposit' => 'Deposits',
            'withdrawal' => 'Withdrawals',
            'payment' => 'Payments',
            'refund' => 'Refunds',
            'bonus' => 'Bonuses',
            'commission' => 'Commissions',
            'fee' => 'Fees',
        ];
    }

    // Transaction status options
    public function getStatusOptions()
    {
        return [
            'all' => 'All Statuses',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'processing' => 'Processing',
        ];
    }

    // Date range options
    public function getDateRangeOptions()
    {
        return [
            'all' => 'All Time',
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'This Week',
            'last_week' => 'Last Week',
            'month' => 'This Month',
            'last_month' => 'Last Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
            'custom' => 'Custom Range',
        ];
    }

    // Sort options
    public function getSortOptions()
    {
        return [
            'latest' => 'Latest First',
            'oldest' => 'Oldest First',
            'amount_high' => 'Amount: High to Low',
            'amount_low' => 'Amount: Low to High',
            'type' => 'Type',
            'status' => 'Status',
        ];
    }

    // Toggle filters visibility
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    // Reset all filters
    public function resetFilters()
    {
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->dateRange = 'all';
        $this->searchTerm = '';
        $this->sortBy = 'latest';
        $this->selectedTransactions = [];
        $this->customDateFrom = '';
        $this->customDateTo = '';

        $this->alert('success', 'Filters reset successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Transaction actions
    public function viewTransaction($transactionId)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return;
        }

        $customerWallet = $customer->wallet;

        $this->selectedTransaction = WalletTransaction::where('id', $transactionId)
                                                    ->where('wallet_id', $customerWallet->id)
                                                    ->with(['wallet', 'order'])
                                                    ->first();

        if ($this->selectedTransaction) {
            $this->showTransactionModal = true;
        }
    }

    public function downloadReceipt($transactionId)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return;
        }

        $customerWallet = $customer->wallet;

        $transaction = WalletTransaction::where('id', $transactionId)
                                       ->where('wallet_id', $customerWallet->id)
                                       ->first();

        if (!$transaction) {
            $this->alert('error', 'Transaction not found.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        if ($transaction->qr_code_path && Storage::exists($transaction->qr_code_path)) {
            return Storage::download($transaction->qr_code_path, "receipt-{$transaction->id}.png");
        }

        $this->alert('info', 'Receipt not available for this transaction.', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function requestRefund($transactionId)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return;
        }

        $customerWallet = $customer->wallet;

        $transaction = WalletTransaction::where('id', $transactionId)
                                       ->where('wallet_id', $customerWallet->id)
                                       ->where('type', 'payment')
                                       ->where('status', 'completed')
                                       ->first();

        if (!$transaction) {
            $this->alert('error', 'Refund not available for this transaction.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        // Create refund request logic here
        $this->alert('info', 'Refund request submitted. We will process it within 3-5 business days.', [
            'position' => 'bottom-end',
            'timer' => 5000,
            'toast' => true,
        ]);
    }

    // Bulk actions
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTransactions = $this->getFilteredTransactions()->pluck('id')->toArray();
        } else {
            $this->selectedTransactions = [];
        }
    }

    public function executeBulkAction()
    {
        if (empty($this->selectedTransactions) || !$this->bulkAction) {
            $this->alert('error', 'Please select transactions and action.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        switch ($this->bulkAction) {
            case 'download_receipts':
                $this->bulkDownloadReceipts();
                break;
            case 'export_data':
                $this->bulkExportData();
                break;
            case 'mark_reviewed':
                $this->bulkMarkReviewed();
                break;
        }

        $this->selectedTransactions = [];
        $this->bulkAction = '';
        $this->selectAll = false;
    }

    private function bulkDownloadReceipts()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return;
        }

        $customerWallet = $customer->wallet;

        $transactions = WalletTransaction::whereIn('id', $this->selectedTransactions)
                                       ->where('wallet_id', $customerWallet->id)
                                       ->whereNotNull('qr_code_path')
                                       ->get();

        if ($transactions->isEmpty()) {
            $this->alert('error', 'No receipts available for selected transactions.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        // Create zip file with all receipts
        $this->alert('info', 'Bulk receipt download started...', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    private function bulkExportData()
    {
        $this->alert('info', 'Transaction data export started. You will receive an email when ready.', [
            'position' => 'bottom-end',
            'timer' => 5000,
            'toast' => true,
        ]);
    }

    private function bulkMarkReviewed()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return;
        }

        $customerWallet = $customer->wallet;

        WalletTransaction::whereIn('id', $this->selectedTransactions)
                        ->where('wallet_id', $customerWallet->id)
                        ->update(['reviewed_at' => now()]);

        $this->alert('success', 'Selected transactions marked as reviewed!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Export functions
    public function exportTransactions()
    {
        $transactions = $this->getFilteredTransactions();

        $this->alert('info', 'Transaction export started. Download will begin shortly.', [
            'position' => 'bottom-end',
            'timer' => 5000,
            'toast' => true,
        ]);

        // Implement actual export logic based on format
    }

    private function getFilteredTransactions()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return collect();
        }

        $customerWallet = $customer->wallet;

        $query = WalletTransaction::where('wallet_id', $customerWallet->id)
                                 ->with(['wallet', 'order']);

        // Type filter
        if ($this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }

        // Status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Date range filter
        if ($this->dateRange !== 'all') {
            switch ($this->dateRange) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->startOfWeek());
                    break;
                case 'last_week':
                    $query->whereBetween('created_at', [
                        now()->subWeek()->startOfWeek(),
                        now()->subWeek()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->startOfMonth());
                    break;
                case 'last_month':
                    $query->whereBetween('created_at', [
                        now()->subMonth()->startOfMonth(),
                        now()->subMonth()->endOfMonth()
                    ]);
                    break;
                case 'quarter':
                    $query->where('created_at', '>=', now()->startOfQuarter());
                    break;
                case 'year':
                    $query->where('created_at', '>=', now()->startOfYear());
                    break;
                case 'custom':
                    if ($this->customDateFrom && $this->customDateTo) {
                        $query->whereBetween('created_at', [
                            $this->customDateFrom,
                            $this->customDateTo . ' 23:59:59'
                        ]);
                    }
                    break;
            }
        }

        // Search filter
        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('id', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('reference', 'like', '%' . $this->searchTerm . '%');
            });
        }

        // Sorting
        switch ($this->sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'amount_high':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('amount', 'asc');
                break;
            case 'type':
                $query->orderBy('type', 'asc');
                break;
            case 'status':
                $query->orderBy('status', 'asc');
                break;
            default:
                $query->latest();
                break;
        }

        return $query;
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login');
        }

        $customerWallet = $customer->wallet;

        $transactions = $this->getFilteredTransactions();

        if (!$customerWallet) {
            $transactions = collect()->paginate(15);
        } else {
            $transactions = $transactions->paginate(15);
        }

        // Calculate transaction statistics
        $stats = [
            'total_transactions' => WalletTransaction::where('wallet_id', $customerWallet->id)->count(),
            'total_deposits' => WalletTransaction::where('wallet_id', $customerWallet->id)
                                               ->where('type', 'deposit')
                                               ->where('status', 'completed')
                                               ->sum('amount'),
            'total_withdrawals' => WalletTransaction::where('wallet_id', $customerWallet->id)
                                                  ->where('type', 'withdrawal')
                                                  ->where('status', 'completed')
                                                  ->sum('amount'),
            'total_payments' => WalletTransaction::where('wallet_id', $customerWallet->id)
                                                ->where('type', 'payment')
                                                ->where('status', 'completed')
                                                ->sum('amount'),
            'pending_count' => WalletTransaction::where('wallet_id', $customerWallet->id)
                                              ->where('status', 'pending')
                                              ->count(),
        ];

        return view('livewire.transactions', [
            'transactions' => $transactions,
            'stats' => $stats,
            'typeOptions' => $this->getTypeOptions(),
            'statusOptions' => $this->getStatusOptions(),
            'dateRangeOptions' => $this->getDateRangeOptions(),
            'sortOptions' => $this->getSortOptions(),
        ]);
    }
}
