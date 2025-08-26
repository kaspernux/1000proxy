<x-filament::section class="py-6">
    <x-slot name="heading">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-5 h-5 text-primary-600" />
                System Status
            </div>
            <x-filament::badge color="success" size="xs">
                <div class="flex items-center gap-1">
                    <div class="w-1.5 h-1.5 bg-success-600 rounded-full animate-pulse"></div>
                    All Systems Operational
                </div>
            </x-filament::badge>
        </div>
    </x-slot>

    <div class="space-y-3">
        <div class="grid grid-cols-1 gap-2">
            <div class="flex items-center justify-between p-2 bg-success-50 dark:bg-success-900/20 rounded-lg border border-success-100 dark:border-success-800">
                <div class="flex items-center gap-2">
                    <div class="p-1 bg-success-100 dark:bg-success-800 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Server Network</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-2 h-2 bg-success-500 rounded-full"></div>
                    <span class="text-xs text-success-600 font-medium">Online</span>
                </div>
            </div>

            <div class="flex items-center justify-between p-2 bg-info-50 dark:bg-info-900/20 rounded-lg border border-info-100 dark:border-info-800">
                <div class="flex items-center gap-2">
                    <div class="p-1 bg-info-100 dark:bg-info-800 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Auto-refresh</span>
                </div>
                <span class="text-xs text-info-600 font-medium">30s</span>
            </div>

            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <div class="p-1 bg-gray-100 dark:bg-gray-700 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Last updated</span>
                </div>
                <span class="text-xs text-gray-500 font-medium">{{ now()->format('H:i') }}</span>
            </div>
        </div>

        <div class="p-3 bg-gradient-to-r from-primary-50 to-purple-50 dark:from-primary-900/20 dark:to-purple-900/20 rounded-lg border border-primary-100 dark:border-primary-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">Network Performance</span>
                <x-filament::badge color="primary" size="xs">Excellent</x-filament::badge>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Uptime</p>
                    <p class="text-sm font-bold text-success-600">99.9%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Latency</p>
                    <p class="text-sm font-bold text-info-600">12ms</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Load</p>
                    <p class="text-sm font-bold text-warning-600">Low</p>
                </div>
            </div>
        </div>
    </div>
</x-filament::section>
