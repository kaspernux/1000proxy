<x-filament-panels::page>
    <div class="fi-section-content-ctn">
        <!-- Header (mirrors order management style) -->
        <div class="fi-section-header mb-12 pb-6">
            <div class="fi-section-header-wrapper">
                <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                    <div class="flex-1 min-w-0">
                        <h1 class="fi-section-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-50 dark:bg-success-900/20 mr-3 flex-shrink-0">
                                    <x-heroicon-s-gift class="h-6 w-6 text-success-600 dark:text-success-400" />
                                </div>
                                <span class="truncate">Referral Program Dashboard</span>
                            </div>
                        </h1>
                        <p class="fi-section-header-description mt-2 text-sm text-gray-500 dark:text-gray-400 leading-6">
                            Track referral performance, manage codes, withdraw earnings, and optimize growth.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        @php($stats = $this->getReferralStats())
        <div class="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 mb-12">
            <x-filament::section class="bg-gradient-to-br from-success-500 to-emerald-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-users class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-success-100">Total Referrals</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">{{ $stats['total_referrals'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-success-200 text-xs">
                            <x-heroicon-o-arrow-trending-up class="h-3 w-3 mr-1" /> Cumulative
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="bg-gradient-to-br from-primary-500 to-indigo-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-user-circle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-primary-100">Active Referrals</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">{{ $stats['active_referrals'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-primary-200 text-xs">
                            <div class="w-2 h-2 bg-primary-200 rounded-full mr-1 animate-pulse"></div>
                            Engaged users
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="bg-gradient-to-br from-warning-500 to-orange-500 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-currency-dollar class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-warning-100">Total Earnings</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">${{ number_format($stats['total_earnings'], 2) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-warning-200 text-xs">
                            <x-heroicon-o-banknotes class="h-3 w-3 mr-1" /> Lifetime revenue
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="bg-gradient-to-br from-fuchsia-500 to-pink-600 text-white border-0 shadow-lg">
                <div class="flex items-center justify-between p-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <x-heroicon-s-chart-bar class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-fuchsia-100">Conversion Rate</p>
                                <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($stats['conversion_rate'], 1) }}%</p>
                            </div>
                        </div>
                        <div class="flex items-center text-fuchsia-200 text-xs">
                            Performance
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Performance Metrics Row -->
        <div class="my-16">
            <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-3">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-chart-bar-square class="w-5 h-5 text-success-600" /> Monthly Signups
                        </div>
                    </x-slot>
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">This Month</span>
                            <x-filament::badge color="success" size="xs">+{{ rand(1,12) }}</x-filament::badge>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-success-500 h-2 rounded-full" style="width: {{ rand(20,100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Recent referral activity</p>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-check-badge class="w-5 h-5 text-primary-600" /> Activation Rate
                        </div>
                    </x-slot>
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active</span>
                            <x-filament::badge color="primary" size="xs">{{ rand(40,95) }}%</x-filament::badge>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-primary-500 h-2 rounded-full" style="width: {{ rand(40,95) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Users who became active</p>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calculator class="w-5 h-5 text-warning-600" /> Avg Commission
                        </div>
                    </x-slot>
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Per Referral</span>
                            <x-filament::badge color="warning" size="xs">${{ number_format($stats['total_referrals'] ? $stats['total_earnings'] / max(1,$stats['total_referrals']) : 0, 2) }}</x-filament::badge>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($stats['total_referrals'] ? $stats['total_earnings'] / max(1,$stats['total_referrals']) : 0, 2) }}</p>
                            <p class="text-xs text-gray-500">Average earning</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Referral Code + Quick Actions (large buttons) -->
        <div class="my-16">
            <x-filament::section class="shadow-xl">
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-success-100 dark:bg-success-900/30 rounded-xl">
                                <x-heroicon-s-gift class="h-6 w-6 text-success-600 dark:text-success-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Referral Tools</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Manage and share your referral code</p>
                            </div>
                        </div>
                        <x-filament::badge color="success" size="sm">
                            <div class="flex items-center gap-1">
                                <x-heroicon-o-sparkles class="h-3 w-3" /> Active Program
                            </div>
                        </x-filament::badge>
                    </div>
                </x-slot>
                <div class="p-8">
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-success-200 to-emerald-300 dark:from-success-600 dark:to-emerald-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity"></div>
                            <x-filament::button color="success" size="xl" outlined icon="heroicon-o-clipboard"
                                onclick="copyToClipboard('{{ $this->getReferralCode() }}')"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-success-200 dark:border-success-600 hover:border-success-400 dark:hover:border-success-500 transition-all duration-300 rounded-2xl">
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-success-100 dark:bg-success-900/30 rounded-full mx-auto w-fit group-hover:bg-success-200 dark:group-hover:bg-success-800/40 transition-colors">
                                        <x-heroicon-o-clipboard-document-check class="w-8 h-8 text-success-600 dark:text-success-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">Copy Code</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $this->getReferralCode() }}</div>
                                        <x-filament::badge color="success" size="sm" class="px-3 py-1">Ready</x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-primary-200 to-indigo-300 dark:from-primary-600 dark:to-indigo-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity"></div>
                            <x-filament::button color="primary" size="xl" outlined icon="heroicon-o-arrow-path"
                                x-on:click="$wire.generateNewReferralCode()"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-primary-200 dark:border-primary-600 hover:border-primary-400 dark:hover:border-primary-500 transition-all duration-300 rounded-2xl">
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-primary-100 dark:bg-primary-900/30 rounded-full mx-auto w-fit group-hover:bg-primary-200 dark:group-hover:bg-primary-800/40 transition-colors">
                                        <x-heroicon-o-arrow-path class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">New Code</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Regenerate referral</div>
                                        <x-filament::badge color="primary" size="sm" class="px-3 py-1">Generate</x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-info-200 to-blue-300 dark:from-info-600 dark:to-blue-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity"></div>
                            <x-filament::button color="info" size="xl" outlined icon="heroicon-o-share"
                                x-on:click="$wire.shareReferral({ platform: 'copy' })"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-info-200 dark:border-info-600 hover:border-info-400 dark:hover:border-info-500 transition-all duration-300 rounded-2xl">
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-info-100 dark:bg-info-900/30 rounded-full mx-auto w-fit group-hover:bg-info-200 dark:group-hover:bg-info-800/40 transition-colors">
                                        <x-heroicon-o-paper-airplane class="w-8 h-8 text-info-600 dark:text-info-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">Share Link</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Spread the word</div>
                                        <x-filament::badge color="info" size="sm" class="px-3 py-1">Share</x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>
                        <div class="group relative transform transition-all duration-300 hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-warning-200 to-orange-300 dark:from-warning-600 dark:to-orange-700 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity"></div>
                            <x-filament::button color="warning" size="xl" outlined icon="heroicon-o-banknotes"
                                x-on:click="$wire.requestWithdrawal()"
                                class="relative h-auto py-8 px-6 flex-col justify-center items-center min-h-[180px] w-full border-2 border-warning-200 dark:border-warning-600 hover:border-warning-400 dark:hover:border-warning-500 transition-all duration-300 rounded-2xl">
                                <div class="text-center space-y-4">
                                    <div class="p-4 bg-warning-100 dark:bg-warning-900/30 rounded-full mx-auto w-fit group-hover:bg-warning-200 dark:group-hover:bg-warning-800/40 transition-colors">
                                        <x-heroicon-o-hand-raised class="w-8 h-8 text-warning-600 dark:text-warning-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg mb-1">Withdraw</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Available funds</div>
                                        <x-filament::badge color="warning" size="sm" class="px-3 py-1">${{ number_format($stats['available_earnings'],2) }}</x-filament::badge>
                                    </div>
                                </div>
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Referrals Table Section -->
        <div class="my-16">
            <x-filament::section class="shadow-xl">
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-success-100 dark:bg-success-900/30 rounded-xl">
                                <x-heroicon-s-table-cells class="h-6 w-6 text-success-600 dark:text-success-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Referral Activity</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Detailed list of referred users</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-filament::badge color="success" size="sm">
                                <div class="flex items-center gap-1">
                                    <div class="h-1.5 w-1.5 rounded-full bg-success-600 animate-pulse"></div>
                                    Live
                                </div>
                            </x-filament::badge>
                            <x-filament::badge color="gray" size="sm">{{ $stats['total_referrals'] }} Total</x-filament::badge>
                        </div>
                    </div>
                </x-slot>

                <div class="p-6">
                    <!-- Filter/Utility Bar (placeholder for future filters) -->
                    <div class="mb-8 p-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <div class="p-2 bg-gray-200 dark:bg-gray-600 rounded-lg">
                                    <x-heroicon-o-funnel class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Filters</span>
                            </div>
                            <div class="flex flex-wrap gap-3 flex-1">
                                <x-filament::button size="sm" color="success" outlined>Active</x-filament::button>
                                <x-filament::button size="sm" color="primary" outlined>Pending</x-filament::button>
                                <x-filament::button size="sm" color="warning" outlined>High Earners</x-filament::button>
                                <x-filament::button size="sm" color="info" outlined>This Month</x-filament::button>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-path">Refresh</x-filament::button>
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-adjustments-horizontal">Advanced</x-filament::button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="min-w-full">
                            {{ $this->table }}
                        </div>
                    </div>

                    <!-- Footer Mini Stats -->
                    <div class="mt-8 p-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl shadow-sm">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Available</p>
                                <p class="text-2xl font-bold text-success-600 dark:text-success-400">${{ number_format($stats['available_earnings'],2) }}</p>
                            </div>
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Pending</p>
                                <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">${{ number_format($stats['pending_earnings'],2) }}</p>
                            </div>
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Referrals</p>
                                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['total_referrals'] }}</p>
                            </div>
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Conversion</p>
                                <p class="text-2xl font-bold text-fuchsia-600 dark:text-fuchsia-400">{{ number_format($stats['conversion_rate'],1) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Help & Tips Section -->
        <div class="grid gap-6 lg:grid-cols-2 mt-12">
            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-success-100 dark:bg-success-900/30 rounded-lg">
                            <x-heroicon-o-information-circle class="h-5 w-5 text-success-600 dark:text-success-400" />
                        </div>
                        <span class="text-lg font-semibold">Referral Journey</span>
                    </div>
                </x-slot>
                <x-slot name="description">Understand the lifecycle of a referral</x-slot>
                <div class="space-y-4 p-4">
                    <div class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-primary-50 to-indigo-50 dark:from-primary-900/10 dark:to-indigo-900/10 border border-primary-200 dark:border-primary-800/30 p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-500 shadow-lg">
                                <x-heroicon-s-clipboard-document-list class="h-6 w-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">Invite Shared</span>
                                    <x-filament::badge color="primary" size="xs">Step 1</x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Prospect receives your referral link or code</p>
                            </div>
                        </div>
                    </div>
                    <div class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-warning-50 to-orange-50 dark:from-warning-900/10 dark:to-orange-900/10 border border-warning-200 dark:border-warning-800/30 p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning-500 shadow-lg">
                                <x-heroicon-s-user-plus class="h-6 w-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">Signup & Tracking</span>
                                    <x-filament::badge color="warning" size="xs">Step 2</x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">User signs up and is attributed to you</p>
                            </div>
                        </div>
                    </div>
                    <div class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-success-50 to-emerald-50 dark:from-success-900/10 dark:to-emerald-900/10 border border-success-200 dark:border-success-800/30 p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success-500 shadow-lg">
                                <x-heroicon-s-check-circle class="h-6 w-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">Activation</span>
                                    <x-filament::badge color="success" size="xs">Step 3</x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Referral becomes active and starts usage</p>
                            </div>
                        </div>
                    </div>
                    <div class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-fuchsia-50 to-pink-50 dark:from-fuchsia-900/10 dark:to-pink-900/10 border border-fuchsia-200 dark:border-fuchsia-800/30 p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-fuchsia-500 shadow-lg">
                                <x-heroicon-s-banknotes class="h-6 w-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">Commission Earned</span>
                                    <x-filament::badge color="fuchsia" size="xs">Step 4</x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Reward credited to your balance</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <x-heroicon-o-light-bulb class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <span class="text-lg font-semibold">Growth Tips</span>
                    </div>
                </x-slot>
                <x-slot name="description">Optimize your referral strategy</x-slot>
                <div class="space-y-6 p-4">
                    <div class="p-6 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 border border-indigo-200 dark:border-indigo-800/30">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><x-heroicon-o-megaphone class="w-5 h-5 text-indigo-600 dark:text-indigo-400" /> Promotion</h4>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li>• Share in relevant niche communities</li>
                            <li>• Offer genuine value explanation</li>
                            <li>• Re-engage inactive referrals</li>
                        </ul>
                    </div>
                    <div class="p-6 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/10 dark:to-teal-900/10 border border-emerald-200 dark:border-emerald-800/30">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><x-heroicon-o-chart-pie class="w-5 h-5 text-emerald-600 dark:text-emerald-400" /> Optimization</h4>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li>• Track performance weekly</li>
                            <li>• A/B test messaging styles</li>
                            <li>• Focus on high-quality referrals</li>
                        </ul>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                window.dispatchEvent(new CustomEvent('show-notification', { detail: { message: 'Copied to clipboard!', type: 'success' } }));
            });
        }
    </script>
    @endpush
</x-filament-panels::page>
