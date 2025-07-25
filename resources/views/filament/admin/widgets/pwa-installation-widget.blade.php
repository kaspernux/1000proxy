<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-device-phone-mobile class="h-5 w-5 text-blue-500" />
                <span>Progressive Web App</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <div class="flex items-center space-x-2">
                @if($isFullyInstalled)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                        Installed
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 mr-1" />
                        {{ $installationPercentage }}% Complete
                    </span>
                @endif
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Installation Progress --}}
            <div>
                <div class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <span>Installation Progress</span>
                    <span>{{ $completedRequirements }}/{{ $totalRequirements }} Components</span>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="h-2 rounded-full transition-all duration-300
                                {{ $installationPercentage === 100 ? 'bg-green-600' :
                                   ($installationPercentage >= 75 ? 'bg-yellow-600' :
                                   ($installationPercentage >= 50 ? 'bg-blue-600' : 'bg-red-600')) }}"
                         style="width: {{ $installationPercentage }}%"></div>
                </div>
            </div>

            {{-- Component Status Grid --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl mb-1">
                        {{ $stats['manifest_exists'] ? '✅' : '❌' }}
                    </div>
                    <div class="text-xs font-medium text-gray-900 dark:text-white">Manifest</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">App Config</div>
                </div>

                <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl mb-1">
                        {{ $stats['service_worker_exists'] ? '✅' : '❌' }}
                    </div>
                    <div class="text-xs font-medium text-gray-900 dark:text-white">Service Worker</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Offline Support</div>
                </div>

                <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl mb-1">
                        {{ $stats['offline_page_exists'] ? '✅' : '❌' }}
                    </div>
                    <div class="text-xs font-medium text-gray-900 dark:text-white">Offline Page</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Fallback View</div>
                </div>

                <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl mb-1">
                        {{ $stats['icons_directory_exists'] ? '✅' : '❌' }}
                    </div>
                    <div class="text-xs font-medium text-gray-900 dark:text-white">Icons</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">App Assets</div>
                </div>
            </div>

            {{-- Features Overview --}}
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Supported Features
                    </h4>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $supportedFeatures }}/{{ $totalFeatures }}
                    </span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                    @if(isset($stats['supported_features']))
                        @foreach($stats['supported_features'] as $feature => $supported)
                            <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded">
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ ucwords(str_replace('_', ' ', $feature)) }}
                                </span>
                                <span class="{{ $supported ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $supported ? '✅' : '❌' }}
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="flex flex-wrap gap-2">
                @if(!$isFullyInstalled)
                    <button type="button"
                            onclick="installPWA()"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1" />
                        Install PWA
                    </button>
                @endif

                <button type="button"
                        onclick="updatePWACache()"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-1" />
                    Update Cache
                </button>

                <button type="button"
                        onclick="testPWA()"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <x-heroicon-o-beaker class="w-4 h-4 mr-1" />
                    Test
                </button>

                <a href="/api/pwa/status"
                   target="_blank"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                    View Status
                </a>
            </div>

            {{-- Technical Details --}}
            <div class="text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="font-medium">Cache Version:</span>
                        <span class="ml-1">{{ $cacheVersion }}</span>
                    </div>
                    <div>
                        <span class="font-medium">Last Updated:</span>
                        <span class="ml-1">{{ \Carbon\Carbon::parse($lastUpdated)->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- JavaScript for PWA actions --}}
    <script>
        async function installPWA() {
            try {
                const response = await fetch('/api/pwa/install', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await response.json();

                if (result.status === 'success') {
                    new FilamentNotification()
                        .title('PWA Installed Successfully')
                        .success()
                        .send();

                    // Refresh page after 2 seconds
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(result.message || 'Installation failed');
                }
            } catch (error) {
                new FilamentNotification()
                    .title('PWA Installation Failed')
                    .body(error.message)
                    .danger()
                    .send();
            }
        }

        async function updatePWACache() {
            try {
                const response = await fetch('/api/pwa/update-cache', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await response.json();

                if (result.status === 'success') {
                    new FilamentNotification()
                        .title('PWA Cache Updated')
                        .body(`Updated to version: ${result.version}`)
                        .success()
                        .send();
                } else {
                    throw new Error(result.message || 'Cache update failed');
                }
            } catch (error) {
                new FilamentNotification()
                    .title('Cache Update Failed')
                    .body(error.message)
                    .danger()
                    .send();
            }
        }

        async function testPWA() {
            try {
                const response = await fetch('/api/pwa/status');
                const result = await response.json();

                if (result.status === 'success') {
                    new FilamentNotification()
                        .title('PWA Test Completed')
                        .body('PWA functionality is working correctly')
                        .success()
                        .send();
                } else {
                    throw new Error('PWA test failed');
                }
            } catch (error) {
                new FilamentNotification()
                    .title('PWA Test Failed')
                    .body(error.message)
                    .danger()
                    .send();
            }
        }
    </script>
</x-filament-widgets::widget>
