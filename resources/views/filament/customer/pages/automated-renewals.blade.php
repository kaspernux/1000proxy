<x-filament-panels::page>
    <div class="space-y-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Auto-Renewal Status Card -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white my-16">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Auto-Renewal Status</h2>
                    <p class="text-blue-100 mt-1">
                        {{ $this->getRenewalSettings()['auto_renew_enabled'] ? 'Active' : 'Inactive' }} •
                        {{ count($this->getUpcomingRenewals()) }} services expiring soon
                    </p>
                </div>
                <div class="text-right flex flex-col items-end gap-2">
                    <div class="p-3 bg-white/20 rounded-lg">
                        @if($this->getRenewalSettings()['auto_renew_enabled'])
                            <x-heroicon-o-check-circle class="w-8 h-8" />
                        @else
                            <x-heroicon-o-x-circle class="w-8 h-8" />
                        @endif
                    </div>
                    <!-- Auto-Renewal Toggle Button -->
                    <div class="mt-2">
                        <label class="inline-flex items-center cursor-pointer group" title="Toggle Auto-Renewal">
                            <input
                                type="checkbox"
                                class="sr-only peer"
                                {{ $this->getRenewalSettings()['auto_renew_enabled'] ? 'checked' : '' }}
                                wire:change="$set('renewalSettings.auto_renew_enabled', $event.target.checked)"
                            >
                            <div class="w-12 h-7 bg-white/30 rounded-full flex items-center transition-colors duration-300 peer-checked:bg-green-500 peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 relative">
                                <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow transition-transform duration-300 peer-checked:translate-x-5"></div>
                                <span class="absolute left-2 text-xs text-gray-700 font-semibold">OFF</span>
                                <span class="absolute right-2 text-xs text-green-700 font-semibold">ON</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-16">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-heroicon-o-arrow-path class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Auto-Renewals</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $this->getRenewalSettings()['auto_renew_enabled'] ? 'ON' : 'OFF' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <x-heroicon-o-clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Renewal Buffer</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $this->getRenewalSettings()['renewal_buffer_days'] }} days
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-heroicon-o-credit-card class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Payment Method</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white capitalize">
                            {{ $this->getRenewalSettings()['payment_method'] }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Renewals -->
        @if(count($this->getUpcomingRenewals()) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow my-16">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Upcoming Renewals</h3>
                <div class="space-y-3">
                    @foreach($this->getUpcomingRenewals() as $renewal)
                        <div class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-center">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-500 mr-3" />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $renewal->orderItems->first()?->serverClient?->server?->name ?? 'Unknown Server' }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Expires: {{ $renewal->orderItems->first()?->expires_at?->format('M j, Y') ?? 'Unknown' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    {{ $renewal->orderItems->first()?->expires_at?->diffInDays() ?? 0 }} days left
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Renewal Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow my-16">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Renewal Preferences</h3>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Auto-Renewal</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            class="sr-only peer"
                            {{ $this->getRenewalSettings()['auto_renew_enabled'] ? 'checked' : '' }}
                            wire:change="$set('renewalSettings.auto_renew_enabled', $event.target.checked)"
                        >
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Renewal Buffer Period
                    </label>
                    <select
                        wire:model="renewalSettings.renewal_buffer_days"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="1">1 day before</option>
                        <option value="3">3 days before</option>
                        <option value="7">7 days before</option>
                        <option value="14">14 days before</option>
                        <option value="30">30 days before</option>
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        When to attempt renewal before expiration
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Payment Method
                    </label>
                    <select
                        wire:model="renewalSettings.payment_method"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="wallet">Wallet Balance</option>
                        <option value="crypto">Cryptocurrency</option>
                        <option value="card">Credit Card</option>
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Preferred payment method for renewals
                    </p>
                </div>
            </div>
        </div>

        <!-- Services Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto my-16">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                <x-heroicon-o-arrow-path class="w-5 h-5 text-green-500" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Service Renewals</h3>
            </div>
            <div class="px-2 py-4">
                {{ $this->table }}
            </div>
        </div>

        <!-- Renewal Tips -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 my-16">
            <h4 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-3">💡 Renewal Tips</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800 dark:text-blue-200">
                <div class="flex items-start">
                    <x-heroicon-o-light-bulb class="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" />
                    <p>Keep sufficient wallet balance to ensure automatic renewals don't fail</p>
                </div>
                <div class="flex items-start">
                    <x-heroicon-o-bell class="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" />
                    <p>Enable notifications to stay informed about renewal status</p>
                </div>
                <div class="flex items-start">
                    <x-heroicon-o-shield-check class="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" />
                    <p>Review your services regularly to avoid unnecessary renewals</p>
                </div>
                <div class="flex items-start">
                    <x-heroicon-o-calendar class="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" />
                    <p>Set renewal buffer to at least 7 days for payment processing time</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            // Real-time updates for renewal status
            setInterval(() => {
                @this.call('$refresh');
            }, 60000); // Refresh every minute
        });
    </script>
    @endpush
</x-filament-panels::page>
