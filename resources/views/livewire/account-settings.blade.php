@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50/50 backdrop-blur-sm">
    <!-- Account Header with Statistics -->
    <div class="bg-white border-b border-gray-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center space-x-6">
                    <!-- Avatar Section -->
                    <div class="relative">
                        @if($current_avatar)
                            <img class="h-24 w-24 rounded-full object-cover ring-4 ring-white shadow-lg"
                                 src="{{ Storage::url($current_avatar) }}"
                                 alt="{{ $name }}">
                        @else
                            <div class="h-24 w-24 rounded-full bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center ring-4 ring-white shadow-lg">
                                <span class="text-2xl font-bold text-white">{{ substr($name, 0, 2) }}</span>
                            </div>
                        @endif
                        <button wire:click="$set('activeTab', 'profile')"
                                class="absolute -bottom-2 -right-2 bg-blue-600 text-white rounded-full p-2 hover:bg-blue-700 transition-colors shadow-lg">
                            <x-custom-icon name="user" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- User Info -->
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $name }}</h1>
                        <p class="text-lg text-gray-600">{{ $email }}</p>
                        <div class="flex items-center mt-2 space-x-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Verified Account
                            </span>
                            @if($user->premium)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    Premium Member
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="mt-6 lg:mt-0 grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $accountStats['total_orders'] }}</div>
                        <div class="text-sm text-blue-700">Total Orders</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">${{ number_format($accountStats['total_spent'], 0) }}</div>
                        <div class="text-sm text-green-700">Total Spent</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ $accountStats['account_age_days'] }}</div>
                        <div class="text-sm text-purple-700">Days with Us</div>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-orange-600">
                            @if($accountStats['last_order'])
                                {{ $accountStats['last_order']->created_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </div>
                        <div class="text-sm text-orange-700">Last Order</div>
                    </div>
                </div>
            </div>
        <!-- Tab Content -->
        <div class="space-y-8">
            <!-- Profile Tab -->
            @if($activeTab === 'profile')
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                        <p class="mt-1 text-sm text-gray-600">Update your personal information and avatar.</p>
                    </div>

                    <form wire:submit.prevent="updateProfile" class="p-6 space-y-6">
                        <!-- Avatar Upload -->
                        <div class="flex items-center space-x-6">
                            <div class="shrink-0">
                                @if($current_avatar)
                                    <img class="h-20 w-20 rounded-full object-cover" src="{{ Storage::url($current_avatar) }}" alt="{{ $name }}">
                                @else
                                    <div class="h-20 w-20 rounded-full bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center">
                                        <span class="text-xl font-bold text-white">{{ substr($name, 0, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                                <div class="flex items-center space-x-4">
                                    <input type="file" wire:model="avatar" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    @if($current_avatar)
                                        <button type="button" wire:click="removeAvatar" class="text-red-600 hover:text-red-800 text-sm font-medium">Remove</button>
                                    @endif
                                </div>
                                @if($avatar)
                                    <button type="button" wire:click="updateAvatar" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                                        Upload New Avatar
                                    </button>
                                @endif
                                @error('avatar') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" id="name" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" id="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" id="phone" wire:model="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                <input type="date" id="date_of_birth" wire:model="date_of_birth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('date_of_birth') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                                <input type="text" id="company" wire:model="company" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('company') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                <input type="url" id="website" wire:model="website" placeholder="https://" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('website') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                            <textarea id="bio" wire:model="bio" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Tell us about yourself..."></textarea>
                            @error('bio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Change Section -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                        <p class="mt-1 text-sm text-gray-600">Ensure your account is using a long, random password to stay secure.</p>
                    </div>

                    <form wire:submit.prevent="changePassword" class="p-6 space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" id="current_password" wire:model="current_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('current_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" id="new_password" wire:model="new_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('new_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" id="new_password_confirmation" wire:model="new_password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Security Tab -->
            @if($activeTab === 'security')
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Security Settings</h3>
                        <p class="mt-1 text-sm text-gray-600">Manage your account security and authentication preferences.</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Two-Factor Authentication -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Two-Factor Authentication</h4>
                                <p class="text-sm text-gray-600">Add an extra layer of security to your account</p>
                            </div>
                            <div class="flex items-center">
                                @if($two_factor_enabled)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-3">
                                        Enabled
                                    </span>
                                    <button class="bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700">
                                        Disable 2FA
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mr-3">
                                        Disabled
                                    </span>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                                        Enable 2FA
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Login Alerts -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Login Alerts</h4>
                                <p class="text-sm text-gray-600">Get notified when someone logs into your account</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="login_alerts" wire:change="updateSecuritySettings" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Account Activity -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-4">Recent Account Activity</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Last login</p>
                                            <p class="text-sm text-gray-600">
                                                @if($user->last_login_at)
                                                    {{ $user->last_login_at->format('M d, Y H:i') }}
                                                @else
                                                    Never
                                                @endif
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
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
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Saved Addresses</h3>
                            <p class="mt-1 text-sm text-gray-600">Manage your billing and shipping addresses.</p>
                        </div>
                        <button wire:click="addAddress" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                            Add New Address
                        </button>
                    </div>

                    <div class="p-6">
                        @if(count($addresses) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($addresses as $address)
                                    <div class="border border-gray-200 rounded-lg p-4 {{ $address['is_default'] ? 'ring-2 ring-blue-500' : '' }}">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $address['type'] === 'billing' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                                        {{ ucfirst($address['type']) }}
                                                    </span>
                                                    @if($address['is_default'])
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>
                                                <h4 class="font-medium text-gray-900">{{ $address['first_name'] }} {{ $address['last_name'] }}</h4>
                                                @if($address['company'])
                                                    <p class="text-sm text-gray-600">{{ $address['company'] }}</p>
                                                @endif
                                                <p class="text-sm text-gray-600">{{ $address['address_line_1'] }}</p>
                                                @if($address['address_line_2'])
                                                    <p class="text-sm text-gray-600">{{ $address['address_line_2'] }}</p>
                                                @endif
                                                <p class="text-sm text-gray-600">{{ $address['city'] }}, {{ $address['state'] }} {{ $address['postal_code'] }}</p>
                                                <p class="text-sm text-gray-600">{{ $address['country'] }}</p>
                                                @if($address['phone'])
                                                    <p class="text-sm text-gray-600">{{ $address['phone'] }}</p>
                                                @endif
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <div class="relative inline-block text-left">
                                                    <button class="text-gray-400 hover:text-gray-600">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex space-x-2">
                                            <button wire:click="editAddress({{ $address['id'] }})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Edit
                                            </button>
                                            @if(!$address['is_default'])
                                                <button wire:click="setDefaultAddress({{ $address['id'] }})" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                                    Set as Default
                                                </button>
                                            @endif
                                            <button wire:click="deleteAddress({{ $address['id'] }})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900">No addresses saved</h3>
                                <p class="mt-2 text-gray-600">Add your first address to make checkout faster.</p>
                                <button wire:click="addAddress" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                                    Add Address
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Notifications Tab -->
            @if($activeTab === 'notifications')
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Notification Preferences</h3>
                        <p class="mt-1 text-sm text-gray-600">Choose how you want to be notified about account activity.</p>
                    </div>

                    <form wire:submit.prevent="updateNotificationPreferences" class="p-6 space-y-6">
                        <!-- Email Notifications -->
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Email Notifications</h4>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Order Updates</label>
                                        <p class="text-sm text-gray-600">Get notified about order status changes</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="email_notifications.order_updates" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Promotional Emails</label>
                                        <p class="text-sm text-gray-600">Receive offers and product announcements</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="email_notifications.promotional" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Security Alerts</label>
                                        <p class="text-sm text-gray-600">Important account security notifications</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="email_notifications.security" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Notifications -->
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 mb-4">SMS Notifications</h4>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Order Updates</label>
                                        <p class="text-sm text-gray-600">Get SMS updates for urgent order changes</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="sms_notifications.order_updates" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Security Alerts</label>
                                        <p class="text-sm text-gray-600">Critical security notifications via SMS</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="sms_notifications.security" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Privacy Tab -->
            @if($activeTab === 'privacy')
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Privacy & Data</h3>
                        <p class="mt-1 text-sm text-gray-600">Control your privacy settings and manage your data.</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Privacy Settings -->
                        <form wire:submit.prevent="updatePrivacySettings">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Profile Visibility</label>
                                        <p class="text-sm text-gray-600">Control who can see your profile information</p>
                                    </div>
                                    <select wire:model="privacy_settings.profile_visibility" class="rounded-md border-gray-300 text-sm">
                                        <option value="public">Public</option>
                                        <option value="private">Private</option>
                                        <option value="friends">Friends Only</option>
                                    </select>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Show Email Address</label>
                                        <p class="text-sm text-gray-600">Allow others to see your email</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="privacy_settings.show_email" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-900">Data Processing</label>
                                        <p class="text-sm text-gray-600">Allow us to process your data for service improvement</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="privacy_settings.data_processing" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Save Privacy Settings
                                </button>
                            </div>
                        </form>

                        <!-- Data Management -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Data Management</h4>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Download Your Data</p>
                                        <p class="text-sm text-gray-600">Get a copy of all your data</p>
                                    </div>
                                    <button wire:click="downloadDataExport" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                                        Request Export
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Delete Account</p>
                                        <p class="text-sm text-gray-600">Permanently delete your account and all data</p>
                                    </div>
                                    <button wire:click="deleteAccount" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700">
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
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $editingAddress ? 'Edit Address' : 'Add New Address' }}
                        </h3>
                    </div>

                    <form wire:submit.prevent="saveAddress" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address Type</label>
                            <select wire:model="newAddress.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="billing">Billing</option>
                                <option value="shipping">Shipping</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" wire:model="newAddress.first_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('newAddress.first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" wire:model="newAddress.last_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('newAddress.last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Company (Optional)</label>
                            <input type="text" wire:model="newAddress.company" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address Line 1</label>
                            <input type="text" wire:model="newAddress.address_line_1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('newAddress.address_line_1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address Line 2 (Optional)</label>
                            <input type="text" wire:model="newAddress.address_line_2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">City</label>
                                <input type="text" wire:model="newAddress.city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('newAddress.city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">State/Province</label>
                                <input type="text" wire:model="newAddress.state" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('newAddress.state') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                <input type="text" wire:model="newAddress.postal_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('newAddress.postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Country</label>
                                <input type="text" wire:model="newAddress.country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('newAddress.country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone (Optional)</label>
                            <input type="tel" wire:model="newAddress.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="newAddress.is_default" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label class="ml-2 block text-sm text-gray-900">Set as default address</label>
                        </div>

                        <div class="flex justify-end space-x-4 pt-4">
                            <button type="button" wire:click="$set('showAddressModal', false)" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                                {{ $editingAddress ? 'Update Address' : 'Save Address' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <!-- Tab Navigation -->
        <div class="bg-white rounded-lg shadow-sm mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button wire:click="setActiveTab('profile')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'profile' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="w-5 h-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Profile
                    </button>
                    <button wire:click="setActiveTab('security')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="w-5 h-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Security
                    </button>
                    <button wire:click="setActiveTab('addresses')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'addresses' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="w-5 h-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Addresses
                    </button>
                    <button wire:click="setActiveTab('notifications')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="w-5 h-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h11" />
                        </svg>
                        Notifications
                    </button>
                    <button wire:click="setActiveTab('privacy')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'privacy' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="w-5 h-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Privacy
                    </button>
                </nav>
            </div>
        </div>

@endsection

