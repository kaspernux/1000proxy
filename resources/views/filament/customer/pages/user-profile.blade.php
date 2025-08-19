<x-filament-panels::page>
    <div class="fi-page-content space-y-8">
        <!-- Enhanced Profile Header -->
        <div class="bg-gradient-to-r from-primary-500 via-blue-600 to-purple-700 overflow-hidden shadow-2xl rounded-3xl relative">
            <!-- Decorative background pattern -->
            <div class="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
            <div class="absolute top-0 right-0 -mt-8 -mr-8 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-32 h-32 bg-primary-400/20 rounded-full blur-2xl"></div>
            
            <div class="relative px-6 py-10 sm:px-10 sm:py-16">
            <div class="flex flex-col md:flex-row md:items-center gap-8">
                <!-- Avatar Section -->
                <div class="flex-shrink-0">
                <div class="relative group">
                    @php
                    $avatar = auth()->guard('customer')->user()->avatar;
                    @endphp
                    @if($avatar && Storage::exists($avatar))
                    <img class="h-28 w-28 lg:h-36 lg:w-36 rounded-full object-cover ring-8 ring-white/30 shadow-2xl group-hover:scale-105 transition-transform duration-300 border-4 border-white/20" 
                         src="{{ Storage::url($avatar) }}" 
                         alt="Profile Photo">
                    @else
                    <div class="h-28 w-28 lg:h-36 lg:w-36 rounded-full bg-gradient-to-br from-primary-400 via-blue-400 to-purple-400 flex items-center justify-center ring-8 ring-white/30 shadow-2xl group-hover:scale-105 transition-transform duration-300 border-4 border-white/20">
                        <x-heroicon-o-user class="h-16 w-16 lg:h-20 lg:w-20 text-white/80" />
                    </div>
                    @endif
                    <div class="absolute -bottom-3 -right-3 bg-success-500 rounded-full p-2 shadow-lg border-2 border-white">
                    <x-heroicon-s-check class="w-5 h-5 text-white" />
                    </div>
                </div>
                </div>
                
                <!-- Profile Info -->
                <div class="flex-1 min-w-0">
                <h1 class="text-3xl lg:text-5xl font-extrabold text-white mb-2 drop-shadow-lg">
                    {{ auth()->guard('customer')->user()->name }}
                </h1>
                <p class="text-lg text-white/90 mb-4 flex items-center gap-2">
                    <x-heroicon-o-envelope class="w-5 h-5 text-white/70" />
                    {{ auth()->guard('customer')->user()->email }}
                </p>
                
                <!-- Status Badges -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-success-500/30 text-success-100 ring-2 ring-success-500/40 shadow">
                    <x-heroicon-s-check-circle class="w-4 h-4 mr-1.5" />
                    Verified
                    </span>
                    @if($this->twoFactorEnabled)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-500/30 text-blue-100 ring-2 ring-blue-500/40 shadow">
                        <x-heroicon-s-shield-check class="w-4 h-4 mr-1.5" />
                        2FA Enabled
                    </span>
                    @endif
                    @if(auth()->guard('customer')->user()->premium)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-500/30 text-yellow-100 ring-2 ring-yellow-500/40 shadow">
                        <x-heroicon-s-star class="w-4 h-4 mr-1.5" />
                        Premium
                    </span>
                    @endif
                </div>
                
                <!-- Member Since -->
                <p class="text-sm text-white/80 flex items-center gap-2">
                    <x-heroicon-o-calendar class="w-4 h-4 text-white/60" />
                    Member since {{ auth()->guard('customer')->user()->created_at->format('F j, Y') }}
                </p>
                </div>
            </div>
            </div>
        </div>

        <!-- Infolist Summary Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4">
                {{ $this->infolist }}
            </div>
        </div>

        <!-- Enhanced Account Statistics -->
        <div class="bg-white my-16 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="flex flex-col md:flex-row md:space-x-6 space-y-6 md:space-y-0 p-6 justify-center items-center">
            <!-- Total Orders -->
            <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300 flex flex-col items-center justify-center text-center min-w-[180px]">
                <div class="p-6 flex flex-col items-center justify-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $this->accountStats['total_orders'] ?? 0 }}
                </div>
                <div class="text-base text-gray-500 dark:text-gray-400 mt-2">Total Orders</div>
                </div>
            </div>

            <!-- Active Services -->
            <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300 flex flex-col items-center justify-center text-center min-w-[180px]">
                <div class="p-6 flex flex-col items-center justify-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $this->accountStats['active_services'] ?? 0 }}
                </div>
                <div class="text-base text-gray-500 dark:text-gray-400 mt-2">Active Services</div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300 flex flex-col items-center justify-center text-center min-w-[180px]">
                <div class="p-6 flex flex-col items-center justify-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($this->accountStats['total_spent'] ?? 0, 2) }}
                </div>
                <div class="text-base text-gray-500 dark:text-gray-400 mt-2">Total Spent</div>
                </div>
            </div>

            <!-- Wallet Balance -->
            <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-2xl hover:shadow-2xl transition-shadow duration-300 flex flex-col items-center justify-center text-center min-w-[180px]">
                <div class="p-6 flex flex-col items-center justify-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($this->accountStats['wallet_balance'] ?? 0, 2) }}
                </div>
                <div class="text-base text-gray-500 dark:text-gray-400 mt-2">Wallet Balance</div>
                </div>
            </div>
            </div>
        </div>

        <!-- Enhanced Forms Layout -->
        <div class="bg-white my-16  dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Profile Information -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-xl">
                            <x-heroicon-o-user class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Settings</h3>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    {{ $this->form }}
                </div>
            </div>
        </div>

        <!-- Account Activity -->
        <div class="bg-white my-16 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
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
        <div class="bg-white my-16 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
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
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">🔐 Two-Factor Authentication</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Enable extra security for your account (coming soon).</p>
                        </div>
                        <button wire:click="toggleTwoFactor"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50"
                                wire:loading.attr="disabled">
                            <x-heroicon-o-shield-check class="w-4 h-4 mr-2" />
                            <span>Manage 2FA</span>
                        </button>
                    </div>
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
