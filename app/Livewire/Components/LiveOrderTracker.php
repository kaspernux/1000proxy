<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Order;
use App\Models\Customer;
use App\Services\PaymentService;
use App\Services\XUIService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LiveOrderTracker extends Component
{
    // Component state
    public $orders = [];
    public $selectedOrderId = null;
    public $filterStatus = 'all'; // all, pending, processing, completed, failed, cancelled
    public $filterTimeframe = 'today'; // today, week, month, all
    public $autoRefresh = true;
    public $refreshInterval = 15; // seconds
    
    // Real-time tracking
    public $isLoading = false;
    public $lastUpdated;
    public $processingQueue = [];
    
    // Statistics
    public $stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'processing_orders' => 0,
        'completed_orders' => 0,
        'failed_orders' => 0,
        'total_revenue' => 0,
        'avg_processing_time' => 0
    ];

    // Order statuses with their configurations
    public $statusConfig = [
        'pending' => [
            'color' => 'text-yellow-600',
            'bg' => 'bg-yellow-100 dark:bg-yellow-900/20',
            'icon' => '⏳',
            'label' => 'Pending Payment'
        ],
        'processing' => [
            'color' => 'text-blue-600',
            'bg' => 'bg-blue-100 dark:bg-blue-900/20',
            'icon' => '⚙️',
            'label' => 'Processing'
        ],
        'completed' => [
            'color' => 'text-green-600',
            'bg' => 'bg-green-100 dark:bg-green-900/20',
            'icon' => '✅',
            'label' => 'Completed'
        ],
        'failed' => [
            'color' => 'text-red-600',
            'bg' => 'bg-red-100 dark:bg-red-900/20',
            'icon' => '❌',
            'label' => 'Failed'
        ],
        'cancelled' => [
            'color' => 'text-gray-600',
            'bg' => 'bg-gray-100 dark:bg-gray-900/20',
            'icon' => '🚫',
            'label' => 'Cancelled'
        ],
        'refunded' => [
            'color' => 'text-purple-600',
            'bg' => 'bg-purple-100 dark:bg-purple-900/20',
            'icon' => '💸',
            'label' => 'Refunded'
        ]
    ];

    protected $listeners = [
        'order.status.updated' => 'handleOrderStatusUpdate',
        'refresh-orders' => 'refreshOrders',
        'echo:orders,OrderStatusUpdated' => 'handleRealtimeOrderUpdate',
        'echo:orders,OrderCreated' => 'handleNewOrder',
        'echo:orders,PaymentCompleted' => 'handlePaymentCompleted'
    ];

    public function mount()
    {
        $this->lastUpdated = now();
        $this->loadOrders();
    }

    public function render()
    {
        return view('livewire.components.live-order-tracker');
    }

    /**
     * Load orders based on filters
     */
    public function loadOrders()
    {
        $this->isLoading = true;
        
        try {
            $query = Order::with(['customer', 'orderItems.serverPlan', 'payment'])
                ->select([
                    'id', 'customer_id', 'status', 'total_amount', 'currency', 
                    'payment_method', 'payment_status', 'created_at', 'updated_at',
                    'processing_started_at', 'completed_at', 'notes'
                ]);

            // Apply status filter
            if ($this->filterStatus !== 'all') {
                $query->where('status', $this->filterStatus);
            }

            // Apply timeframe filter
            switch ($this->filterTimeframe) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

        $this->orders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
            'user_name' => $order->customer->name ?? 'Guest',
            'user_email' => $order->customer->email ?? 'N/A',
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'created_human' => $order->created_at->diffForHumans(),
                    'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                    'updated_human' => $order->updated_at->diffForHumans(),
                    'processing_time' => $this->calculateProcessingTime($order),
                    'items_count' => $order->orderItems->count(),
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'server_plan_name' => $item->serverPlan->name ?? 'Unknown Plan',
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'duration' => $item->duration
                        ];
                    })->toArray(),
                    'status_config' => $this->statusConfig[$order->status] ?? $this->statusConfig['pending'],
                    'can_process' => $order->status === 'pending' && $order->payment_status === 'completed',
                    'can_retry' => $order->status === 'failed',
                    'can_cancel' => in_array($order->status, ['pending', 'processing']),
                    'progress_percentage' => $this->calculateProgressPercentage($order->status),
                    'next_action' => $this->getNextAction($order),
                    'notes' => $order->notes
                ];
            })->toArray();

            $this->calculateStats();
            $this->lastUpdated = now();
            
        } catch (\Exception $e) {
            $this->addError('load_error', 'Failed to load orders: ' . $e->getMessage());
            logger()->error('LiveOrderTracker: Failed to load orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Refresh orders
     */
    public function refreshOrders()
    {
        $this->loadOrders();
        $this->dispatch('orders-refreshed');
    }

    /**
     * Process a pending order
     */
    public function processOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            if ($order->status !== 'pending' || $order->payment_status !== 'completed') {
                throw new \Exception('Order cannot be processed in current state');
            }

            // Add to processing queue
            $this->processingQueue[] = $orderId;
            
            // Update order status
            $order->update([
                'status' => 'processing',
                'processing_started_at' => now()
            ]);

            // Process order items (create proxy configurations)
            $this->processOrderItems($order);

            // Update to completed
            $order->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Remove from processing queue
            $this->processingQueue = array_filter($this->processingQueue, fn($id) => $id !== $orderId);

            $this->loadOrders();
            
            $this->dispatch('order-processed', [
                'orderId' => $orderId,
                'status' => 'completed'
            ]);

        } catch (\Exception $e) {
            // Remove from processing queue
            $this->processingQueue = array_filter($this->processingQueue, fn($id) => $id !== $orderId);
            
            // Update order to failed
            Order::where('id', $orderId)->update([
                'status' => 'failed',
                'notes' => 'Processing failed: ' . $e->getMessage()
            ]);

            logger()->error('LiveOrderTracker: Failed to process order', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            $this->addError('process_error', 'Failed to process order: ' . $e->getMessage());
            $this->loadOrders();
        }
    }

    /**
     * Process order items - create proxy configurations
     */
    private function processOrderItems($order)
    {
        foreach ($order->orderItems as $item) {
            $serverPlan = $item->serverPlan;
            $server = $serverPlan->server;
            
            // Create XUI client configuration
            $xuiService = new XUIService($server);
            
            for ($i = 0; $i < $item->quantity; $i++) {
                $clientData = [
                    'email' => ($order->customer->email ?? ('order'.$order->id.'@example.com')) . '_' . $item->id . '_' . ($i + 1),
                    'uuid' => \Str::uuid(),
                    'expiryTime' => now()->addDays($item->duration)->timestamp * 1000,
                    'limitIp' => $serverPlan->max_connections,
                    'totalGB' => $serverPlan->bandwidth_limit * 1024 * 1024 * 1024, // Convert GB to bytes
                    'enable' => true
                ];

                // Add client to XUI server
                $clientResult = $xuiService->addClient($serverPlan->inbound_id, $clientData);
                
                if ($clientResult['success']) {
                    // Create local client record
                    \App\Models\ServerClient::create([
                        'server_id' => $server->id,
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'server_plan_id' => $serverPlan->id,
                        'customer_id' => $order->customer_id,
                        'client_uuid' => $clientData['uuid'],
                        'client_email' => $clientData['email'],
                        'inbound_id' => $serverPlan->inbound_id,
                        'expiry_time' => Carbon::createFromTimestamp($clientData['expiryTime'] / 1000),
                        'total_gb' => $clientData['totalGB'],
                        'status' => 'active',
                        'remote_client_id' => $clientResult['data']['id'] ?? null
                    ]);
                } else {
                    throw new \Exception("Failed to create client on server: " . ($clientResult['message'] ?? 'Unknown error'));
                }
            }
        }
    }

    /**
     * Retry failed order
     */
    public function retryOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            if ($order->status !== 'failed') {
                throw new \Exception('Only failed orders can be retried');
            }

            $order->update([
                'status' => 'pending',
                'notes' => 'Order retry initiated at ' . now()
            ]);

            $this->loadOrders();
            
            $this->dispatch('order-retried', ['orderId' => $orderId]);

        } catch (\Exception $e) {
            $this->addError('retry_error', 'Failed to retry order: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            if (!in_array($order->status, ['pending', 'processing'])) {
                throw new \Exception('Order cannot be cancelled in current state');
            }

            $order->update([
                'status' => 'cancelled',
                'notes' => 'Order cancelled at ' . now()
            ]);

            // If payment was completed, initiate refund
            if ($order->payment_status === 'completed') {
                $this->initiateRefund($order);
            }

            $this->loadOrders();
            
            $this->dispatch('order-cancelled', ['orderId' => $orderId]);

        } catch (\Exception $e) {
            $this->addError('cancel_error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Initiate refund for cancelled order
     */
    private function initiateRefund($order)
    {
        try {
            $paymentService = new PaymentService();
            $refundResult = $paymentService->refund($order->payment);
            
            if ($refundResult['success']) {
                $order->update(['status' => 'refunded']);
            }
        } catch (\Exception $e) {
            logger()->error('Failed to initiate refund', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Filter orders by status
     */
    public function filterByStatus($status)
    {
        $this->filterStatus = $status;
        $this->loadOrders();
    }

    /**
     * Filter orders by timeframe
     */
    public function filterByTimeframe($timeframe)
    {
        $this->filterTimeframe = $timeframe;
        $this->loadOrders();
    }

    /**
     * Select/deselect order
     */
    public function selectOrder($orderId)
    {
        $this->selectedOrderId = $this->selectedOrderId === $orderId ? null : $orderId;
    }

    /**
     * Get selected order details
     */
    public function getSelectedOrderProperty()
    {
        if (!$this->selectedOrderId) {
            return null;
        }

        return collect($this->orders)->firstWhere('id', $this->selectedOrderId);
    }

    /**
     * Handle real-time order updates
     */
    #[On('echo:orders,OrderStatusUpdated')]
    public function handleRealtimeOrderUpdate($data)
    {
        $orderId = $data['order_id'];
        $newStatus = $data['status'];

        // Update order in current list
        foreach ($this->orders as $index => $order) {
            if ($order['id'] == $orderId) {
                $this->orders[$index]['status'] = $newStatus;
                $this->orders[$index]['updated_human'] = 'Just now';
                $this->orders[$index]['status_config'] = $this->statusConfig[$newStatus] ?? $this->statusConfig['pending'];
                $this->orders[$index]['progress_percentage'] = $this->calculateProgressPercentage($newStatus);
                break;
            }
        }

        $this->calculateStats();
        
        $this->dispatch('order-status-updated', [
            'orderId' => $orderId,
            'status' => $newStatus
        ]);
    }

    /**
     * Handle new order creation
     */
    #[On('echo:orders,OrderCreated')]
    public function handleNewOrder($data)
    {
        $this->loadOrders(); // Reload to include new order
        
        $this->dispatch('new-order-created', [
            'orderId' => $data['order_id']
        ]);
    }

    /**
     * Calculate order processing time
     */
    private function calculateProcessingTime($order)
    {
        if ($order->status === 'processing' && $order->processing_started_at) {
            return now()->diffInMinutes($order->processing_started_at);
        } elseif ($order->status === 'completed' && $order->processing_started_at && $order->completed_at) {
            return Carbon::parse($order->completed_at)->diffInMinutes($order->processing_started_at);
        }
        
        return null;
    }

    /**
     * Calculate progress percentage based on status
     */
    private function calculateProgressPercentage($status)
    {
        return match($status) {
            'pending' => 20,
            'processing' => 60,
            'completed' => 100,
            'failed' => 0,
            'cancelled' => 0,
            'refunded' => 0,
            default => 0
        };
    }

    /**
     * Get next action for order
     */
    private function getNextAction($order)
    {
        return match($order->status) {
            'pending' => $order->payment_status === 'completed' ? 'Process Order' : 'Waiting for Payment',
            'processing' => 'Creating Configurations...',
            'completed' => 'Delivered',
            'failed' => 'Retry Available',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => 'Unknown'
        };
    }

    /**
     * Calculate statistics
     */
    private function calculateStats()
    {
        $total = count($this->orders);
        $pending = 0;
        $processing = 0;
        $completed = 0;
        $failed = 0;
        $totalRevenue = 0;
        $processingTimes = [];

        foreach ($this->orders as $order) {
            switch ($order['status']) {
                case 'pending':
                    $pending++;
                    break;
                case 'processing':
                    $processing++;
                    break;
                case 'completed':
                    $completed++;
                    $totalRevenue += $order['total_amount'];
                    break;
                case 'failed':
                    $failed++;
                    break;
            }

            if ($order['processing_time']) {
                $processingTimes[] = $order['processing_time'];
            }
        }

        $this->stats = [
            'total_orders' => $total,
            'pending_orders' => $pending,
            'processing_orders' => $processing,
            'completed_orders' => $completed,
            'failed_orders' => $failed,
            'total_revenue' => $totalRevenue,
            'avg_processing_time' => count($processingTimes) > 0 ? round(array_sum($processingTimes) / count($processingTimes), 1) : 0
        ];
    }

    /**
     * Toggle auto-refresh
     */
    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('start-auto-refresh', $this->refreshInterval);
        } else {
            $this->dispatch('stop-auto-refresh');
        }
    }

    /**
     * Export orders report
     */
    public function exportReport()
    {
        $filename = 'orders-' . now()->format('Y-m-d-H-i-s') . '.csv';
        
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($handle, [
                'Order ID',
                'Customer',
                'Email',
                'Status',
                'Payment Method',
                'Amount',
                'Currency',
                'Items',
                'Created',
                'Processing Time'
            ]);
            
            // CSV data
            foreach ($this->orders as $order) {
                fputcsv($handle, [
                    $order['id'],
                    $order['user_name'],
                    $order['user_email'],
                    $order['status'],
                    $order['payment_method'],
                    $order['total_amount'],
                    $order['currency'],
                    $order['items_count'],
                    $order['created_human'],
                    $order['processing_time'] ? $order['processing_time'] . ' min' : 'N/A'
                ]);
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
