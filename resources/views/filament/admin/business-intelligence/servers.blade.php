<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Server Analytics</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Server performance, usage, and trends.</p>
        </div>

        <div class="space-y-4">
            @foreach (method_exists($this, 'getHeaderWidgets') ? $this->getHeaderWidgets() : [] as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
