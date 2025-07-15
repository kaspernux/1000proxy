<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\XUIService;

#[Title('My Orders - 1000 PROXIES')]
class MyOrdersPage extends Component
{
    use WithPagination, LivewireAlert;

    // Advanced filtering properties
    #[Url]
    public $statusFilter = 'all';

    #[Url]
    public $dateRange = 'all';

    #[Url]
    public $searchTerm = '';

    public $selectedOrders = [];
    public $showFilters = false;
    public $sortBy = 'latest';

    // Order management properties
    public $orderToCancel = null;
    public $cancellationReason = '';
    public $showCancelModal = false;

    // Bulk actions
    public $bulkAction = '';
    public $selectAll = false;

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
        'dateRange' => ['except' => 'all'],
        'searchTerm' => ['except' => ''],
        'sortBy' => ['except' => 'latest'],
    ];

    // Advanced order status options
    public function getStatusOptions()
    {
        return [
            'all' => 'All Orders',
            'pending' => 'Pending Payment',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
        ];
    }

    // Date range options
    public function getDateRangeOptions()
    {
        return [
            'all' => 'All Time',
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
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
        $this->statusFilter = 'all';
        $this->dateRange = 'all';
        $this->searchTerm = '';
        $this->sortBy = 'latest';
        $this->selectedOrders = [];

        $this->alert('success', 'Filters reset successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Order management actions
    public function viewOrder($orderId)
    {
        return redirect()->route('my-order-detail', ['order' => $orderId]);
    }

    public function downloadInvoice($orderId)
    {
        $order = Order::where('id', $orderId)
                     ->where('customer_id', Auth::guard('customer')->id())
                     ->firstOrFail();

        // Generate and download invoice
        $this->alert('info', 'Invoice download started...', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function trackOrder($orderId)
    {
        $order = Order::where('id', $orderId)
                     ->where('customer_id', Auth::guard('customer')->id())
                     ->firstOrFail();

        // Redirect to tracking page or show tracking modal
        $this->dispatch('show-tracking-modal', orderId: $orderId);
    }

    public function reorderItems($orderId)
    {
        $order = Order::where('id', $orderId)
                     ->where('customer_id', Auth::guard('customer')->id())
                     ->with('orderItems.serverPlan')
                     ->firstOrFail();

        foreach ($order->orderItems as $item) {
            if ($item->serverPlan && $item->serverPlan->is_active) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    \App\Helpers\CartManagement::addItemToCart($item->server_plan_id);
                }
            }
        }

        $this->alert('success', 'Items added to cart successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);

        return redirect()->route('cart');
    }

    public function initiateCancellation($orderId)
    {
        $this->orderToCancel = $orderId;
        $this->showCancelModal = true;
    }

    public function cancelOrder()
    {
        if (!$this->orderToCancel || !$this->cancellationReason) {
            $this->alert('error', 'Please provide a cancellation reason.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        $order = Order::where('id', $this->orderToCancel)
                     ->where('customer_id', Auth::guard('customer')->id())
                     ->whereIn('status', ['pending', 'processing'])
                     ->firstOrFail();

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $this->cancellationReason,
            'cancelled_at' => now(),
        ]);

        $this->showCancelModal = false;
        $this->orderToCancel = null;
        $this->cancellationReason = '';

        $this->alert('success', 'Order cancelled successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Bulk actions
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedOrders = $this->getFilteredOrders()->pluck('id')->toArray();
        } else {
            $this->selectedOrders = [];
        }
    }

    public function executeBulkAction()
    {
        if (empty($this->selectedOrders) || !$this->bulkAction) {
            $this->alert('error', 'Please select orders and action.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        switch ($this->bulkAction) {
            case 'download_invoices':
                $this->bulkDownloadInvoices();
                break;
            case 'cancel_orders':
                $this->bulkCancelOrders();
                break;
            case 'mark_received':
                $this->bulkMarkReceived();
                break;
        }

        $this->selectedOrders = [];
        $this->bulkAction = '';
        $this->selectAll = false;
    }

    private function bulkDownloadInvoices()
    {
        // Implement bulk invoice download
        $this->alert('info', 'Bulk invoice download started...', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    private function bulkCancelOrders()
    {
        Order::whereIn('id', $this->selectedOrders)
             ->where('customer_id', Auth::guard('customer')->id())
             ->whereIn('status', ['pending', 'processing'])
             ->update([
                 'status' => 'cancelled',
                 'cancellation_reason' => 'Bulk cancellation',
                 'cancelled_at' => now(),
             ]);

        $this->alert('success', 'Selected orders cancelled successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    private function bulkMarkReceived()
    {
        Order::whereIn('id', $this->selectedOrders)
             ->where('customer_id', Auth::guard('customer')->id())
             ->where('status', 'shipped')
             ->update(['status' => 'delivered']);

        $this->alert('success', 'Selected orders marked as received!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    private function getFilteredOrders()
    {
        $query = Order::where('customer_id', Auth::guard('customer')->id());

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
                case 'week':
                    $query->where('created_at', '>=', now()->startOfWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->startOfMonth());
                    break;
                case 'quarter':
                    $query->where('created_at', '>=', now()->startOfQuarter());
                    break;
                case 'year':
                    $query->where('created_at', '>=', now()->startOfYear());
                    break;
            }
        }

        // Search filter
        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('id', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('customer_notes', 'like', '%' . $this->searchTerm . '%');
            });
        }

        // Sorting
        switch ($this->sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'amount_high':
                $query->orderBy('grand_total', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('grand_total', 'asc');
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
        $orders = $this->getFilteredOrders()->paginate(10);

        // Calculate order statistics
        $orderStats = [
            'total' => Order::where('customer_id', Auth::guard('customer')->id())->count(),
            'pending' => Order::where('customer_id', Auth::guard('customer')->id())->where('status', 'pending')->count(),
            'processing' => Order::where('customer_id', Auth::guard('customer')->id())->where('status', 'processing')->count(),
            'delivered' => Order::where('customer_id', Auth::guard('customer')->id())->where('status', 'delivered')->count(),
            'total_spent' => Order::where('customer_id', Auth::guard('customer')->id())->where('status', 'delivered')->sum('grand_total'),
        ];

        return view('livewire.my-orders-page', [
            'orders' => $orders,
            'orderStats' => $orderStats,
            'statusOptions' => $this->getStatusOptions(),
            'dateRangeOptions' => $this->getDateRangeOptions(),
            'sortOptions' => $this->getSortOptions(),
        ]);
    }
}
