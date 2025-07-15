<div class="w-full bg-gradient-to-r from-green-900 to-green-600 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header Section --}}
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-4">My Orders</h1>
            <p class="text-white/80 text-lg">Track and manage your proxy service orders</p>
        </div>

        {{-- Order Statistics Dashboard --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-white">{{ $orderStats['total'] }}</div>
                <div class="text-white/70">Total Orders</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-yellow-400">{{ $orderStats['pending'] }}</div>
                <div class="text-white/70">Pending</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-400">{{ $orderStats['processing'] }}</div>
                <div class="text-white/70">Processing</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-green-400">{{ $orderStats['delivered'] }}</div>
                <div class="text-white/70">Delivered</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 text-center">
                <div class="text-2xl font-bold text-white">${{ number_format($orderStats['total_spent'], 2) }}</div>
                <div class="text-white/70">Total Spent</div>
            </div>
        </div>

        {{-- Enhanced Filters Section --}}
        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 mb-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-4">
                <h2 class="text-xl font-bold text-white mb-4 lg:mb-0">Filter & Search Orders</h2>
                <button wire:click="toggleFilters"
                        class="lg:hidden bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    {{ $showFilters ? 'Hide' : 'Show' }} Filters
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 {{ !$showFilters ? 'hidden lg:grid' : '' }}">
                {{-- Search --}}
                <div>
                    <label class="block text-white font-medium mb-2">Search Orders</label>
                    <input type="text"
                           wire:model.live="searchTerm"
                           placeholder="Order ID, notes..."
                           class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400">
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-white font-medium mb-2">Order Status</label>
                    <select wire:model.live="statusFilter"
                            class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-green-400">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" class="bg-green-900">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range Filter --}}
                <div>
                    <label class="block text-white font-medium mb-2">Date Range</label>
                    <select wire:model.live="dateRange"
                            class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-green-400">
                        @foreach($dateRangeOptions as $value => $label)
                            <option value="{{ $value }}" class="bg-green-900">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sort By --}}
                <div>
                    <label class="block text-white font-medium mb-2">Sort By</label>
                    <select wire:model.live="sortBy"
                            class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-green-400">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" class="bg-green-900">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-6 pt-6 border-t border-white/20">
                <button wire:click="resetFilters"
                        class="text-yellow-400 hover:text-yellow-300 font-medium mb-4 sm:mb-0">
                    Reset All Filters
                </button>

                {{-- Bulk Actions --}}
                @if(count($selectedOrders) > 0)
                <div class="flex items-center space-x-4">
                    <span class="text-white">{{ count($selectedOrders) }} selected</span>
                    <select wire:model="bulkAction"
                            class="px-3 py-2 bg-white/10 border border-white/20 rounded text-white text-sm">
                        <option value="">Choose Action...</option>
                        <option value="download_invoices">Download Invoices</option>
                        <option value="cancel_orders">Cancel Orders</option>
                        <option value="mark_received">Mark as Received</option>
                    </select>
                    <button wire:click="executeBulkAction"
                            class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                        Execute
                    </button>
                </div>
                @endif
        </div>

        {{-- Enhanced Orders Table --}}
        <div class="bg-white/10 backdrop-blur-sm rounded-lg overflow-hidden">
            @if($orders->count() > 0)
                {{-- Desktop Table View --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-white/20">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <input type="checkbox"
                                           wire:model.live="selectAll"
                                           class="rounded border-white/20 bg-white/10 text-green-600 focus:ring-green-500">
                                </th>
                                <th class="px-6 py-4 text-left text-white font-semibold">Order</th>
                                <th class="px-6 py-4 text-left text-white font-semibold">Date</th>
                                <th class="px-6 py-4 text-left text-white font-semibold">Status</th>
                                <th class="px-6 py-4 text-left text-white font-semibold">Items</th>
                                <th class="px-6 py-4 text-left text-white font-semibold">Total</th>
                                <th class="px-6 py-4 text-left text-white font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @foreach ($orders as $order)
                            <tr class="hover:bg-white/5 transition duration-200" wire:key="order-{{ $order->id }}">
                                <td class="px-6 py-4">
                                    <input type="checkbox"
                                           wire:model.live="selectedOrders"
                                           value="{{ $order->id }}"
                                           class="rounded border-white/20 bg-white/10 text-green-600 focus:ring-green-500">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-semibold">#{{ $order->id }}</div>
                                    <div class="text-white/70 text-sm">{{ $order->orderItems->count() }} items</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white">{{ $order->created_at->format('M d, Y') }}</div>
                                    <div class="text-white/70 text-sm">{{ $order->created_at->format('H:i A') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500',
                                            'processing' => 'bg-blue-500/20 text-blue-300 border-blue-500',
                                            'shipped' => 'bg-purple-500/20 text-purple-300 border-purple-500',
                                            'delivered' => 'bg-green-500/20 text-green-300 border-green-500',
                                            'cancelled' => 'bg-red-500/20 text-red-300 border-red-500',
                                            'refunded' => 'bg-gray-500/20 text-gray-300 border-gray-500',
                                            'failed' => 'bg-red-500/20 text-red-300 border-red-500',
                                        ];
                                        $statusClass = $statusColors[$order->status] ?? 'bg-gray-500/20 text-gray-300 border-gray-500';
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white">{{ $order->orderItems->count() }} items</div>
                                    @if($order->orderItems->first())
                                        <div class="text-white/70 text-sm">{{ $order->orderItems->first()->serverPlan->name ?? 'N/A' }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-bold">${{ number_format($order->grand_total, 2) }}</div>
                                    @if($order->payment_status)
                                        <div class="text-white/70 text-sm">{{ ucfirst($order->payment_status) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="viewOrder({{ $order->id }})"
                                                class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                                            View
                                        </button>
                                        @if(in_array($order->status, ['pending', 'processing']))
                                            <button wire:click="initiateCancellation({{ $order->id }})"
                                                    class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                                                Cancel
                                            </button>
                                        @endif
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open"
                                                    class="bg-white/20 text-white px-2 py-1 rounded text-sm hover:bg-white/30 transition">
                                                ⋯
                                            </button>
                                            <div x-show="open" @click.away="open = false"
                                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10 py-2">
                                                <button wire:click="downloadInvoice({{ $order->id }})"
                                                        class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 transition">
                                                    Download Invoice
                                                </button>
                                                <button wire:click="trackOrder({{ $order->id }})"
                                                        class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 transition">
                                                    Track Order
                                                </button>
                                                @if($order->status === 'delivered')
                                                    <button wire:click="reorderItems({{ $order->id }})"
                                                            class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 transition">
                                                        Reorder Items
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="lg:hidden space-y-4 p-4">
                    @foreach ($orders as $order)
                    <div class="bg-white/5 rounded-lg p-4 border border-white/10" wire:key="mobile-order-{{ $order->id }}">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-white font-bold">Order #{{ $order->id }}</h3>
                                <p class="text-white/70 text-sm">{{ $order->created_at->format('M d, Y H:i A') }}</p>
                            </div>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500',
                                    'processing' => 'bg-blue-500/20 text-blue-300 border-blue-500',
                                    'shipped' => 'bg-purple-500/20 text-purple-300 border-purple-500',
                                    'delivered' => 'bg-green-500/20 text-green-300 border-green-500',
                                    'cancelled' => 'bg-red-500/20 text-red-300 border-red-500',
                                    'refunded' => 'bg-gray-500/20 text-gray-300 border-gray-500',
                                    'failed' => 'bg-red-500/20 text-red-300 border-red-500',
                                ];
                                $statusClass = $statusColors[$order->status] ?? 'bg-gray-500/20 text-gray-300 border-gray-500';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-white/70 text-sm">Items</p>
                                <p class="text-white font-semibold">{{ $order->orderItems->count() }} items</p>
                            </div>
                            <div>
                                <p class="text-white/70 text-sm">Total</p>
                                <p class="text-white font-bold">${{ number_format($order->grand_total, 2) }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button wire:click="viewOrder({{ $order->id }})"
                                    class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                                View Details
                            </button>
                            @if(in_array($order->status, ['pending', 'processing']))
                                <button wire:click="initiateCancellation({{ $order->id }})"
                                        class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                                    Cancel
                                </button>
                            @endif
                            <button wire:click="downloadInvoice({{ $order->id }})"
                                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                                Invoice
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-6 border-t border-white/20">
                    {{ $orders->links() }}
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-12">
                    <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="text-2xl font-bold text-white mb-2">No orders found</h3>
                    <p class="text-white/70 mb-6">You haven't placed any orders yet</p>
                    <a href="/" wire:navigate
                       class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition duration-200 inline-block">
                        Start Shopping
                    </a>
                </div>
            @endif
        </div>

        {{-- Cancellation Modal --}}
        @if($showCancelModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:ignore.self>
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Cancel Order</h3>
                <p class="text-gray-600 mb-4">Please provide a reason for cancelling this order:</p>

                <textarea wire:model="cancellationReason"
                          placeholder="Enter cancellation reason..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500"
                          rows="3"></textarea>

                <div class="flex justify-end space-x-3 mt-6">
                    <button wire:click="$set('showCancelModal', false)"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition">
                        Cancel
                    </button>
                    <button wire:click="cancelOrder"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                        Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
