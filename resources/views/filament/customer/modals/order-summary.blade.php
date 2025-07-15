@php
    $totalOrders = $customer->orders()->count();
    $completedOrders = $customer->orders()->where('order_status', 'completed')->count();
    $pendingOrders = $customer->orders()->where('order_status', 'pending')->count();
    $totalSpent = $customer->orders()->where('payment_status', 'paid')->sum('total_amount');
    $activeConfigs = $customer->orders()
        ->where('order_status', 'completed')
        ->with('orderItems.orderServerClients.serverClient')
        ->get()
        ->flatMap(fn($order) => $order->getAllClients())
        ->where('status', 'active')
        ->count();
    
    $monthlyStats = $customer->orders()
        ->selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(total_amount) as total')
        ->whereYear('created_at', now()->year)
        ->groupBy('month')
        ->get()
        ->keyBy('month');
@endphp

<div class="p-6 space-y-6">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Order Summary</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Your complete order overview and statistics</p>
    </div>

    {{-- Quick Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totalOrders }}</div>
            <div class="text-sm text-blue-800 dark:text-blue-300">Total Orders</div>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $completedOrders }}</div>
            <div class="text-sm text-green-800 dark:text-green-300">Completed</div>
        </div>
        
        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $pendingOrders }}</div>
            <div class="text-sm text-orange-800 dark:text-orange-300">Pending</div>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $activeConfigs }}</div>
            <div class="text-sm text-purple-800 dark:text-purple-300">Active Configs</div>
        </div>
    </div>

    {{-- Financial Summary --}}
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Financial Summary</h3>
        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($totalSpent, 2) }}</div>
        <p class="text-indigo-800 dark:text-indigo-300">Total Amount Spent</p>
        @if($totalOrders > 0)
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                Average per order: ${{ number_format($totalSpent / $totalOrders, 2) }}
            </p>
        @endif
    </div>

    {{-- Monthly Activity Chart --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Activity</h3>
        <div class="space-y-3">
            @for ($month = 1; $month <= 12; $month++)
                @php
                    $monthData = $monthlyStats->get($month);
                    $orderCount = $monthData?->count ?? 0;
                    $monthTotal = $monthData?->total ?? 0;
                    $monthName = \Carbon\Carbon::create()->month($month)->format('M');
                    $percentage = $totalOrders > 0 ? ($orderCount / $totalOrders) * 100 : 0;
                @endphp
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-8">{{ $monthName }}</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-32">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $orderCount }} orders</div>
                        @if($monthTotal > 0)
                            <div class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($monthTotal, 2) }}</div>
                        @endif
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Orders</h3>
        <div class="space-y-3">
            @forelse($customer->orders()->latest()->limit(5)->get() as $order)
                <div class="flex justify-between items-center py-2">
                    <div>
                        <span class="font-medium text-gray-900 dark:text-white">Order #{{ $order->id }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                            {{ $order->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($order->total_amount, 2) }}
                        </span>
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($order->order_status === 'completed') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                            @elseif($order->order_status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                            @endif">
                            {{ ucfirst($order->order_status) }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No recent orders</p>
            @endforelse
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <button onclick="window.open('{{ route('filament.customer.pages.order-management') }}', '_blank')" 
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Place New Order
        </button>
        
        <button onclick="window.open('{{ route('filament.customer.pages.configuration-guides') }}', '_blank')" 
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            Setup Guides
        </button>
    </div>
</div>
