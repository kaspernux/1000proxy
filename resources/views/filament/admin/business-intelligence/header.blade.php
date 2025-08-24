<div class="mb-4 -mx-6 border-b border-gray-200/60 px-6 pb-3 dark:border-white/10">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Business Intelligence</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Comprehensive analytics and insights</p>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="timeRange" class="fi-input block rounded-lg border-gray-300 bg-white px-3 py-2 text-sm text-black placeholder:text-black shadow-sm outline-none ring-0 transition focus:border-primary-500 dark:border-white/10 dark:bg-white dark:text-black dark:placeholder:text-black">
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
                <option value="90d">Last 90 days</option>
                <option value="1y">Last year</option>
            </select>
            <x-filament::button icon="heroicon-o-arrow-path" wire:click="refreshDashboard">Refresh</x-filament::button>
        </div>
    </div>

    <div class="mt-3">
        <nav class="flex gap-1" aria-label="Tabs">
            @php($tabs=['overview'=>'Overview','revenue'=>'Revenue','users'=>'Users','servers'=>'Servers','insights'=>'Insights'])
            @foreach($tabs as $key=>$label)
                <button wire:click="switchTab('{{ $key }}')" @class([
                    'px-3 py-1.5 text-sm rounded-md',
                    'bg-primary-600 text-white' => ($tab ?? 'overview') === $key,
                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' => ($tab ?? 'overview') !== $key,
                ])>
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>
</div>
