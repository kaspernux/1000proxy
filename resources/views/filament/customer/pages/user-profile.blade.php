<x-filament-panels::page>
    <div class="fi-page-content space-y-8">
        <!-- Enhanced Profile Header -->
        <div class="bg-gradient-to-r from-primary-500 via-blue-600 to-purple-700 overflow-hidden shadow-2xl rounded-3xl">
            <div class="relative px-6 py-8 sm:px-8 sm:py-12">
                <!-- Decorative background pattern -->
                <div class="absolute inset-0 bg-gradient-to-r from-white/5 to-transparent"></div>
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
                
                <div class="relative">
                    <div class="flex flex-col md:flex-row md:items-center gap-6">
                        <!-- Avatar Section -->
                        <div class="flex-shrink-0">
                            <div class="relative group">
                                @if(auth()->guard('customer')->user()->avatar)
                                    <img class="h-24 w-24 lg:h-32 lg:w-32 rounded-full object-cover ring-4 ring-white/20 shadow-2xl group-hover:scale-105 transition-transform duration-300" 
                                         src="{{ Storage::url(auth()->guard('customer')->user()->avatar) }}" 
                                         alt="Profile Photo">
                                @else
                                    <div class="h-24 w-24 lg:h-32 lg:w-32 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center ring-4 ring-white/20 shadow-2xl group-hover:scale-105 transition-transform duration-300">
                                        <x-heroicon-o-user class="h-12 w-12 lg:h-16 lg:w-16 text-white/80" />
                                    </div>
                                @endif
                                <div class="absolute -bottom-2 -right-2 bg-success-500 rounded-full p-2 shadow-lg">
                                    <x-heroicon-s-check class="w-4 h-4 text-white" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Info -->
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl lg:text-4xl font-bold text-white mb-2">
                                {{ auth()->guard('customer')->user()->name }}
                            </h1>
                            <p class="text-lg text-white/90 mb-4">{{ auth()->guard('customer')->user()->email }}</p>
                            
                            <!-- Status Badges -->
                            <div class="flex flex-wrap items-center gap-2 mb-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-success-500/20 text-success-100 ring-1 ring-success-500/30">
                                    <x-heroicon-s-check-circle class="w-4 h-4 mr-1.5" />
                                    Verified Account
                                </span>
                                @if($this->twoFactorEnabled)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-500/20 text-blue-100 ring-1 ring-blue-500/30">
                                        <x-heroicon-s-shield-check class="w-4 h-4 mr-1.5" />
                                        2FA Protected
                                    </span>
                                @endif
                                @if(auth()->guard('customer')->user()->premium)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-500/20 text-yellow-100 ring-1 ring-yellow-500/30">
                                        <x-heroicon-s-star class="w-4 h-4 mr-1.5" />
                                        Premium
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Member Since -->
                            <p class="text-sm text-white/70">
                                Member since {{ auth()->guard('customer')->user()->created_at->format('F j, Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Account Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Orders -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                                <x-heroicon-o-shopping-bag class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $this->accountStats['total_orders'] ?? 0 }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Orders</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Services -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-r from-success-500 to-success-600 rounded-xl flex items-center justify-center shadow-lg">
                                <x-heroicon-o-server class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $this->accountStats['active_services'] ?? 0 }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Active Services</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                <x-heroicon-o-currency-dollar class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                ${{ number_format($this->accountStats['total_spent'] ?? 0, 2) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Spent</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet Balance -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                                <x-heroicon-o-wallet class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                ${{ number_format($this->accountStats['wallet_balance'] ?? 0, 2) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Wallet Balance</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Forms Layout -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <!-- Profile Information -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                            <x-heroicon-o-user class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Update your personal details and preferences</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    {{ $this->form }}
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-warning-50 to-orange-50 dark:from-warning-900/20 dark:to-orange-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-warning-100 dark:bg-warning-900/30 rounded-xl">
                            <x-heroicon-o-bell class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notification Settings</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Manage how you receive updates</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    {{ $this->notificationForm }}
                </div>
            </div>
        <!-- Security & Password Section -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <!-- Security Settings -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-success-50 to-emerald-50 dark:from-success-900/20 dark:to-emerald-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-success-100 dark:bg-success-900/30 rounded-xl">
                            <x-heroicon-o-shield-check class="w-6 h-6 text-success-600 dark:text-success-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Security Overview</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Monitor your account security status</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    {{ $this->securityForm }}
                    
                    <!-- Enhanced Security Actions -->
                    <div class="mt-6 space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 {{ $this->twoFactorEnabled ? 'bg-success-100 dark:bg-success-900/30' : 'bg-warning-100 dark:bg-warning-900/30' }} rounded-lg">
                                        <x-heroicon-o-shield-check class="w-5 h-5 {{ $this->twoFactorEnabled ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">Two-Factor Authentication</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $this->twoFactorEnabled ? 'Your account is protected with 2FA' : 'Enable 2FA for enhanced security' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($this->twoFactorEnabled)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300">
                                            <x-heroicon-s-check-circle class="w-3 h-3 mr-1" />
                                            Enabled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300">
                                            <x-heroicon-s-exclamation-triangle class="w-3 h-3 mr-1" />
                                            Disabled
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-info-100 dark:bg-info-900/30 rounded-lg">
                                    <x-heroicon-o-clock class="w-5 h-5 text-info-600 dark:text-info-400" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Last Login</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $this->accountStats['last_login'] ?? 'Never' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Change -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-danger-50 to-red-50 dark:from-danger-900/20 dark:to-red-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-xl">
                            <x-heroicon-o-key class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Update your account password for better security</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    {{ $this->passwordForm }}
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-xl">
                            <x-heroicon-o-bell class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Notification Settings</h3>
                            <p class="text-sm text-purple-100">Control how you receive updates</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    {{ $this->notificationForm }}
                </div>
            </div>

            <!-- Security Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-pink-600 p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-xl">
                            <x-heroicon-o-shield-check class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Security Settings</h3>
                            <p class="text-sm text-red-100">Advanced security options</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    {{ $this->securityForm }}
                </div>
            </div>
        </div>

        <!-- Account Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-xl">
                        <x-heroicon-o-clock class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Recent Activity</h3>
                        <p class="text-sm text-indigo-100">Your account activity timeline</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="flow-root">
                    <ul class="-mb-8">
                        <li>
                            <div class="relative pb-8">
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"></span>
                                <div class="relative flex space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-blue-500 dark:bg-blue-600 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <x-heroicon-o-user class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Profile accessed</p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ now()->format('M j, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        @php
                            $lastOrder = \Illuminate\Support\Facades\DB::table('orders')
                                ->where('customer_id', auth()->guard('customer')->id())
                                ->orderBy('created_at', 'desc')
                                ->first();
                        @endphp

                        @if($lastOrder)
                        <li>
                            <div class="relative pb-8">
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"></span>
                                <div class="relative flex space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-green-500 dark:bg-green-600 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <x-heroicon-o-shopping-bag class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Order #{{ $lastOrder->id }} placed</p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($lastOrder->created_at)->format('M j, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endif

                        <li>
                            <div class="relative">
                                <div class="relative flex space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-gray-400 dark:bg-gray-600 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <x-heroicon-o-user-plus class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Account created</p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ auth()->guard('customer')->user()->created_at->format('M j, Y') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Data & Privacy -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-500 to-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-xl">
                        <x-heroicon-o-document-text class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Data & Privacy</h3>
                        <p class="text-sm text-gray-100">Manage your data and privacy settings</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">📦 Download your data</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Get a copy of all your account data and activity.</p>
                        </div>
                        <button wire:click="downloadData" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                                wire:loading.attr="disabled">
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                            <span wire:loading.remove>Download</span>
                            <span wire:loading>Preparing...</span>
                        </button>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
                        <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <div>
                                <h4 class="text-sm font-medium text-red-900 dark:text-red-300">⚠️ Delete account</h4>
                                <p class="text-sm text-red-700 dark:text-red-400">Permanently delete your account and all associated data.</p>
                            </div>
                            <button wire:click="deleteAccount"
                                    onclick="return confirm('⚠️ Are you absolutely sure?\n\nThis will permanently delete your account and all data. This action cannot be undone!')"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                                    wire:loading.attr="disabled">
                                <x-heroicon-o-trash class="w-4 h-4 mr-2" />
                                <span wire:loading.remove>Delete Account</span>
                                <span wire:loading>Processing...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading.flex class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm mx-4">
            <div class="flex items-center gap-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">Processing...</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Please wait while we save your changes</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
