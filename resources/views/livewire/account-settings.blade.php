<main class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 py-8 px-2 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>
    <header class="w-full max-w-7xl mx-auto mb-8 relative z-10">
        <section class="flex flex-col gap-6 bg-white/10 dark:bg-gray-900/40 backdrop-blur-md rounded-2xl shadow-2xl p-4 sm:p-6 lg:p-10 border border-white/20">
            <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                <!-- Avatar Section -->
                <div class="relative group">
                    @if($current_avatar)
                        <img class="h-20 w-20 sm:h-24 sm:w-24 rounded-full object-cover ring-4 ring-blue-400 shadow-2xl group-hover:scale-105 transition-transform duration-300" src="{{ Storage::url($current_avatar) }}" alt="{{ $name }}">
                    @else
                        <div class="h-20 w-20 sm:h-24 sm:w-24 rounded-full bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center ring-4 ring-blue-400 shadow-2xl group-hover:scale-105 transition-transform duration-300">
                            <span class="text-xl sm:text-2xl font-bold text-white">{{ substr($name, 0, 2) }}</span>
                        </div>
                    @endif
                    <button wire:click="$set('activeTab', 'profile')" class="absolute -bottom-2 -right-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full p-2 hover:from-purple-600 hover:to-blue-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-110">
                        <x-heroicon-s-user class="w-4 h-4" />
                    </button>
                </div>
                <!-- User Info -->
                <div class="flex flex-col gap-1 text-center sm:text-left">
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">{{ $name }}</h1>
                    <p class="text-sm sm:text-base lg:text-lg text-gray-300">{{ $email }}</p>
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mt-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100/20 text-green-300 border border-green-400/30">
                            <x-heroicon-s-check-circle class="w-4 h-4 mr-1.5" />
                            Verified Account
                        </span>
                        @if($customer->premium)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100/20 text-yellow-300 border border-yellow-400/30">
                                <x-heroicon-s-star class="w-4 h-4 mr-1.5" />
                                Premium Member
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Account Statistics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 w-full">
                <div class="bg-blue-500/20 backdrop-blur-md rounded-xl p-3 sm:p-4 text-center shadow-lg border border-blue-400/30 hover:border-blue-300/50 transition-all duration-300 hover:shadow-xl hover:scale-105">
                    <div class="text-lg sm:text-2xl font-bold text-blue-300">{{ $accountStats['total_orders'] }}</div>
                    <div class="text-xs text-blue-200">Total Orders</div>
                </div>
                <div class="bg-green-500/20 backdrop-blur-md rounded-xl p-3 sm:p-4 text-center shadow-lg border border-green-400/30 hover:border-green-300/50 transition-all duration-300 hover:shadow-xl hover:scale-105">
                    <div class="text-lg sm:text-2xl font-bold text-green-300">${{ number_format($accountStats['total_spent'], 2) }}</div>
                    <div class="text-xs text-green-200">Total Spent</div>
                </div>
                <div class="bg-purple-500/20 backdrop-blur-md rounded-xl p-3 sm:p-4 text-center shadow-lg border border-purple-400/30 hover:border-purple-300/50 transition-all duration-300 hover:shadow-xl hover:scale-105">
                    <div class="text-sm sm:text-lg lg:text-xl font-bold text-purple-300">{{ $this->accountStats['account_age_days'] }}</div>
                    <div class="text-xs text-purple-200">Time with Us</div>
                </div>
                <div class="bg-orange-500/20 backdrop-blur-md rounded-xl p-3 sm:p-4 text-center shadow-lg border border-orange-400/30 hover:border-orange-300/50 transition-all duration-300 hover:shadow-xl hover:scale-105">
                    <div class="text-sm sm:text-lg lg:text-xl font-bold text-orange-300">
                        @if($accountStats['last_order'])
                            {{ $accountStats['last_order']->created_at->diffForHumans() }}
                        @else
                            Never
                        @endif
                    </div>
                    <div class="text-xs text-orange-200">Last Order</div>
                </div>
            </div>
        </section>
    </header>
    <section class="max-w-7xl mx-auto pb-12">
        <!-- Tab Navigation -->
        <nav class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl mb-8 border border-white/20 overflow-hidden">
            <div class="border-b border-white/20">
                <div class="flex flex-wrap gap-1 px-2 sm:px-4 py-2 overflow-x-auto">
                    <button wire:click="setActiveTab('profile')"
                            class="py-2 sm:py-3 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm transition-colors flex items-center gap-1 sm:gap-2 flex-shrink-0 {{ $activeTab === 'profile' ? 'border-blue-400 text-blue-300 bg-blue-500/20' : 'border-transparent text-gray-300 hover:text-white hover:border-blue-400/50 hover:bg-white/5' }}">
                        <x-heroicon-s-user class="w-4 h-4 sm:w-5 sm:h-5" />
                        <span class="hidden sm:inline">Profile</span>
                    </button>
                    <button wire:click="setActiveTab('security')"
                            class="py-2 sm:py-3 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm transition-colors flex items-center gap-1 sm:gap-2 flex-shrink-0 {{ $activeTab === 'security' ? 'border-green-400 text-green-300 bg-green-500/20' : 'border-transparent text-gray-300 hover:text-white hover:border-green-400/50 hover:bg-white/5' }}">
                        <x-heroicon-s-lock-closed class="w-4 h-4 sm:w-5 sm:h-5" />
                        <span class="hidden sm:inline">Security</span>
                    </button>
                    <button wire:click="setActiveTab('addresses')"
                            class="py-2 sm:py-3 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm transition-colors flex items-center gap-1 sm:gap-2 flex-shrink-0 {{ $activeTab === 'addresses' ? 'border-purple-400 text-purple-300 bg-purple-500/20' : 'border-transparent text-gray-300 hover:text-white hover:border-purple-400/50 hover:bg-white/5' }}">
                        <x-heroicon-s-map-pin class="w-4 h-4 sm:w-5 sm:h-5" />
                        <span class="hidden sm:inline">Addresses</span>
                    </button>
                    <button wire:click="setActiveTab('notifications')"
                            class="py-2 sm:py-3 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm transition-colors flex items-center gap-1 sm:gap-2 flex-shrink-0 {{ $activeTab === 'notifications' ? 'border-yellow-400 text-yellow-300 bg-yellow-500/20' : 'border-transparent text-gray-300 hover:text-white hover:border-yellow-400/50 hover:bg-white/5' }}">
                        <x-heroicon-s-bell class="w-4 h-4 sm:w-5 sm:h-5" />
                        <span class="hidden sm:inline">Notifications</span>
                    </button>
                    <button wire:click="setActiveTab('privacy')"
                            class="py-2 sm:py-3 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm transition-colors flex items-center gap-1 sm:gap-2 flex-shrink-0 {{ $activeTab === 'privacy' ? 'border-red-400 text-red-300 bg-red-500/20' : 'border-transparent text-gray-300 hover:text-white hover:border-red-400/50 hover:bg-white/5' }}">
                        <x-heroicon-s-shield-check class="w-4 h-4 sm:w-5 sm:h-5" />
                        <span class="hidden sm:inline">Privacy</span>
                    </button>
                </div>
            </div>
        </nav>
        <div class="space-y-8">
            <!-- Profile Tab -->
            @if($activeTab === 'profile')
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl border border-white/20">
                    <div class="p-4 sm:p-6 border-b border-white/20">
                        <h3 class="text-lg font-medium text-white">Profile Information</h3>
                        <p class="mt-1 text-sm text-gray-300">Update your personal information and avatar.</p>
                    </div>

                    <form wire:submit.prevent="updateProfile" class="p-4 sm:p-6 space-y-6">
                        <!-- Avatar Upload -->
                        <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
                            <div class="shrink-0">
                                @if($current_avatar)
                                    <img class="h-20 w-20 rounded-full object-cover ring-4 ring-blue-400/50 shadow-xl" src="{{ Storage::url($current_avatar) }}" alt="{{ $name }}">
                                @else
                                    <div class="h-20 w-20 rounded-full bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center ring-4 ring-blue-400/50 shadow-xl">
                                        <span class="text-xl font-bold text-white">{{ substr($name, 0, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 w-full text-center sm:text-left">
                                <label class="block text-sm font-medium text-white mb-2">Profile Photo</label>
                                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                                    <input type="file" wire:model="avatar" accept="image/*" class="block w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600/20 file:text-blue-300 hover:file:bg-blue-500/30 file:backdrop-blur-sm">
                                    @if($current_avatar)
                                        <button type="button" wire:click="removeAvatar" class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors whitespace-nowrap">Remove</button>
                                    @endif
                                </div>
                                @if($avatar)
                                    <button type="button" wire:click="updateAvatar" class="mt-2 bg-blue-600/80 backdrop-blur-sm text-white px-4 py-2 rounded-md text-sm hover:bg-blue-500/80 transition-all border border-blue-400/30 w-full sm:w-auto">
                                        Upload New Avatar
                                    </button>
                                @endif
                                @error('avatar') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label for="account_name" class="block text-sm font-medium text-white mb-2">Full Name</label>
                                <input type="text" id="account_name" name="name" wire:model="name" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                                @error('name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="account_email" class="block text-sm font-medium text-white mb-2">Email Address</label>
                                <input type="email" id="account_email" name="email" wire:model="email" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                                @error('email') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="account_phone" class="block text-sm font-medium text-white mb-2">Phone Number</label>
                                <input type="tel" id="account_phone" name="phone" wire:model="phone" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                                @error('phone') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="account_date_of_birth" class="block text-sm font-medium text-white mb-2">Date of Birth</label>
                                <input type="date" id="account_date_of_birth" name="date_of_birth" wire:model="date_of_birth" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                                @error('date_of_birth') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="account_company" class="block text-sm font-medium text-white mb-2">Company</label>
                                <input type="text" id="account_company" name="company" wire:model="company" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                                @error('company') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="account_website" class="block text-sm font-medium text-white mb-2">Website</label>
                                <input type="url" id="account_website" name="website" wire:model="website" placeholder="https://" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                                @error('website') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="account_bio" class="block text-sm font-medium text-white mb-2">Bio</label>
                            <textarea id="account_bio" name="bio" wire:model="bio" rows="3" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all" placeholder="Tell us about yourself..."></textarea>
                            @error('bio') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white px-6 py-2 rounded-md transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 border border-blue-400/30 w-full sm:w-auto">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Change Section -->
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl border border-white/20">
                    <div class="p-4 sm:p-6 border-b border-white/20">
                        <h3 class="text-lg font-medium text-white">Change Password</h3>
                        <p class="mt-1 text-sm text-gray-300">Ensure your account is using a long, random password to stay secure.</p>
                    </div>

                    <form wire:submit.prevent="changePassword" class="p-4 sm:p-6 space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-white mb-2">Current Password</label>
                            <input type="password" id="current_password" wire:model="current_password" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                            @error('current_password') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-white mb-2">New Password</label>
                            <input type="password" id="new_password" wire:model="new_password" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                            @error('new_password') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="new_password_confirmation" class="block text-sm font-medium text-white mb-2">Confirm New Password</label>
                            <input type="password" id="new_password_confirmation" wire:model="new_password_confirmation" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50 sm:text-sm transition-all">
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white px-6 py-2 rounded-md transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 border border-green-400/30 w-full sm:w-auto">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Security Tab -->
            @if($activeTab === 'security')
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl border border-white/20">
                    <div class="p-6 border-b border-white/20">
                        <h3 class="text-lg font-medium text-white">Security Settings</h3>
                        <p class="mt-1 text-sm text-gray-300">Manage your account security and authentication preferences.</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Two-Factor Authentication -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Two-Factor Authentication</h4>
                                <p class="text-sm text-gray-300">Add an extra layer of security to your account</p>
                            </div>
                            <div class="flex items-center">
                                @if($two_factor_enabled)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-300 border border-green-400/30 mr-3">
                                        Enabled
                                    </span>
                                    <button class="bg-red-600/80 backdrop-blur-sm text-white px-4 py-2 rounded-md text-sm hover:bg-red-500/80 transition-all border border-red-400/30">
                                        Disable 2FA
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/20 text-red-300 border border-red-400/30 mr-3">
                                        Disabled
                                    </span>
                                    <button class="bg-blue-600/80 backdrop-blur-sm text-white px-4 py-2 rounded-md text-sm hover:bg-blue-500/80 transition-all border border-blue-400/30">
                                        Enable 2FA
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Login Alerts -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-white">Login Alerts</h4>
                                <p class="text-sm text-gray-300">Get notified when someone logs into your account</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="login_alerts" wire:change="updateSecuritySettings" class="sr-only peer">
                                <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Account Activity -->
                        <div>
                            <h4 class="text-sm font-medium text-white mb-4">Recent Account Activity</h4>
                            <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-white">Last login</p>
                                            <p class="text-sm text-gray-300">
                                                @if($customer->last_login_at)
                                                    {{ $customer->last_login_at->format('M d, Y H:i') }}
                                                @else
                                                    Never
                                                @endif
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-300 border border-green-400/30">
                                            Successful
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Addresses Tab -->
            @if($activeTab === 'addresses')
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl border border-white/20">
                    <div class="p-4 sm:p-6 border-b border-white/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-white">Saved Addresses</h3>
                            <p class="mt-1 text-sm text-gray-300">Manage your billing and shipping addresses.</p>
                        </div>
                        <button wire:click="addAddress" class="bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-white px-4 py-2 rounded-md text-sm transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 border border-purple-400/30 w-full sm:w-auto">
                            Add New Address
                        </button>
                    </div>

                    <div class="p-4 sm:p-6">
                        @if(count($addresses) > 0)
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                                @foreach($addresses as $address)
                                    <div class="bg-white/5 backdrop-blur-sm border border-white/20 rounded-lg p-4 {{ $address['is_default'] ? 'ring-2 ring-purple-400/50' : '' }} hover:bg-white/10 transition-all">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center mb-2 gap-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $address['type'] === 'billing' ? 'bg-blue-500/20 text-blue-300 border border-blue-400/30' : 'bg-green-500/20 text-green-300 border border-green-400/30' }}">
                                                        {{ ucfirst($address['type']) }}
                                                    </span>
                                                    @if($address['is_default'])
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-300 border border-yellow-400/30">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>
                                                <h4 class="font-medium text-white">{{ $address['first_name'] }} {{ $address['last_name'] }}</h4>
                                                @if($address['company'])
                                                    <p class="text-sm text-gray-300">{{ $address['company'] }}</p>
                                                @endif
                                                <p class="text-sm text-gray-300">{{ $address['address_line_1'] }}</p>
                                                @if($address['address_line_2'])
                                                    <p class="text-sm text-gray-300">{{ $address['address_line_2'] }}</p>
                                                @endif
                                                <p class="text-sm text-gray-300">{{ $address['city'] }}, {{ $address['state'] }} {{ $address['postal_code'] }}</p>
                                                <p class="text-sm text-gray-300">{{ $address['country'] }}</p>
                                                @if($address['phone'])
                                                    <p class="text-sm text-gray-300">{{ $address['phone'] }}</p>
                                                @endif
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <button class="text-gray-400 hover:text-white transition-colors">
                                                    <x-heroicon-s-ellipsis-vertical class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <button wire:click="editAddress({{ $address['id'] }})" class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors">
                                                Edit
                                            </button>
                                            @if(!$address['is_default'])
                                                <button wire:click="setDefaultAddress({{ $address['id'] }})" class="text-green-400 hover:text-green-300 text-sm font-medium transition-colors">
                                                    Set as Default
                                                </button>
                                            @endif
                                            <button wire:click="deleteAddress({{ $address['id'] }})" class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <x-heroicon-o-map-pin class="w-16 h-16 mx-auto text-gray-400" />
                                <h3 class="mt-4 text-lg font-medium text-white">No addresses saved</h3>
                                <p class="mt-2 text-gray-300">Add your first address to make checkout faster.</p>
                                <button wire:click="addAddress" class="mt-4 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-white px-4 py-2 rounded-md text-sm transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 border border-purple-400/30">
                                    Add Address
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Notifications Tab -->
            @if($activeTab === 'notifications')
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl border border-white/20">
                    <div class="p-4 sm:p-6 border-b border-white/20">
                        <h3 class="text-lg font-medium text-white">Notification Preferences</h3>
                        <p class="mt-1 text-sm text-gray-300">Choose how you want to be notified about account activity.</p>
                    </div>

                    <form wire:submit.prevent="updateNotificationPreferences" class="p-4 sm:p-6 space-y-6">
                        <!-- Email Notifications -->
                        <div>
                            <h4 class="text-lg font-medium text-white mb-4">Email Notifications</h4>
                            <div class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Order Updates</label>
                                        <p class="text-sm text-gray-300">Get notified about order status changes</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="email_notifications.order_updates" class="sr-only peer">
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                                    </label>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Promotional Emails</label>
                                        <p class="text-sm text-gray-300">Receive offers and product announcements</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="email_notifications.promotional" class="sr-only peer">
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                                    </label>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Security Alerts</label>
                                        <p class="text-sm text-gray-300">Important account security notifications</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="email_notifications.security" class="sr-only peer">
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Notifications -->
                        <div>
                            <h4 class="text-lg font-medium text-white mb-4">SMS Notifications</h4>
                            <div class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Order Updates</label>
                                        <p class="text-sm text-gray-300">Get SMS updates for urgent order changes</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="sms_notifications.order_updates" class="sr-only peer">
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                                    </label>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Security Alerts</label>
                                        <p class="text-sm text-gray-300">Critical security notifications via SMS</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="sms_notifications.security" class="sr-only peer">
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-white px-6 py-2 rounded-md transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 border border-yellow-400/30 w-full sm:w-auto">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Privacy Tab -->
            @if($activeTab === 'privacy')
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl border border-white/20">
                    <div class="p-4 sm:p-6 border-b border-white/20">
                        <h3 class="text-lg font-medium text-white">Privacy & Data</h3>
                        <p class="mt-1 text-sm text-gray-300">Control your privacy settings and manage your data.</p>
                    </div>

                    <div class="p-4 sm:p-6 space-y-6">
                        <!-- Privacy Settings -->
                        <form wire:submit.prevent="updatePrivacySettings">
                            <div class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Profile Visibility</label>
                                        <p class="text-sm text-gray-300">Control who can see your profile information</p>
                                    </div>
                                    <select wire:model="privacy_settings.profile_visibility" class="rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white text-sm focus:border-red-400 focus:ring-2 focus:ring-red-400/50 transition-all w-full sm:w-auto">
                                        <option value="public" class="bg-gray-800 text-white">Public</option>
                                        <option value="private" class="bg-gray-800 text-white">Private</option>
                                        <option value="friends" class="bg-gray-800 text-white">Friends Only</option>
                                    </select>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Show Email Address</label>
                                        <p class="text-sm text-gray-300">Allow others to see your email</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="privacy_settings.show_email" class="sr-only peer">
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                    </label>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-white">Data Processing</label>
                                        <p class="text-sm text-gray-300">Allow us to process your data for service improvement</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="privacy_settings.data_processing" class="sr-only peer">
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" class="bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white px-6 py-2 rounded-md transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 border border-red-400/30 w-full sm:w-auto">
                                    Save Privacy Settings
                                </button>
                            </div>
                        </form>

                        <!-- Data Management -->
                        <div class="border-t border-white/20 pt-6">
                            <h4 class="text-lg font-medium text-white mb-4">Data Management</h4>
                            <div class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-white">Download Your Data</p>
                                        <p class="text-sm text-gray-300">Get a copy of all your data</p>
                                    </div>
                                    <button wire:click="downloadDataExport" class="bg-blue-600/80 backdrop-blur-sm text-white px-4 py-2 rounded-md text-sm hover:bg-blue-500/80 transition-all border border-blue-400/30 w-full sm:w-auto">
                                        Request Export
                                    </button>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-white">Delete Account</p>
                                        <p class="text-sm text-gray-300">Permanently delete your account and all data</p>
                                    </div>
                                    <button wire:click="deleteAccount" class="bg-red-600/80 backdrop-blur-sm text-white px-4 py-2 rounded-md text-sm hover:bg-red-500/80 transition-all border border-red-400/30 w-full sm:w-auto">
                                        Delete Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Address Modal -->
        @if($showAddressModal)
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto border border-white/20">
                    <div class="p-4 sm:p-6 border-b border-white/20">
                        <h3 class="text-lg font-medium text-white">
                            {{ $editingAddress ? 'Edit Address' : 'Add New Address' }}
                        </h3>
                    </div>

                    <form wire:submit.prevent="saveAddress" class="p-4 sm:p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-white mb-2">Address Type</label>
                            <select wire:model="newAddress.type" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                                <option value="billing" class="bg-gray-800 text-white">Billing</option>
                                <option value="shipping" class="bg-gray-800 text-white">Shipping</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">First Name</label>
                                <input type="text" wire:model="newAddress.first_name" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                                @error('newAddress.first_name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">Last Name</label>
                                <input type="text" wire:model="newAddress.last_name" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                                @error('newAddress.last_name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-white mb-2">Company (Optional)</label>
                            <input type="text" wire:model="newAddress.company" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-white mb-2">Address Line 1</label>
                            <input type="text" wire:model="newAddress.address_line_1" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                            @error('newAddress.address_line_1') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-white mb-2">Address Line 2 (Optional)</label>
                            <input type="text" wire:model="newAddress.address_line_2" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">City</label>
                                <input type="text" wire:model="newAddress.city" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                                @error('newAddress.city') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">State/Province</label>
                                <input type="text" wire:model="newAddress.state" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                                @error('newAddress.state') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">Postal Code</label>
                                <input type="text" wire:model="newAddress.postal_code" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                                @error('newAddress.postal_code') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-2">Country</label>
                                <input type="text" wire:model="newAddress.country" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                                @error('newAddress.country') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-white mb-2">Phone (Optional)</label>
                            <input type="tel" wire:model="newAddress.phone" class="mt-1 block w-full rounded-md bg-white/10 border border-white/20 backdrop-blur-sm text-white placeholder-gray-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-400/50 sm:text-sm transition-all">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="newAddress.is_default" class="h-4 w-4 text-purple-600 focus:ring-purple-400 border-white/20 rounded bg-white/10">
                            <label class="ml-2 block text-sm text-white">Set as default address</label>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end gap-4 pt-4">
                            <button type="button" wire:click="$set('showAddressModal', false)" class="bg-white/10 backdrop-blur-sm text-gray-300 px-4 py-2 rounded-md text-sm hover:bg-white/20 transition-all border border-white/20 w-full sm:w-auto order-2 sm:order-1">
                                Cancel
                            </button>
                            <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-white px-4 py-2 rounded-md text-sm transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 border border-purple-400/30 w-full sm:w-auto order-1 sm:order-2">
                                {{ $editingAddress ? 'Update Address' : 'Save Address' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </section>
</main>


