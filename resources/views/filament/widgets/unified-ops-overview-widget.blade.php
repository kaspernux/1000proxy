<x-filament-widgets::widget>
    <x-filament::section>
        <div class="p-4">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Business & Infrastructure Overview') }}</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('Unified KPIs across revenue, customers, servers and health.') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::badge color="success" icon="heroicon-o-bolt">{{ __('Live') }}</x-filament::badge>
                    <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-path" wire:click="refreshData">{{ __('Refresh') }}</x-filament::button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <!-- Business KPIs -->
                <div class="space-y-3">
                    <h4 class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Business KPIs') }}</h4>
                    <div class="grid grid-cols-1 items-stretch gap-3 sm:grid-cols-2 lg:grid-cols-3 auto-rows-fr">
                        @foreach(($business ?? []) as $kpi)
                            @php($color = $kpi['color'] ?? 'gray')
                            <div class="h-full min-h-28 rounded-xl border border-primary-100 bg-primary-50 p-4 transition hover:bg-primary-100 dark:border-primary-800 dark:bg-primary-900/20 dark:hover:bg-primary-900/30">
                                <div class="flex h-full flex-col justify-between gap-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</div>
                                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $kpi['value'] }}</div>
                                            <div class="text-[11px] text-gray-600 dark:text-gray-400">{{ $kpi['desc'] }}</div>
                                        </div>
                                        <div @class([
                                            'inline-flex h-9 w-9 items-center justify-center rounded-lg',
                                            'bg-green-100 dark:bg-green-900/30' => $color === 'success',
                                            'bg-amber-100 dark:bg-amber-900/30' => $color === 'warning',
                                            'bg-rose-100 dark:bg-rose-900/30' => $color === 'danger',
                                            'bg-sky-100 dark:bg-sky-900/30' => $color === 'info',
                                            'bg-purple-100 dark:bg-purple-900/30' => $color === 'purple',
                                            'bg-cyan-100 dark:bg-cyan-900/30' => $color === 'cyan',
                                            'bg-primary-100 dark:bg-primary-900/30' => $color === 'gray',
                                        ])>
                                            <x-filament::icon :icon="$kpi['icon']" class="@class([
                                                'h-5 w-5',
                                                'text-green-600 dark:text-green-300' => $color === 'success',
                                                'text-amber-600 dark:text-amber-300' => $color === 'warning',
                                                'text-rose-600 dark:text-rose-300' => $color === 'danger',
                                                'text-sky-600 dark:text-sky-300' => $color === 'info',
                                                'text-purple-600 dark:text-purple-300' => $color === 'purple',
                                                'text-cyan-600 dark:text-cyan-300' => $color === 'cyan',
                                                'text-primary-600 dark:text-primary-300' => $color === 'gray',
                                            ])" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Infrastructure KPIs -->
                <div class="space-y-3">
                    <h4 class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Infrastructure KPIs') }}</h4>
                    <div class="grid grid-cols-1 items-stretch gap-3 sm:grid-cols-2 lg:grid-cols-3 auto-rows-fr">
                        @foreach(($infra ?? []) as $kpi)
                            @php($color = $kpi['color'] ?? 'gray')
                            <div class="h-full min-h-28 rounded-xl border border-primary-100 bg-primary-50 p-4 transition hover:bg-primary-100 dark:border-primary-800 dark:bg-primary-900/20 dark:hover:bg-primary-900/30">
                                <div class="flex h-full flex-col justify-between gap-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</div>
                                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $kpi['value'] }}</div>
                                            <div class="text-[11px] text-gray-600 dark:text-gray-400">{{ $kpi['desc'] }}</div>
                                        </div>
                                        <div @class([
                                            'inline-flex h-9 w-9 items-center justify-center rounded-lg',
                                            'bg-green-100 dark:bg-green-900/30' => $color === 'success',
                                            'bg-amber-100 dark:bg-amber-900/30' => $color === 'warning',
                                            'bg-rose-100 dark:bg-rose-900/30' => $color === 'danger',
                                            'bg-sky-100 dark:bg-sky-900/30' => $color === 'info',
                                            'bg-purple-100 dark:bg-purple-900/30' => $color === 'purple',
                                            'bg-cyan-100 dark:bg-cyan-900/30' => $color === 'cyan',
                                            'bg-primary-100 dark:bg-primary-900/30' => $color === 'gray',
                                        ])>
                                            <x-filament::icon :icon="$kpi['icon']" class="@class([
                                                'h-5 w-5',
                                                'text-green-600 dark:text-green-300' => $color === 'success',
                                                'text-amber-600 dark:text-amber-300' => $color === 'warning',
                                                'text-rose-600 dark:text-rose-300' => $color === 'danger',
                                                'text-sky-600 dark:text-sky-300' => $color === 'info',
                                                'text-purple-600 dark:text-purple-300' => $color === 'purple',
                                                'text-cyan-600 dark:text-cyan-300' => $color === 'cyan',
                                                'text-primary-600 dark:text-primary-300' => $color === 'gray',
                                            ])" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
