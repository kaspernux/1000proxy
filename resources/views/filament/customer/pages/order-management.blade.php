<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-shopping-bag class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ auth()->guard('customer')->user()->orders()->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Services</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ auth()->guard('customer')->user()->clients()->where('enable', true)->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-clock class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Expiring Soon</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ auth()->guard('customer')->user()->clients()
                                ->where('enable', true)
                                ->where('expiry_time', '<=', now()->addDays(7))
                                ->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-currency-dollar class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Spent</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            ${{ number_format(auth()->guard('customer')->user()->orders()->sum('total_amount'), 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('filament.customer.pages.server-browsing') }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <x-heroicon-o-server class="w-4 h-4 mr-2" />
                    Browse Servers
                </a>

                <button onclick="downloadAllConfigs()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                    Download All Configs
                </button>

                <button onclick="renewExpiring()"
                        class="inline-flex items-center px-4 py-2 border border-yellow-300 text-sm font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100">
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                    Renew Expiring
                </button>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Orders</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Time</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 3 months</option>
                        <option value="365">Last year</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Methods</option>
                        <option value="wallet">Wallet</option>
                        <option value="bitcoin">Bitcoin</option>
                        <option value="monero">Monero</option>
                        <option value="solana">Solana</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Range</label>
                    <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Any Amount</option>
                        <option value="0-10">$0 - $10</option>
                        <option value="10-50">$10 - $50</option>
                        <option value="50-100">$50 - $100</option>
                        <option value="100+">$100+</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow">
            {{ $this->table }}
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-4">Order Management Help</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-blue-800 mb-2">Order Status Guide</h4>
                    <ul class="space-y-1 text-sm text-blue-700">
                        <li><span class="font-medium">Pending:</span> Payment being processed</li>
                        <li><span class="font-medium">Processing:</span> Setting up your service</li>
                        <li><span class="font-medium">Completed:</span> Ready to use</li>
                        <li><span class="font-medium">Cancelled:</span> Order was cancelled</li>
                        <li><span class="font-medium">Refunded:</span> Refund processed</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-blue-800 mb-2">Configuration Files</h4>
                    <ul class="space-y-1 text-sm text-blue-700">
                        <li>Download includes VLESS and VMess configs</li>
                        <li>QR codes for easy mobile setup</li>
                        <li>Works with V2Ray, Xray, and compatible clients</li>
                        <li>Configurations auto-expire with your service</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadAllConfigs() {
            // This would trigger download of all active configurations
            alert('Downloading all active configurations...');
        }

        function renewExpiring() {
            // This would renew all services expiring within 7 days
            alert('Renewing all expiring services...');
        }

        // Auto-refresh the table every 60 seconds
        setInterval(() => {
            window.Livewire.dispatch('$refresh');
        }, 60000);
    </script>
</x-filament-panels::page>
