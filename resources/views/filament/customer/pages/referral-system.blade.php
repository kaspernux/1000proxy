<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Referral Header -->
        <div class="bg-gradient-to-r from-green-400 to-blue-500 rounded-lg p-8 text-white">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Earn with Referrals!</h1>
                    <p class="text-green-100 mb-4">
                        Share your referral code and earn commissions for every successful referral.
                    </p>
                    <div class="bg-white/20 backdrop-blur rounded-lg p-4">
                        <p class="text-sm text-green-100 mb-1">Your Referral Code</p>
                        <div class="flex items-center space-x-3">
                            <code class="text-2xl font-bold tracking-wider">{{ $this->getReferralCode() }}</code>
                            <button
                                onclick="copyToClipboard('{{ $this->getReferralCode() }}')"
                                class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded text-sm transition-colors"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="text-center lg:text-right">
                    <div class="inline-block p-6 bg-white/10 backdrop-blur rounded-full">
                        <x-heroicon-o-gift class="w-16 h-16" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-heroicon-o-users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Referrals</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getReferralStats()['total_referrals'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Referrals</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getReferralStats()['active_referrals'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <x-heroicon-o-banknotes class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Earnings</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($this->getReferralStats()['total_earnings'], 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-heroicon-o-chart-bar class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Conversion Rate</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->getReferralStats()['conversion_rate'], 1) }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Available Earnings</h3>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2">
                        ${{ number_format($this->getReferralStats()['available_earnings'], 2) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Ready to withdraw</p>
                    @if($this->getReferralStats()['available_earnings'] > 0)
                        <button
                            wire:click="requestWithdrawal"
                            class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
                        >
                            Withdraw Now
                        </button>
                    @else
                        <button
                            disabled
                            class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed"
                        >
                            No Earnings Available
                        </button>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Pending Earnings</h3>
                <div class="text-center">
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mb-2">
                        ${{ number_format($this->getReferralStats()['pending_earnings'], 2) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Processing commissions</p>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: 60%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Tier</h3>
                <div class="text-center">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mb-2">
                        {{ $this->getCurrentTier()['name'] }}
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $this->getCurrentTier()['commission_rate'] }}%
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Commission Rate</p>
                </div>
            </div>
        </div>

        <!-- Referral Tiers -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Referral Tiers & Benefits</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->getReferralTiers() as $tier)
                    <div class="border-2 {{ $this->getCurrentTier()['name'] === $tier['name'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }} rounded-lg p-4">
                        <div class="text-center mb-3">
                            <h4 class="font-bold text-lg {{ $this->getCurrentTier()['name'] === $tier['name'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $tier['name'] }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $tier['min_referrals'] }}+ referrals</p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Commission</span>
                                <span class="font-medium">{{ $tier['commission_rate'] }}%</span>
                            </div>
                            @foreach($tier['bonuses'] as $bonus)
                                <div class="flex items-center text-sm">
                                    <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                                    <span class="text-gray-700 dark:text-gray-300">{{ $bonus }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Share Options -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Share Your Referral Code</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @foreach($this->getShareableLinks() as $type => $link)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2 capitalize">{{ str_replace('_', ' ', $type) }}</h4>
                        <div class="flex items-center space-x-2">
                            <input
                                type="text"
                                value="{{ $link }}"
                                readonly
                                class="flex-1 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded px-3 py-2"
                            >
                            <button
                                onclick="copyToClipboard('{{ $link }}')"
                                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm transition-colors"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    wire:click="$dispatch('openModal', { component: 'share-referral' })"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                    <x-heroicon-o-share class="w-4 h-4 mr-2" />
                    Share via Email
                </button>
                <button
                    onclick="shareToTelegram()"
                    class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                >
                    <x-heroicon-o-chat-bubble-left class="w-4 h-4 mr-2" />
                    Share to Telegram
                </button>
                <button
                    onclick="shareToTwitter()"
                    class="inline-flex items-center px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-lg transition-colors"
                >
                    <x-heroicon-o-megaphone class="w-4 h-4 mr-2" />
                    Share to Twitter
                </button>
            </div>
        </div>

        <!-- Referrals Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Referrals</h3>
            </div>
            {{ $this->table }}
        </div>

        <!-- Referral Tips -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg p-6 text-white">
            <h4 class="text-xl font-bold mb-4">🚀 Maximize Your Earnings</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h5 class="font-semibold mb-2">Best Practices</h5>
                    <ul class="space-y-1 text-sm text-purple-100">
                        <li>• Share in relevant communities and forums</li>
                        <li>• Write honest reviews about our service</li>
                        <li>• Explain the benefits to potential users</li>
                        <li>• Use social media to reach more people</li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-2">Earning Tips</h5>
                    <ul class="space-y-1 text-sm text-purple-100">
                        <li>• Focus on quality over quantity</li>
                        <li>• Engage with your referrals to help them succeed</li>
                        <li>• Track your performance and optimize</li>
                        <li>• Stay updated with new features to share</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success notification
                window.dispatchEvent(new CustomEvent('show-notification', {
                    detail: { message: 'Copied to clipboard!', type: 'success' }
                }));
            });
        }

        function shareToTelegram() {
            const text = `Join me on 1000proxy and get premium proxy services! Use my referral code: {{ $this->getReferralCode() }}`;
            const url = `{{ $this->getShareableLinks()['direct_link'] }}`;
            const telegramUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`;
            window.open(telegramUrl, '_blank');
        }

        function shareToTwitter() {
            const text = `Check out 1000proxy for premium proxy services! Use my referral code: {{ $this->getReferralCode() }} {{ $this->getShareableLinks()['direct_link'] }}`;
            const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`;
            window.open(twitterUrl, '_blank');
        }
    </script>
    @endpush
</x-filament-panels::page>
