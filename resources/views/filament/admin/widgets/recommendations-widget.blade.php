<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Strategic Recommendations</x-slot>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse($recommendations as $rec)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                    <h4 class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-1">{{ $rec['title'] ?? 'Opportunity' }}</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">{{ $rec['description'] ?? ($rec['detail'] ?? 'No description') }}</p>
                    @if(isset($rec['impact']))
                        <div class="mt-2 text-xs">
                            <span class="px-2 py-0.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">Impact: {{ $rec['impact'] }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No recommendations available right now.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
