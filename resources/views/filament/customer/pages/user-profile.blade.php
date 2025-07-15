<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Profile Header -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-20 w-20 rounded-full bg-gray-300 flex items-center justify-center">
                            @if(auth()->guard('customer')->user()->avatar)
                                <img class="h-20 w-20 rounded-full" src="{{ Storage::url(auth()->guard('customer')->user()->avatar) }}" alt="Profile">
                            @else
                                <svg class="h-12 w-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            @endif
                        </div>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ auth()->guard('customer')->user()->name }}</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ auth()->guard('customer')->user()->email }}</p>
                        <div class="mt-2 flex items-center space-x-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-2 h-2 mr-1.5" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Active Account
                            </span>
                            @if($this->twoFactorEnabled)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <x-heroicon-o-shield-check class="w-3 h-3 mr-1" />
                                    2FA Enabled
                                </span>
                            @endif
                            <span class="text-xs text-gray-500">
                                Member since {{ auth()->guard('customer')->user()->created_at->format('M Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-shopping-bag class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Orders</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ \Illuminate\Support\Facades\DB::table('orders')->where('customer_id', auth()->guard('customer')->id())->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-server class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Services</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ \Illuminate\Support\Facades\DB::table('server_clients')->where('customer_id', auth()->guard('customer')->id())->where('enable', true)->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-currency-dollar class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Spent</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    ${{ number_format(\Illuminate\Support\Facades\DB::table('orders')->where('customer_id', auth()->guard('customer')->id())->sum('total_amount'), 2) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <x-heroicon-o-wallet class="w-5 h-5 text-white" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Wallet Balance</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    ${{ number_format(\Illuminate\Support\Facades\DB::table('wallets')->where('customer_id', auth()->guard('customer')->id())->value('balance') ?? 0, 2) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Profile Information</h3>
                {{ $this->form }}
            </div>
        </div>

        <!-- Password Change Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Security Settings</h3>
                {{ $this->passwordForm }}

                <!-- Two-Factor Authentication Section -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-md font-medium text-gray-900">Two-Factor Authentication</h4>
                            <p class="text-sm text-gray-500 mt-1">
                                @if($this->twoFactorEnabled)
                                    Two-factor authentication is currently enabled for your account.
                                @else
                                    Add an extra layer of security to your account.
                                @endif
                            </p>
                        </div>
                        <div>
                            @if($this->twoFactorEnabled)
                                <span class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-green-800 bg-green-100">
                                    <x-heroicon-o-shield-check class="w-4 h-4 mr-2" />
                                    Enabled
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white">
                                    <x-heroicon-o-shield-exclamation class="w-4 h-4 mr-2" />
                                    Disabled
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Activity -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activity</h3>
                <div class="flow-root">
                    <ul class="-mb-8">
                        <li>
                            <div class="relative pb-8">
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                <div class="relative flex space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                        <x-heroicon-o-user class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Profile accessed</p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
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
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                <div class="relative flex space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                        <x-heroicon-o-shopping-bag class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Order #{{ $lastOrder->id }} placed</p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
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
                                    <div class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white">
                                        <x-heroicon-o-user-plus class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Account created</p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
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
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Data & Privacy</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Download your data</h4>
                            <p class="text-sm text-gray-500">Get a copy of all your account data and activity.</p>
                        </div>
                        <button wire:click="downloadData" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                            Download
                        </button>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Delete account</h4>
                                <p class="text-sm text-gray-500">Permanently delete your account and all associated data.</p>
                            </div>
                            <button onclick="confirm('Are you sure you want to delete your account? This action cannot be undone.') && alert('Account deletion would be processed here.')"
                                    class="inline-flex items-center px-3 py-2 border border-red-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100">
                                <x-heroicon-o-trash class="w-4 h-4 mr-2" />
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
