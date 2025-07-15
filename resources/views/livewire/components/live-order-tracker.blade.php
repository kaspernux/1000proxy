{{-- Live Order Tracker - Real-time order monitoring and processing --}}
<div x-data="{ 
        autoRefreshTimer: null,
        startAutoRefresh(interval) {
            this.stopAutoRefresh();
            this.autoRefreshTimer = setInterval(() => {
                $wire.refreshOrders();
            }, interval * 1000);
        },
        stopAutoRefresh() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
                this.autoRefreshTimer = null;
            }
        }
    }"
    x-init="@if($autoRefresh) startAutoRefresh({{ $refreshInterval }}) @endif"
    @start-auto-refresh.window="startAutoRefresh($event.detail)"
    @stop-auto-refresh.window="stopAutoRefresh()"
    class="live-order-tracker bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">

    {{-- Header with Statistics --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Live Order Tracker
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Real-time order monitoring and processing
                    @if($lastUpdated)
                        • Last updated: {{ $lastUpdated->diffForHumans() }}
                    @endif
                </p>
            </div>

            {{-- Control Buttons --}}
            <div class="flex items-center space-x-3">
                {{-- Refresh Button --}}
                <button wire:click="refreshOrders" 
                        :disabled="$wire.isLoading"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': $wire.isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>

                {{-- Auto-refresh Toggle --}}
                <div class="flex items-center">
                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" 
                               wire:model.live="autoRefresh" 
                               wire:change="toggleAutoRefresh"
                               class="mr-2 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        Auto-refresh ({{ $refreshInterval }}s)
                    </label>
                </div>

                {{-- Export Button --}}
                <button wire:click="exportReport"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export
                </button>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Orders</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_orders'] }}</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Pending</div>
                <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['pending_orders'] }}</div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Processing</div>
                <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['processing_orders'] }}</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-green-600 dark:text-green-400">Completed</div>
                <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $stats['completed_orders'] }}</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-red-600 dark:text-red-400">Failed</div>
                <div class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stats['failed_orders'] }}</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Revenue</div>
                <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">${{ number_format($stats['total_revenue'], 2) }}</div>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                <div class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Avg Process Time</div>
                <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">{{ $stats['avg_processing_time'] }}m</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <div class="flex flex-wrap items-center justify-between gap-4">
            {{-- Status Filter --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Status:</span>
                <div class="flex space-x-1">
                    @foreach(['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $status => $label)
                        <button wire:click="filterByStatus('{{ $status }}')"
                                class="px-3 py-1 text-xs font-medium rounded-full transition-colors {{ $filterStatus === $status 
                                    ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' 
                                    : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Timeframe Filter --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Timeframe:</span>
                <div class="flex space-x-1">
                    @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time'] as $timeframe => $label)
                        <button wire:click="filterByTimeframe('{{ $timeframe }}')"
                                class="px-3 py-1 text-xs font-medium rounded-full transition-colors {{ $filterTimeframe === $timeframe 
                                    ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                                    : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Orders List --}}
    <div class="overflow-hidden">
        @if($isLoading)
            <div class="flex items-center justify-center py-12">
                <div class="flex items-center space-x-3">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    <span class="text-gray-600 dark:text-gray-400">Loading orders...</span>
                </div>
            </div>
        @elseif(empty($orders))
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No orders found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No orders match your current filters.</p>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($orders as $order)
                    <div wire:key="order-{{ $order['id'] }}" 
                         class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                         wire:click="selectOrder({{ $order['id'] }})">
                        
                        <div class="flex items-center justify-between">
                            {{-- Order Info --}}
                            <div class="flex items-center space-x-4">
                                {{-- Status Badge --}}
                                <div class="flex items-center">
                                    <span class="text-lg mr-2">{{ $order['status_config']['icon'] }}</span>
                                    <div class="flex flex-col">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $order['status_config']['bg'] }} {{ $order['status_config']['color'] }}">
                                            {{ $order['status_config']['label'] }}
                                        </span>
                                        @if(in_array($order['id'], $processingQueue))
                                            <div class="flex items-center mt-1">
                                                <div class="animate-spin rounded-full h-3 w-3 border-b border-blue-600 mr-1"></div>
                                                <span class="text-xs text-blue-600">Processing...</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Order Details --}}
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                            Order #{{ $order['id'] }}
                                        </h3>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">•</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $order['user_name'] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        <span>${{ number_format($order['total_amount'], 2) }} {{ $order['currency'] }}</span>
                                        <span>{{ $order['items_count'] }} items</span>
                                        <span>{{ $order['payment_method'] }}</span>
                                        <span>{{ $order['created_human'] }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="flex items-center space-x-4">
                                <div class="w-32">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-gray-500 dark:text-gray-400">Progress</span>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $order['progress_percentage'] }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500 {{ $order['status'] === 'completed' ? 'bg-green-500' : ($order['status'] === 'failed' ? 'bg-red-500' : 'bg-blue-500') }}"
                                             style="width: {{ $order['progress_percentage'] }}%"></div>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex items-center space-x-2">
                                    @if($order['can_process'])
                                        <button wire:click.stop="processOrder({{ $order['id'] }})"
                                                :disabled="$wire.isLoading"
                                                class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 transition-colors">
                                            Process
                                        </button>
                                    @endif
                                    
                                    @if($order['can_retry'])
                                        <button wire:click.stop="retryOrder({{ $order['id'] }})"
                                                class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                            Retry
                                        </button>
                                    @endif
                                    
                                    @if($order['can_cancel'])
                                        <button wire:click.stop="cancelOrder({{ $order['id'] }})"
                                                onclick="return confirm('Are you sure you want to cancel this order?')"
                                                class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                                            Cancel
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Processing Time Indicator --}}
                        @if($order['processing_time'])
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Processing time: {{ $order['processing_time'] }} minutes
                            </div>
                        @endif

                        {{-- Next Action --}}
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                            Next: {{ $order['next_action'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Selected Order Details Panel --}}
    @if($selectedOrder)
        <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Details #{{ $selectedOrder['id'] }}</h3>
                <button wire:click="selectOrder(null)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Customer Information --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Name:</span> {{ $selectedOrder['user_name'] }}</div>
                        <div><span class="font-medium">Email:</span> {{ $selectedOrder['user_email'] }}</div>
                        <div><span class="font-medium">Payment Method:</span> {{ $selectedOrder['payment_method'] }}</div>
                        <div><span class="font-medium">Payment Status:</span> 
                            <span class="px-2 py-1 text-xs rounded-full {{ $selectedOrder['payment_status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($selectedOrder['payment_status']) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Order Information --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Order Information</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Status:</span> 
                            <span class="{{ $selectedOrder['status_config']['color'] }}">{{ $selectedOrder['status_config']['label'] }}</span>
                        </div>
                        <div><span class="font-medium">Total Amount:</span> ${{ number_format($selectedOrder['total_amount'], 2) }} {{ $selectedOrder['currency'] }}</div>
                        <div><span class="font-medium">Items:</span> {{ $selectedOrder['items_count'] }}</div>
                        <div><span class="font-medium">Created:</span> {{ $selectedOrder['created_human'] }}</div>
                        <div><span class="font-medium">Updated:</span> {{ $selectedOrder['updated_human'] }}</div>
                    </div>
                </div>
                
                {{-- Order Items --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Order Items</h4>
                    <div class="space-y-2">
                        @foreach($selectedOrder['items'] as $item)
                            <div class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['server_plan_name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Quantity: {{ $item['quantity'] }} • Price: ${{ number_format($item['price'], 2) }} • Duration: {{ $item['duration'] }} days
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($selectedOrder['notes'])
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                    <div class="text-sm text-yellow-700 dark:text-yellow-400">
                        <strong>Notes:</strong> {{ $selectedOrder['notes'] }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Real-time status indicator --}}
<div wire:loading.flex class="fixed bottom-4 right-4 items-center space-x-2 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg">
    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
    <span class="text-sm font-medium">Processing orders...</span>
</div>

{{-- Toast notifications for order events --}}
@script
<script>
    // Listen for order events
    $wire.on('order-processed', (data) => {
        if (window.showNotification) {
            window.showNotification('success', `Order #${data.orderId} processed successfully`);
        }
    });
    
    $wire.on('order-retried', (data) => {
        if (window.showNotification) {
            window.showNotification('info', `Order #${data.orderId} retry initiated`);
        }
    });
    
    $wire.on('order-cancelled', (data) => {
        if (window.showNotification) {
            window.showNotification('warning', `Order #${data.orderId} cancelled`);
        }
    });
    
    $wire.on('new-order-created', (data) => {
        if (window.showNotification) {
            window.showNotification('success', `New order #${data.orderId} received`);
        }
    });
    
    $wire.on('order-status-updated', (data) => {
        if (window.showNotification) {
            window.showNotification('info', `Order #${data.orderId} status: ${data.status}`);
        }
    });
</script>
@endscript
