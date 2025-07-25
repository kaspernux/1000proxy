<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Progressive Web App</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Installation status and management</p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <span class="{{ $this->getStatusColor() }} font-medium text-sm">
                {{ $this->getStatusIcon() }} {{ $this->getStatusText() }}
            </span>

            <button wire:click="refreshStatus"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Installation Progress --}}
    @if($installationStatus !== 'complete')
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">PWA Installation Required</h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        Complete the PWA installation to enable offline functionality and app-like experience.
                    </p>
                </div>
            </div>

            @if($installationStatus !== 'complete')
                <div class="mt-4">
                    <button wire:click="installPWA"
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Install PWA Components
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Installation Status Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Manifest</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">App configuration</p>
                </div>
                <span class="text-xl">{{ $stats['manifest_exists'] ? '✅' : '❌' }}</span>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Service Worker</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Offline support</p>
                </div>
                <span class="text-xl">{{ $stats['service_worker_exists'] ? '✅' : '❌' }}</span>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Offline Page</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Fallback content</p>
                </div>
                <span class="text-xl">{{ $stats['offline_page_exists'] ? '✅' : '❌' }}</span>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Icons</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">App assets</p>
                </div>
                <span class="text-xl">{{ $stats['icons_directory_exists'] ? '✅' : '❌' }}</span>
            </div>
        </div>
    </div>

    {{-- Features Status --}}
    <div class="mb-6">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Supported Features</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @if(isset($stats['supported_features']))
                @foreach($stats['supported_features'] as $feature => $supported)
                    @php
                        $featureStatus = $this->getFeatureStatus($feature);
                    @endphp
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ ucwords(str_replace('_', ' ', $feature)) }}
                        </span>
                        <span class="{{ $featureStatus['color'] }}">{{ $featureStatus['icon'] }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <button wire:click="updateCache"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Update Cache
        </button>

        <button wire:click="toggleNotifications"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            {{ $showNotifications ? 'Hide' : 'Show' }} Notifications
            @if(count($notifications) > 0)
                <span class="ml-1 bg-green-800 text-white text-xs px-2 py-1 rounded-full">
                    {{ count($notifications) }}
                </span>
            @endif
        </button>

        @if($this->isAdmin)
            <button wire:click="generateTestData"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Generate Test Data
            </button>
        @endif
    </div>

    {{-- Notifications Panel --}}
    @if($showNotifications)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Push Notifications</h4>
                @if(count($notifications) > 0)
                    <button wire:click="clearNotifications"
                            class="text-red-600 hover:text-red-700 text-sm font-medium">
                        Clear All
                    </button>
                @endif
            </div>

            {{-- Send Notification Form --}}
            @if($this->isAdmin)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Send Test Notification</h5>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                            <input wire:model="notificationTitle"
                                   type="text"
                                   placeholder="Notification title..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            @error('notificationTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                            <textarea wire:model="notificationBody"
                                      placeholder="Notification message..."
                                      rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white"></textarea>
                            @error('notificationBody') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon URL</label>
                                <input wire:model="notificationIcon"
                                       type="url"
                                       placeholder="https://..."
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                @error('notificationIcon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target URL</label>
                                <input wire:model="notificationUrl"
                                       type="url"
                                       placeholder="/"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                @error('notificationUrl') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button wire:click="sendTestNotification"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Send Test Notification
                            </button>

                            <button wire:click="resetNotificationForm"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Notifications List --}}
            <div class="space-y-3">
                @forelse($notifications as $index => $notification)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $notification['title'] ?? 'Untitled' }}
                                </h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $notification['body'] ?? 'No message' }}
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ \Carbon\Carbon::parse($notification['timestamp'])->diffForHumans() }}</span>
                                    @if(isset($notification['url']) && $notification['url'])
                                        <a href="{{ $notification['url'] }}" class="text-blue-600 hover:text-blue-700">
                                            View Target
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if(isset($notification['icon']) && $notification['icon'])
                                <div class="ml-3">
                                    <img src="{{ $notification['icon'] }}"
                                         alt="Notification icon"
                                         class="w-8 h-8 rounded-full"
                                         onerror="this.style.display='none'">
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-6a2 2 0 00-2-2H8a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v1.5"/>
                        </svg>
                        <p class="text-sm">No notifications yet</p>
                        @if($this->isAdmin)
                            <p class="text-xs mt-1">Send a test notification to see it here</p>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        </div>
    @endif
</div>
