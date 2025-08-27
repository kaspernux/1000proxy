<x-filament-widgets::widget>
    <x-filament::section>
        <div class="max-h-[42rem] overflow-y-auto p-5" @if(!$pauseLive) wire:poll.keep-alive.{{ $pollIntervalSec ?? 20 }}s="refreshLiveData" @endif>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Live Server Metrics') }}</h3>
                <div class="flex items-center gap-2">
                    <x-filament::badge color="success" icon="heroicon-o-bolt">{{ __('Live') }}</x-filament::badge>
                    <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-path" wire:click="refreshLiveData">{{ __('Refresh') }}</x-filament::button>
                </div>
            </div>

            @if(!empty($serverMetrics))
                <div class="space-y-3">
                    @foreach($serverMetrics as $m)
                        <div class="rounded-xl border border-gray-200/70 dark:border-white/10 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $m['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $m['country'] }}</div>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                                    <x-filament::badge size="sm" :color="(($m['status'] ?? 'healthy') === 'healthy') ? 'success' : ((($m['status'] ?? 'warning') === 'warning') ? 'warning' : 'danger')">
                                        {{ ucfirst($m['status'] ?? 'healthy') }}
                                    </x-filament::badge>
                                    @if(!empty($m['is_login_locked']))
                                        <x-filament::badge size="sm" color="danger">{{ __('Locked') }}</x-filament::badge>
                                    @endif
                                    @if(array_key_exists('has_valid_session', $m))
                                        <x-filament::badge size="sm" :color="$m['has_valid_session'] ? 'success' : 'warning'">{{ $m['has_valid_session'] ? __('Session OK') : __('No Session') }}</x-filament::badge>
                                    @endif
                                    @if(!empty($m['panel_url']))
                                        <a href="{{ $m['panel_url'] }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">{{ __('Panel') }}</a>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                @php($cpu = (int)($m['cpu_usage'] ?? 0))
                                @php($mem = (int)($m['memory_usage'] ?? 0))
                                @php($disk = (int)($m['disk_usage'] ?? 0))
                                <div>
                                    <div class="flex justify-between text-[11px] text-gray-600 dark:text-gray-400"><span>CPU</span><span>{{ $cpu }}%</span></div>
                                    <div class="mt-1 h-2 rounded bg-gray-200 dark:bg-gray-700">
                                        <div class="h-2 rounded bg-blue-500" style="width: {{ max(0,min(100,$cpu)) }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-[11px] text-gray-600 dark:text-gray-400"><span>Memory</span><span>{{ $mem }}%</span></div>
                                    <div class="mt-1 h-2 rounded bg-gray-200 dark:bg-gray-700">
                                        <div class="h-2 rounded bg-emerald-500" style="width: {{ max(0,min(100,$mem)) }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-[11px] text-gray-600 dark:text-gray-400"><span>Disk</span><span>{{ $disk }}%</span></div>
                                    <div class="mt-1 h-2 rounded bg-gray-200 dark:bg-gray-700">
                                        <div class="h-2 rounded bg-purple-500" style="width: {{ max(0,min(100,$disk)) }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-gray-600 dark:text-gray-400">
                                <span>{{ __('Response') }}: <span class="font-medium text-gray-900 dark:text-white">{{ (int)($m['response_time_ms'] ?? 0) }}ms</span></span>
                                <span>{{ __('Clients') }}: <span class="font-medium text-gray-900 dark:text-white">{{ (int)($m['active_clients'] ?? 0) }}</span></span>
                                <span>{{ __('Inbounds') }}: <span class="font-medium text-gray-900 dark:text-white">{{ (int)($m['inbounds_count'] ?? 0) }}</span></span>
                                <div class="ms-auto flex items-center gap-1.5">
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-presentation-chart-line" wire:click="monitorServerPerformance({{ $m['id'] }})">{{ __('Monitor') }}</x-filament::button>
                                    <x-filament::button size="xs" color="purple" icon="heroicon-o-arrow-down-tray" wire:click="syncInbounds({{ $m['id'] }})">{{ __('Sync Inbounds') }}</x-filament::button>
                                    <x-filament::button size="xs" color="danger" icon="heroicon-o-trash" wire:click="resetAllTraffics({{ $m['id'] }})">{{ __('Reset Traffics') }}</x-filament::button>
                                    @if(!empty($m['is_login_locked']))
                                        <x-filament::button size="xs" color="danger" icon="heroicon-o-lock-open" wire:click="unlockXuiServer({{ $m['id'] }})">{{ __('Unlock') }}</x-filament::button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No live metrics available.') }}</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
