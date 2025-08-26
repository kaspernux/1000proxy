<x-filament::section class="py-6">
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-heroicon-o-question-mark-circle class="w-5 h-5 text-primary-600" />
            Need Help?
        </div>
    </x-slot>

    <div class="space-y-3">
        <div class="space-y-2">
            <div class="flex items-start gap-3 p-2 bg-success-50 dark:bg-success-900/20 rounded-lg">
                <div class="p-1 bg-success-100 dark:bg-success-800 rounded-full flex-shrink-0 mt-0.5">
                    <x-heroicon-o-map-pin class="w-3 h-3 text-success-600" />
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-medium text-gray-900 dark:text-white block">Choose Closest Location</span>
                    <p class="text-gray-600 dark:text-gray-400 text-xs">Lower latency = better performance</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-2 bg-info-50 dark:bg-info-900/20 rounded-lg">
                <div class="p-1 bg-info-100 dark:bg-info-800 rounded-full flex-shrink-0 mt-0.5">
                    <x-heroicon-o-shield-check class="w-3 h-3 text-info-600" />
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-medium text-gray-900 dark:text-white block">99.9% Uptime Guarantee</span>
                    <p class="text-gray-600 dark:text-gray-400 text-xs">Reliable connections you can trust</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-2 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                <div class="p-1 bg-warning-100 dark:bg-warning-800 rounded-full flex-shrink-0 mt-0.5">
                    <x-heroicon-o-cog-6-tooth class="w-3 h-3 text-warning-600" />
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-medium text-gray-900 dark:text-white block">Match Your Usage</span>
                    <p class="text-gray-600 dark:text-gray-400 text-xs">Consider speed vs. price needs</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <x-filament::button
                color="primary"
                icon="heroicon-o-sparkles"
                size="sm"
                wire:click="getServerRecommendations"
                class="justify-center"
            >
                Get Recommendations
            </x-filament::button>

            <x-filament::button
                color="gray"
                icon="heroicon-o-chat-bubble-left-right"
                size="sm"
                class="justify-center"
            >
                Live Support
            </x-filament::button>
        </div>
    </div>
</x-filament::section>
