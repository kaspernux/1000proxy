<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AI Insights & Recommendations</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Automatically generated insights to guide decisions.</p>
        </div>

    <x-filament-widgets::widgets :widgets="method_exists($this, 'getHeaderWidgets') ? $this->getHeaderWidgets() : []" />
    </div>
</x-filament-panels::page>
