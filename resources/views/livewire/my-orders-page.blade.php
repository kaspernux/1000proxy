<main class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 py-10 px-2 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/15 to-yellow-500/15 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
    </div>

    <!-- Floating shapes with enhanced animations -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-yellow-400/25 to-blue-400/25 rounded-full blur-3xl animate-bounce duration-[6000ms]"></div>
        <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-yellow-400/15 rounded-full blur-3xl animate-pulse duration-[8000ms]"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-2xl animate-spin duration-[20000ms]"></div>
    </div>

    <section class="w-full max-w-7xl mx-auto relative z-10">
        {{-- Enhanced Header Section --}}
        <header class="text-center mb-12">
            <!-- Breadcrumb -->
            <nav class="flex justify-center items-center space-x-2 text-sm mb-6">
                <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-blue-400 font-medium">My Orders</span>
            </nav>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-4 leading-tight">
                <span class="bg-gradient-to-r from-blue-400 via-yellow-400 to-blue-500 bg-clip-text text-transparent">
                    My Orders
                </span>
            </h1>
            <p class="text-lg md:text-xl text-gray-300 font-light max-w-2xl mx-auto">
                Track and manage your proxy service orders with real-time updates
            </p>
        </header>

        {{-- Enhanced Order Statistics Dashboard --}}
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-12">
            <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10 text-center">
                <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors duration-300">{{ $orderStats['total'] }}</div>
                <div class="text-gray-400 font-medium">Total Orders</div>
            </div>
            <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10 text-center">
                <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-yellow-400 transition-colors duration-300">{{ $orderStats['pending'] }}</div>
                <div class="text-gray-400 font-medium">Pending</div>
            </div>
            <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10 text-center">
                <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors duration-300">{{ $orderStats['processing'] }}</div>
                <div class="text-gray-400 font-medium">Processing</div>
            </div>
            <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10 text-center">
                <div class="text-3xl lg:text-4xl font-bold text-white mb-2 group-hover:text-green-400 transition-colors duration-300">{{ $orderStats['delivered'] }}</div>
                <div class="text-gray-400 font-medium">Delivered</div>
            </div>
            <div class="group p-6 rounded-2xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all duration-500 hover:scale-105 border border-white/10 text-center">
                <div class="text-2xl lg:text-3xl font-bold text-white mb-2 group-hover:text-yellow-400 transition-colors duration-300">${{ number_format($orderStats['total_spent'], 2) }}</div>
                <div class="text-gray-400 font-medium">Total Spent</div>
            </div>
        </section>

        {{-- Enhanced Filters Section with Modern Design --}}
        <section class="bg-white/10 backdrop-blur-lg rounded-3xl shadow-2xl p-10 lg:p-16 xl:p-20 ring-1 ring-white/20 mb-10 relative overflow-hidden">
            <!-- Enhanced Background decoration -->
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/8 to-yellow-500/8"></div>
                <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-400/10 to-transparent rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-yellow-400/10 to-transparent rounded-full blur-3xl"></div>
            </div>
            
            <div class="relative z-10">
                <!-- Enhanced Header Section -->
                <div class="text-center mb-10 lg:mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4 tracking-tight">Find Your Perfect Order</h2>
                    <p class="text-lg lg:text-xl text-gray-300 font-light max-w-2xl mx-auto">
                        Search, filter, and manage your proxy orders with ease
                    </p>
                </div>

                <!-- Mobile Filter Toggle -->
                <div class="lg:hidden flex justify-center mb-8">
                    <button wire:click="toggleFilters"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-2xl transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 ring-1 ring-blue-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                        </svg>
                        {{ $showFilters ? 'Hide' : 'Show' }} Filters
                    </button>
                </div>

                <!-- Enhanced Search Bar -->
                <div class="mb-10 {{ !$showFilters ? 'hidden lg:block' : '' }}">
                    <label class="block text-white/90 font-semibold mb-4 text-lg">Search Orders</label>
                    <div class="relative group">
                        <input type="text"
                               wire:model.live.debounce.300ms="searchTerm"
                               placeholder="Search by Order ID, notes, or any details..."
                               class="w-full px-6 py-4 lg:py-5 pl-14 bg-white/10 border border-white/20 rounded-2xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-400/20 transition-all duration-300 backdrop-blur-sm text-lg hover:bg-white/15 group-hover:border-white/30">
                        <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-6 h-6 text-gray-400 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-blue-500/5 to-yellow-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                    </div>
                </div>

                <!-- Enhanced Filter Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10 {{ !$showFilters ? 'hidden lg:grid' : '' }}">
                    <!-- Status Filter -->
                    <div class="group">
                        <label class="block text-white/90 font-semibold mb-4 text-lg group-hover:text-white transition-colors">Order Status</label>
                        <div class="relative">
                            <select wire:model.live="statusFilter"
                                    class="w-full px-4 py-4 bg-white/10 border border-white/20 rounded-2xl text-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-400/20 transition-all duration-300 backdrop-blur-sm hover:bg-white/15 hover:border-white/30 appearance-none cursor-pointer">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" class="bg-white text-black py-2">{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="group">
                        <label class="block text-white/90 font-semibold mb-4 text-lg group-hover:text-white transition-colors">Date Range</label>
                        <div class="relative">
                            <select wire:model.live="dateRange"
                                    class="w-full px-4 py-4 bg-white/10 border border-white/20 rounded-2xl text-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-400/20 transition-all duration-300 backdrop-blur-sm hover:bg-white/15 hover:border-white/30 appearance-none cursor-pointer">
                                @foreach($dateRangeOptions as $value => $label)
                                    <option value="{{ $value }}" class="bg-white text-black py-2">{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Sort By Filter -->
                    <div class="group">
                        <label class="block text-white/90 font-semibold mb-4 text-lg group-hover:text-white transition-colors">Sort By</label>
                        <div class="relative">
                            <select wire:model.live="sortBy"
                                    class="w-full px-4 py-4 bg-white/10 border border-white/20 rounded-2xl text-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-400/20 transition-all duration-300 backdrop-blur-sm hover:bg-white/15 hover:border-white/30 appearance-none cursor-pointer">
                                @foreach($sortOptions as $value => $label)
                                    <option value="{{ $value }}" class="bg-white text-black py-2">{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Enhanced Filter Actions --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-12 pt-8 border-t border-white/20 {{ !$showFilters ? 'hidden lg:flex' : '' }}">
                <!-- Reset Filters Button -->
                <button wire:click="resetFilters"
                        class="group inline-flex items-center px-6 py-3 text-yellow-400 hover:text-yellow-300 font-semibold mb-4 sm:mb-0 transition-all duration-300 rounded-xl hover:bg-yellow-400/10 hover:scale-105">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset All Filters
                </button>

                {{-- Enhanced Bulk Actions --}}
                @if(count($selectedOrders) > 0)
                <div class="flex items-center space-x-4 bg-white/10 backdrop-blur-sm rounded-2xl px-6 py-3 border border-white/20 shadow-lg">
                    <span class="text-white font-semibold">{{ count($selectedOrders) }} selected</span>
                    <div class="relative">
                        <select wire:model="bulkAction"
                                class="px-4 py-2 bg-white/20 border border-white/30 rounded-xl text-white text-sm focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/20 transition-all backdrop-blur-sm appearance-none cursor-pointer">
                            <option value="" class="bg-white text-black py-2">Choose Action...</option>
                            <option value="download_invoices" class="bg-white text-black py-2">Download Invoices</option>
                            <option value="cancel_orders" class="bg-white text-black py-2">Cancel Orders</option>
                            <option value="mark_received" class="bg-white text-black py-2">Mark as Received</option>
                        </select>
                        <svg class="absolute right-2 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <button wire:click="executeBulkAction"
                            class="bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-700 hover:to-yellow-800 text-white px-6 py-2 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                        Execute
                    </button>
                </div>
                @endif
            </div>
        </section>

        {{-- Enhanced Orders Table --}}
        <section class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-lg overflow-hidden border border-white/20">
            @if($orders->count() > 0)
                {{-- Desktop Table View --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-white/5 backdrop-blur-sm border-b border-white/20">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <input type="checkbox"
                                           wire:model.live="selectAll"
                                           class="rounded border-white/20 bg-white/10 text-blue-600 focus:ring-blue-500">
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
                                           class="rounded border-white/20 bg-white/10 text-blue-600 focus:ring-blue-500">
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
                                    <div class="text-white font-bold">${{ number_format($order->grand_amount, 2) }}</div>
                                    @if($order->payment_status)
                                        <div class="text-white/70 text-sm">{{ ucfirst($order->payment_status) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="viewOrder({{ $order->id }})"
                                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
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
                                                 class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-lg z-10 py-2 border border-white/20">
                                                <button wire:click="downloadInvoice({{ $order->id }})"
                                                        class="block w-full text-left px-4 py-2 text-white hover:bg-white/10 transition">
                                                    Download Invoice
                                                </button>
                                                <button wire:click="trackOrder({{ $order->id }})"
                                                        class="block w-full text-left px-4 py-2 text-white hover:bg-white/10 transition">
                                                    Track Order
                                                </button>
                                                @if($order->status === 'delivered')
                                                    <button wire:click="reorderItems({{ $order->id }})"
                                                            class="block w-full text-left px-4 py-2 text-white hover:bg-white/10 transition">
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
                                <p class="text-white font-bold">${{ number_format($order->grand_amount, 2) }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button wire:click="viewOrder({{ $order->id }})"
                                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                                View Details
                            </button>
                            @if(in_array($order->status, ['pending', 'processing']))
                                <button wire:click="initiateCancellation({{ $order->id }})"
                                        class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                                    Cancel
                                </button>
                            @endif
                            <button wire:click="downloadInvoice({{ $order->id }})"
                                    class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700 transition">
                                Invoice
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-6 border-t border-white/20 bg-white/5 backdrop-blur-sm">
                    {{ $orders->links() }}
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-12 bg-white/5 backdrop-blur-sm">
                    <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="text-2xl font-bold text-white mb-2">No orders found</h3>
                    <p class="text-white/70 mb-6">You haven't placed any orders yet</p>
                    <a href="/" wire:navigate
                       class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition duration-200 inline-block">
                        Start Shopping
                    </a>
                </div>
            @endif
        </section>

        {{-- Cancellation Modal --}}
        @if($showCancelModal)
        <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" wire:ignore.self>
            <div class="bg-gray-800 backdrop-blur-lg rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 border border-white/20">
                <h3 class="text-xl font-bold text-white mb-4">Cancel Order</h3>
                <p class="text-gray-300 mb-4">Please provide a reason for cancelling this order:</p>
                <textarea wire:model="cancellationReason"
                          placeholder="Enter cancellation reason..."
                          class="w-full px-3 py-2 border border-white/20 rounded-lg focus:outline-none focus:border-blue-500 bg-white/10 backdrop-blur-sm text-white placeholder-gray-400"
                          rows="3"></textarea>
                <div class="flex justify-end space-x-3 mt-6">
                    <button wire:click="$set('showCancelModal', false)"
                            class="px-4 py-2 text-gray-300 hover:text-white transition">
                        Cancel
                    </button>
                    <button wire:click="cancelOrder"
                            class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                        Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
        @endif
    </section>

    {{-- Custom CSS for Enhanced Styling --}}
    <style>
        /* Enhanced fade-in animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-scale-in {
            animation: scaleIn 0.6s ease-out forwards;
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        /* Enhanced hover effects */
        .group:hover .group-hover\:translate-x-1 {
            transform: translateX(6px);
        }

        .group:hover .group-hover\:scale-110 {
            transform: scale(1.1);
        }

        .group:hover .group-hover\:rotate-3 {
            transform: rotate(3deg);
        }

        /* Enhanced focus ring */
        .focus\:ring-4:focus {
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) var(--tw-ring-color);
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
        }

        /* Custom scrollbar styles */
        .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }

        .overflow-y-auto::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #3b82f6, #eab308);
            border-radius: 6px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #2563eb, #d97706);
        }

        /* Enhanced backdrop blur effects */
        .backdrop-blur-lg {
            backdrop-filter: blur(16px);
        }

        .backdrop-blur-xl {
            backdrop-filter: blur(24px);
        }

        /* Enhanced shadow utilities */
        .shadow-3xl {
            box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
        }

        /* Enhanced transition utilities */
        .transition-all-300 {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .transition-all-500 {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Custom dropdown arrow styling */
        select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Enhanced form focus states */
        input:focus, select:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Enhanced glassmorphism effects */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-effect:hover {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</main>