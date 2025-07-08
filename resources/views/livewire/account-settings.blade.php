<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Account Settings</h1>
        <p class="text-gray-600">Manage your account preferences and integrations</p>
    </div>
    
    <div class="grid grid-cols-1 gap-6">
        <!-- Account Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Account Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $user->name }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $user->username }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $user->email }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <div class="mt-1 text-sm text-gray-900">{{ ucfirst($user->role) }}</div>
                </div>
            </div>
        </div>
        
        <!-- Telegram Integration -->
        <livewire:auth.telegram-link />
        
        <!-- Wallet Information -->
        @if($user->wallet)
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Wallet Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Balance</label>
                    <div class="mt-1 text-lg font-semibold text-green-600">${{ number_format($user->wallet->balance, 2) }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $user->wallet->updated_at->format('M d, Y H:i') }}</div>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Security Settings -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Security</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Login</label>
                    <div class="mt-1 text-sm text-gray-900">
                        @if($user->last_login_at)
                            {{ $user->last_login_at->format('M d, Y H:i') }}
                        @else
                            Never
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Account Status</label>
                    <div class="mt-1">
                        @if($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/servers" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Browse Servers
                </a>
                <a href="/my-orders" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    My Orders
                </a>
                <a href="/cart" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    View Cart
                </a>
            </div>
        </div>
    </div>
</div>
