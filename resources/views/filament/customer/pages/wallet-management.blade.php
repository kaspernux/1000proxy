<x-filament-panels::page>
    {{-- Custom style overrides for Add Funds modal (payment method select placeholder background) --}}
    <style>
        /* Dark, consistent background and readable text for Add Funds modal fields */
        .fi-modal [data-field-name="payment_method"] .fi-input,
        .fi-modal [data-field-name="payment_method"] .fi-select-trigger,
        .fi-modal [data-field-name="payment_method"] select,
        .fi-modal [data-field-name="payment_method"] .fi-fo-select,
        .fi-modal [data-field-name="payment_method"] .choices__inner,
        .fi-modal [data-field-name="amount"] .fi-input {
            background-color: #0f172a !important; /* slate-900 */
            color: #f1f5f9 !important; /* slate-100 */
            border-color: #334155 !important; /* slate-600 */
        }
        .fi-modal [data-field-name="payment_method"] .fi-input:focus,
        .fi-modal [data-field-name="payment_method"] .fi-select-trigger:focus,
        .fi-modal [data-field-name="payment_method"] select:focus,
        .fi-modal [data-field-name="amount"] .fi-input:focus {
            border-color: #6366f1 !important; /* indigo-500 */
            box-shadow: 0 0 0 1px #6366f1;
        }
        .fi-modal [data-field-name="payment_method"] .fi-input::placeholder,
        .fi-modal [data-field-name="payment_method"] .fi-select-trigger[data-placeholder],
        .fi-modal [data-field-name="amount"] .fi-input::placeholder {
            color: #64748b !important; /* slate-500 */
            opacity: 1;
        }
        .dark .fi-modal [data-field-name="payment_method"] .fi-input::placeholder,
        .dark .fi-modal [data-field-name="payment_method"] .fi-select-trigger[data-placeholder],
        .dark .fi-modal [data-field-name="amount"] .fi-input::placeholder {
            color: #94a3b8 !important; /* slate-400 */
        }
        .fi-modal [data-field-name="payment_method"] .fi-select-options { background-color: #1e293b; }
        .fi-modal [data-field-name="payment_method"] .choices__inner { min-height: 2.75rem; }
    </style>
    <div class="fi-section-content-ctn">
        <!-- Mobile-First Header Section -->
        <div class="fi-section-header mb-12 pb-6">
            <div class="fi-section-header-wrapper">
                <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                    <div class="flex-1 min-w-0">
                        <h1 class="fi-section-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20 mr-3 flex-shrink-0">
                                    <x-heroicon-s-wallet class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                </div>
                                <span class="truncate">Wallet Management</span>
                            </div>
                        </h1>
                        <p class="fi-section-header-description mt-2 text-sm text-gray-500 dark:text-gray-400 leading-6">
                            Secure wallet management with cryptocurrency support and transaction tracking
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Wallet Balance Card -->
        <div class="my-16">
            <x-filament::section class="bg-gradient-to-br from-primary-500 via-blue-600 to-purple-700 text-white border-0 shadow-2xl overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-r from-white/5 to-transparent"></div>
                <div class="relative p-6 md:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
                                <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm flex-shrink-0">
                                    <x-heroicon-s-banknotes class="h-6 w-6 md:h-8 md:w-8 text-white" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base md:text-lg font-medium text-white/90 truncate">Available Balance</h3>
                                    <p class="text-xs text-white/70">Ready for immediate use</p>
                                </div>
                            </div>
                            <p class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-2 break-all">
                                ${{ number_format($this->walletBalance, 2) }}
                            </p>
                            <div class="flex items-center gap-2 text-white/80">
                                <x-heroicon-o-shield-check class="h-4 w-4 flex-shrink-0" />
                                <span class="text-sm">Secured & Encrypted</span>
                            </div>
                        </div>
                        
                        <div class="flex-shrink-0 self-center lg:self-auto">
                            <div class="relative">
                                <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/10 rounded-2xl backdrop-blur-sm flex items-center justify-center">
                                    <x-heroicon-o-wallet class="w-10 h-10 lg:w-12 lg:h-12 text-white/90" />
                                </div>
                                <div class="absolute -top-2 -right-2 w-6 h-6 bg-success-500 rounded-full flex items-center justify-center">
                                    <x-heroicon-s-check class="w-3 h-3 text-white" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Enhanced Quick Actions Dashboard -->
        <div class="my-16">
            <x-filament::section class="shadow-xl">
                <x-slot name="heading">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex-shrink-0">
                            <x-heroicon-s-bolt class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Quick Actions</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Manage your wallet with one-click actions</p>
                        </div>
                    </div>
                </x-slot>

                <div class="p-4 md:p-6">
                    <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                        <!-- Enhanced Add Funds Action -->
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-success-200 to-emerald-300 dark:from-success-600 dark:to-emerald-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <x-filament::button
                                color="success"
                                size="xl"
                                outlined
                                icon="heroicon-o-plus-circle"
                                class="relative h-auto py-6 md:py-8 px-4 md:px-6 flex-col justify-center items-center min-h-[180px] md:min-h-[200px] w-full border-2 border-success-200 dark:border-success-600 hover:border-success-400 dark:hover:border-success-500 transition-all duration-300 rounded-2xl"
                            >
                                <div class="text-center space-y-3 md:space-y-4">
                                    <div class="p-3 md:p-4 bg-success-100 dark:bg-success-900/30 rounded-full mx-auto w-fit group-hover:bg-success-200 dark:group-hover:bg-success-800/40 transition-colors duration-300">
                                        <x-heroicon-o-banknotes class="w-8 h-8 md:w-10 md:h-10 text-success-600 dark:text-success-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg md:text-xl mb-2">Add Funds</div>
                                        <div class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mb-2 md:mb-3">Top up your wallet balance</div>
                                        <x-filament::badge color="success" size="sm" class="px-2 md:px-3 py-1">
                                            <x-heroicon-o-arrow-trending-up class="w-3 h-3 mr-1" />
                                            Instant Deposit
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>

                        <!-- Enhanced Withdraw Action -->
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-warning-200 to-orange-300 dark:from-warning-600 dark:to-orange-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <x-filament::button
                                color="warning"
                                size="xl"
                                outlined
                                icon="heroicon-o-arrow-up-tray"
                                class="relative h-auto py-6 md:py-8 px-4 md:px-6 flex-col justify-center items-center min-h-[180px] md:min-h-[200px] w-full border-2 border-warning-200 dark:border-warning-600 hover:border-warning-400 dark:hover:border-warning-500 transition-all duration-300 rounded-2xl"
                            >
                                <div class="text-center space-y-3 md:space-y-4">
                                    <div class="p-3 md:p-4 bg-warning-100 dark:bg-warning-900/30 rounded-full mx-auto w-fit group-hover:bg-warning-200 dark:group-hover:bg-warning-800/40 transition-colors duration-300">
                                        <x-heroicon-o-arrow-up-circle class="w-8 h-8 md:w-10 md:h-10 text-warning-600 dark:text-warning-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg md:text-xl mb-2">Withdraw Funds</div>
                                        <div class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mb-2 md:mb-3">
                                            @if($this->walletBalance >= 10)
                                                Withdraw your funds securely
                                            @else
                                                Minimum $10.00 required
                                            @endif
                                        </div>
                                        <x-filament::badge color="{{ $this->walletBalance >= 10 ? 'warning' : 'gray' }}" size="sm" class="px-2 md:px-3 py-1">
                                            <x-heroicon-o-shield-check class="w-3 h-3 mr-1" />
                                            {{ $this->walletBalance >= 10 ? 'Available' : 'Unavailable' }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>

                        <!-- Enhanced History Action -->
                        <div class="group relative transform transition-all duration-300 hover:scale-105 md:col-span-2 xl:col-span-1">
                            <div class="absolute inset-0 bg-gradient-to-r from-info-200 to-blue-300 dark:from-info-600 dark:to-blue-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <x-filament::button
                                color="info"
                                size="xl"
                                outlined
                                icon="heroicon-o-document-text"
                                class="relative h-auto py-6 md:py-8 px-4 md:px-6 flex-col justify-center items-center min-h-[180px] md:min-h-[200px] w-full border-2 border-info-200 dark:border-info-600 hover:border-info-400 dark:hover:border-info-500 transition-all duration-300 rounded-2xl"
                            >
                                <div class="text-center space-y-3 md:space-y-4">
                                    <div class="p-3 md:p-4 bg-info-100 dark:bg-info-900/30 rounded-full mx-auto w-fit group-hover:bg-info-200 dark:group-hover:bg-info-800/40 transition-colors duration-300">
                                        <x-heroicon-o-clock class="w-8 h-8 md:w-10 md:h-10 text-info-600 dark:text-info-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg md:text-xl mb-2">Transaction History</div>
                                        <div class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mb-2 md:mb-3">View detailed transaction logs</div>
                                        <x-filament::badge color="info" size="sm" class="px-2 md:px-3 py-1">
                                            <x-heroicon-o-chart-bar class="w-3 h-3 mr-1" />
                                            Full History
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Enhanced Transaction Statistics -->
        <div class="my-16">
            <x-filament::section class="shadow-xl">
                <x-slot name="heading">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex-shrink-0">
                            <x-heroicon-s-chart-bar class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Transaction Statistics</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Overview of your wallet activity</p>
                        </div>
                    </div>
                </x-slot>

                <div class="p-4 md:p-6">
                    <div class="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
                        <!-- Total Deposits -->
                        <x-filament::section class="bg-gradient-to-br from-success-500 to-emerald-600 text-white border-0 shadow-lg">
                            <div class="flex items-center justify-between p-3 md:p-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 md:gap-3 mb-2">
                                        <div class="p-1.5 md:p-2 bg-white/20 rounded-lg flex-shrink-0">
                                            <x-heroicon-s-arrow-down-tray class="h-5 w-5 md:h-6 md:w-6 text-white" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs md:text-sm font-medium text-success-100">Total Deposits</p>
                                            <p class="text-lg md:text-2xl font-bold text-white truncate">
                                                ${{ number_format(\Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('type', 'deposit')->where('status', 'completed')->sum('amount'), 2) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-success-200 text-xs">
                                        <x-heroicon-o-arrow-trending-up class="h-3 w-3 mr-1 flex-shrink-0" />
                                        <span class="truncate">Lifetime deposits</span>
                                    </div>
                                </div>
                            </div>
                        </x-filament::section>

                        <!-- Total Purchases -->
                        <x-filament::section class="bg-gradient-to-br from-info-500 to-blue-600 text-white border-0 shadow-lg">
                            <div class="flex items-center justify-between p-3 md:p-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 md:gap-3 mb-2">
                                        <div class="p-1.5 md:p-2 bg-white/20 rounded-lg flex-shrink-0">
                                            <x-heroicon-s-shopping-cart class="h-5 w-5 md:h-6 md:w-6 text-white" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs md:text-sm font-medium text-info-100">Total Purchases</p>
                                            <p class="text-lg md:text-2xl font-bold text-white truncate">
                                                ${{ number_format(\Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('type', 'purchase')->sum('amount'), 2) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-info-200 text-xs">
                                        <x-heroicon-o-shopping-bag class="h-3 w-3 mr-1 flex-shrink-0" />
                                        <span class="truncate">Service spending</span>
                                    </div>
                                </div>
                            </div>
                        </x-filament::section>

                        <!-- Total Withdrawals -->
                        <x-filament::section class="bg-gradient-to-br from-warning-500 to-orange-500 text-white border-0 shadow-lg">
                            <div class="flex items-center justify-between p-3 md:p-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 md:gap-3 mb-2">
                                        <div class="p-1.5 md:p-2 bg-white/20 rounded-lg flex-shrink-0">
                                            <x-heroicon-s-arrow-up-tray class="h-5 w-5 md:h-6 md:w-6 text-white" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs md:text-sm font-medium text-warning-100">Total Withdrawals</p>
                                            <p class="text-lg md:text-2xl font-bold text-white truncate">
                                                ${{ number_format(\Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('type', 'withdrawal')->where('status', 'completed')->sum('amount'), 2) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-warning-200 text-xs">
                                        <x-heroicon-o-arrow-up-circle class="h-3 w-3 mr-1 flex-shrink-0" />
                                        <span class="truncate">Successful withdrawals</span>
                                    </div>
                                </div>
                            </div>
                        </x-filament::section>

                        <!-- Pending Transactions -->
                        <x-filament::section class="bg-gradient-to-br from-purple-500 to-indigo-600 text-white border-0 shadow-lg">
                            <div class="flex items-center justify-between p-3 md:p-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 md:gap-3 mb-2">
                                        <div class="p-1.5 md:p-2 bg-white/20 rounded-lg flex-shrink-0">
                                            <x-heroicon-s-clock class="h-5 w-5 md:h-6 md:w-6 text-white animate-pulse" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs md:text-sm font-medium text-purple-100">Pending</p>
                                            <p class="text-lg md:text-2xl font-bold text-white">
                                                {{ \Illuminate\Support\Facades\DB::table('wallet_transactions')->where('customer_id', auth()->guard('customer')->id())->where('status', 'pending')->count() }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-purple-200 text-xs">
                                        <div class="w-2 h-2 bg-purple-200 rounded-full mr-1 animate-pulse flex-shrink-0"></div>
                                        <span class="truncate">Processing transactions</span>
                                    </div>
                                </div>
                            </div>
                        </x-filament::section>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Enhanced Recent Transactions -->
        <div class="my-16">
            <x-filament::section class="shadow-xl">
                <x-slot name="heading">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="p-2 bg-info-100 dark:bg-info-900/30 rounded-xl flex-shrink-0">
                                <x-heroicon-s-document-text class="h-6 w-6 text-info-600 dark:text-info-400" />
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Recent Transactions</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Your latest wallet activity</p>
                            </div>
                        </div>
                        <x-filament::badge color="info" size="sm" class="flex-shrink-0">
                            <x-heroicon-o-clock class="w-3 h-3 mr-1" />
                            Live Updates
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="p-4 md:p-6">
                    <div class="space-y-3 md:space-y-4">
                        @php
                            $recentTransactions = \Illuminate\Support\Facades\DB::table('wallet_transactions')
                                ->where('customer_id', auth()->guard('customer')->id())
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp

                        @forelse($recentTransactions as $transaction)
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 md:p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                <div class="flex items-center gap-3 md:gap-4 min-w-0 flex-1">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center shadow-sm
                                            {{ $transaction->type === 'deposit' ? 'bg-success-100 dark:bg-success-900/30' :
                                               ($transaction->type === 'withdrawal' ? 'bg-warning-100 dark:bg-warning-900/30' :
                                                ($transaction->type === 'purchase' ? 'bg-info-100 dark:bg-info-900/30' : 'bg-gray-100 dark:bg-gray-700')) }}">
                                            @if($transaction->type === 'deposit')
                                                <x-heroicon-s-arrow-down-tray class="w-5 h-5 md:w-6 md:h-6 text-success-600 dark:text-success-400" />
                                            @elseif($transaction->type === 'withdrawal')
                                                <x-heroicon-s-arrow-up-tray class="w-5 h-5 md:w-6 md:h-6 text-warning-600 dark:text-warning-400" />
                                            @elseif($transaction->type === 'purchase')
                                                <x-heroicon-s-shopping-cart class="w-5 h-5 md:w-6 md:h-6 text-info-600 dark:text-info-400" />
                                            @else
                                                <x-heroicon-s-currency-dollar class="w-5 h-5 md:w-6 md:h-6 text-gray-600 dark:text-gray-400" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mb-1">
                                            <p class="text-sm md:text-base font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $transaction->description ?? ucfirst($transaction->type) }}
                                            </p>
                                            <x-filament::badge 
                                                color="{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'gray')) }}" 
                                                size="xs"
                                                class="flex-shrink-0"
                                            >
                                                {{ ucfirst($transaction->status) }}
                                            </x-filament::badge>
                                        </div>
                                        <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                            <x-heroicon-o-calendar class="w-3 h-3 md:w-4 md:h-4 flex-shrink-0" />
                                            <span class="truncate">{{ \Carbon\Carbon::parse($transaction->created_at)->format('M j, Y H:i') }}</span>
                                        </p>
                                        @if(!empty($transaction->payment_id))
                                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                TxID: <a href="https://nowpayments.io/payment/?iid={{ urlencode($transaction->payment_id) }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 dark:text-primary-400 hover:underline">{{ $transaction->payment_id }}</a>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-base md:text-lg font-bold
                                        {{ $transaction->type === 'deposit' || $transaction->type === 'refund' ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                        {{ $transaction->type === 'deposit' || $transaction->type === 'refund' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 md:py-12">
                                <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <x-heroicon-o-document-text class="w-8 h-8 md:w-10 md:h-10 text-gray-400" />
                                </div>
                                <h3 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-2">No transactions yet</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 md:mb-6">Get started by adding funds to your wallet</p>
                                <x-filament::button color="primary" icon="heroicon-o-plus" size="sm">
                                    Add Your First Deposit
                                </x-filament::button>
                            </div>
                        @endforelse
                    </div>

                    @if($recentTransactions->count() > 0)
                        <div class="mt-4 md:mt-6 pt-4 md:pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
                            <x-filament::button color="gray" outlined icon="heroicon-o-eye" size="sm">
                                View All Transactions
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        <!-- Enhanced Payment Methods -->
        <div class="my-16">
            <x-filament::section class="shadow-xl">
                <x-slot name="heading">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="p-2 bg-warning-100 dark:bg-warning-900/30 rounded-xl flex-shrink-0">
                            <x-heroicon-s-credit-card class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Supported Payment Methods</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Secure cryptocurrency payment options</p>
                        </div>
                    </div>
                </x-slot>

                <div class="p-4 md:p-6">
                    <div class="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Bitcoin -->
                        <div class="group relative p-4 md:p-6 border-2 border-orange-200 dark:border-orange-700 rounded-2xl hover:border-orange-400 dark:hover:border-orange-500 transition-all duration-300 hover:shadow-lg bg-gradient-to-br from-orange-50 to-yellow-50 dark:from-orange-900/10 dark:to-yellow-900/10">
                            <div class="text-center">
                                <div class="w-12 h-12 md:w-16 md:h-16 bg-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-3 md:mb-4 shadow-lg">
                                    <span class="text-xl md:text-2xl font-bold text-white">₿</span>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white mb-1">Bitcoin</h3>
                                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mb-2 md:mb-3">BTC Network</p>
                                <x-filament::badge color="orange" size="sm" class="px-2 md:px-3 py-1">
                                    <x-heroicon-o-shield-check class="w-3 h-3 mr-1" />
                                    Secure
                                </x-filament::badge>
                            </div>
                        </div>

                        <!-- Monero -->
                        <div class="group relative p-4 md:p-6 border-2 border-gray-200 dark:border-gray-700 rounded-2xl hover:border-gray-400 dark:hover:border-gray-500 transition-all duration-300 hover:shadow-lg bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900/10 dark:to-slate-900/10">
                            <div class="text-center">
                                <div class="w-12 h-12 md:w-16 md:h-16 bg-gray-600 rounded-2xl flex items-center justify-center mx-auto mb-3 md:mb-4 shadow-lg">
                                    <span class="text-xl md:text-2xl font-bold text-white">ⓧ</span>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white mb-1">Monero</h3>
                                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mb-2 md:mb-3">XMR Network</p>
                                <x-filament::badge color="gray" size="sm" class="px-2 md:px-3 py-1">
                                    <x-heroicon-o-eye-slash class="w-3 h-3 mr-1" />
                                    Private
                                </x-filament::badge>
                            </div>
                        </div>

                        <!-- Solana -->
                        <div class="group relative p-4 md:p-6 border-2 border-purple-200 dark:border-purple-700 rounded-2xl hover:border-purple-400 dark:hover:border-purple-500 transition-all duration-300 hover:shadow-lg bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/10 dark:to-indigo-900/10">
                            <div class="text-center">
                                <div class="w-12 h-12 md:w-16 md:h-16 bg-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-3 md:mb-4 shadow-lg">
                                    <span class="text-xl md:text-2xl font-bold text-white">◎</span>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white mb-1">Solana</h3>
                                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mb-2 md:mb-3">SOL Network</p>
                                <x-filament::badge color="purple" size="sm" class="px-2 md:px-3 py-1">
                                    <x-heroicon-o-bolt class="w-3 h-3 mr-1" />
                                    Fast
                                </x-filament::badge>
                            </div>
                        </div>

                        <!-- USDT -->
                        <div class="group relative p-4 md:p-6 border-2 border-success-200 dark:border-success-700 rounded-2xl hover:border-success-400 dark:hover:border-success-500 transition-all duration-300 hover:shadow-lg bg-gradient-to-br from-success-50 to-emerald-50 dark:from-success-900/10 dark:to-emerald-900/10">
                            <div class="text-center">
                                <div class="w-12 h-12 md:w-16 md:h-16 bg-success-600 rounded-2xl flex items-center justify-center mx-auto mb-3 md:mb-4 shadow-lg">
                                    <span class="text-xl md:text-2xl font-bold text-white">₮</span>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white mb-1">USDT</h3>
                                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mb-2 md:mb-3">TRC20 Network</p>
                                <x-filament::badge color="success" size="sm" class="px-2 md:px-3 py-1">
                                    <x-heroicon-o-chart-bar class="w-3 h-3 mr-1" />
                                    Stable
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Enhanced Wallet Security Info -->
        <div class="my-16">
            <x-filament::section class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 border-2 border-blue-200 dark:border-blue-700 shadow-xl">
                <x-slot name="heading">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex-shrink-0">
                            <x-heroicon-s-shield-check class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg md:text-xl font-bold text-blue-900 dark:text-blue-100">Wallet Security & Information</h2>
                            <p class="text-sm text-blue-700 dark:text-blue-300">Your security and transaction details</p>
                        </div>
                    </div>
                </x-slot>

                <div class="p-4 md:p-6">
                    <div class="grid gap-6 md:gap-8 grid-cols-1 lg:grid-cols-2">
                        <!-- Security Features -->
                        <div class="space-y-4 md:space-y-6">
                            <div>
                                <h3 class="text-base md:text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3 md:mb-4 flex items-center gap-2">
                                    <x-heroicon-o-lock-closed class="w-4 h-4 md:w-5 md:h-5" />
                                    <span class="truncate">Security Features</span>
                                </h3>
                                <div class="space-y-2 md:space-y-3">
                                    <div class="flex items-center gap-2 md:gap-3 p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="w-6 h-6 md:w-8 md:h-8 bg-success-100 dark:bg-success-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                            <x-heroicon-s-shield-check class="w-3 h-3 md:w-4 md:h-4 text-success-600 dark:text-success-400" />
                                        </div>
                                        <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 min-w-0">All transactions are encrypted and secured</span>
                                    </div>
                                    <div class="flex items-center gap-2 md:gap-3 p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="w-6 h-6 md:w-8 md:h-8 bg-warning-100 dark:bg-warning-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                            <x-heroicon-s-exclamation-triangle class="w-3 h-3 md:w-4 md:h-4 text-warning-600 dark:text-warning-400" />
                                        </div>
                                        <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 min-w-0">Automatic fraud detection and prevention</span>
                                    </div>
                                    <div class="flex items-center gap-2 md:gap-3 p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="w-6 h-6 md:w-8 md:h-8 bg-info-100 dark:bg-info-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                            <x-heroicon-s-eye class="w-3 h-3 md:w-4 md:h-4 text-info-600 dark:text-info-400" />
                                        </div>
                                        <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 min-w-0">Real-time transaction monitoring</span>
                                    </div>
                                    <div class="flex items-center gap-2 md:gap-3 p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="w-6 h-6 md:w-8 md:h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                            <x-heroicon-s-device-phone-mobile class="w-3 h-3 md:w-4 md:h-4 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 min-w-0">Two-factor authentication protection</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Limits -->
                        <div class="space-y-4 md:space-y-6">
                            <div>
                                <h3 class="text-base md:text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3 md:mb-4 flex items-center gap-2">
                                    <x-heroicon-o-scale class="w-4 h-4 md:w-5 md:h-5" />
                                    <span class="truncate">Transaction Limits</span>
                                </h3>
                                <div class="space-y-2 md:space-y-3">
                                    <div class="flex items-center justify-between p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <x-heroicon-o-arrow-down-tray class="w-3 h-3 md:w-4 md:h-4 text-success-600 dark:text-success-400 flex-shrink-0" />
                                            <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 truncate">Minimum deposit</span>
                                        </div>
                                        <x-filament::badge color="success" size="sm">$5.00</x-filament::badge>
                                    </div>
                                    <div class="flex items-center justify-between p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <x-heroicon-o-arrow-down-tray class="w-3 h-3 md:w-4 md:h-4 text-info-600 dark:text-info-400 flex-shrink-0" />
                                            <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 truncate">Maximum deposit</span>
                                        </div>
                                        <x-filament::badge color="info" size="sm">$1,000.00</x-filament::badge>
                                    </div>
                                    <div class="flex items-center justify-between p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <x-heroicon-o-arrow-up-tray class="w-3 h-3 md:w-4 md:h-4 text-warning-600 dark:text-warning-400 flex-shrink-0" />
                                            <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 truncate">Minimum withdrawal</span>
                                        </div>
                                        <x-filament::badge color="warning" size="sm">$10.00</x-filament::badge>
                                    </div>
                                    <div class="flex items-center justify-between p-2 md:p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <x-heroicon-o-clock class="w-3 h-3 md:w-4 md:h-4 text-purple-600 dark:text-purple-400 flex-shrink-0" />
                                            <span class="text-xs md:text-sm font-medium text-blue-800 dark:text-blue-200 truncate">Processing time</span>
                                        </div>
                                        <x-filament::badge color="purple" size="sm">24-48 hours</x-filament::badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="mt-6 md:mt-8 p-3 md:p-4 bg-blue-100 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-700">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                            <div class="p-2 bg-blue-500 rounded-lg flex-shrink-0">
                                <x-heroicon-s-information-circle class="w-4 h-4 md:w-5 md:h-5 text-white" />
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1 text-sm md:text-base">Security Notice</h4>
                                <p class="text-xs md:text-sm text-blue-800 dark:text-blue-200">
                                    Your wallet is protected by enterprise-grade security measures. All cryptocurrency transactions are irreversible, 
                                    so please double-check all details before confirming any transaction.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
