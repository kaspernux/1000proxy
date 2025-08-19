<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Business Intelligence</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Key KPIs and trends for the past 30 days.</p>
        </div>

        @isset($analytics)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <x-filament::section heading="Revenue (30d)">
                    <div class="text-2xl font-semibold">{{ number_format($analytics['revenue_30d'] ?? 0, 2) }}</div>
                </x-filament::section>
                <x-filament::section heading="Paid Orders (30d)">
                    <div class="text-2xl font-semibold">{{ $analytics['paid_orders_30d'] ?? 0 }}</div>
                </x-filament::section>
                <x-filament::section heading="New Users (30d)">
                    <div class="text-2xl font-semibold">{{ $analytics['new_users_30d'] ?? 0 }}</div>
                </x-filament::section>
            </div>
        @endisset

        <div class="space-y-4">
            @foreach (method_exists($this, 'getHeaderWidgets') ? $this->getHeaderWidgets() : [] as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
