<x-filament-panels::page class="fi-dashboard-page">
    <!-- Hero Header (mirrors customer dashboard structure) -->
    <div class="fi-section-content-ctn">
        <div class="fi-section-header mb-8 pb-4">
            <div class="fi-section-header-wrapper">
                <div class="flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex-1 min-w-0">
                        <h1 class="fi-section-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20 mr-3 flex-shrink-0">
                                    <x-heroicon-o-home class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                </div>
                                <span class="truncate">{{ __('Admin Dashboard') }}</span>
                            </div>
                        </h1>
                        <p class="fi-section-header-description mt-2 text-sm text-gray-500 dark:text-gray-400 leading-6">
                            {{ __('Operational metrics, revenue, infrastructure & activity overview.') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-filament::button color="gray" outlined x-on:click.window="window.dispatchEvent(new Event('filament-refresh-widgets'))" icon="heroicon-o-arrow-path">
                            {{ __('Refresh') }}
                        </x-filament::button>
                        @php($analyticsUrl = url('admin/analytics'))
                        @if (app('router')->has('filament.admin.pages.analytics'))
                            <x-filament::button color="primary" tag="a" href="{{ route('filament.admin.pages.analytics') }}" icon="heroicon-o-chart-bar">{{ __('Analytics') }}</x-filament::button>
                        @else
                            <x-filament::button color="primary" tag="a" href="{{ $analyticsUrl }}" icon="heroicon-o-chart-bar">{{ __('Analytics') }}</x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- (Optional) Quick Actions Row placeholder -->
        <div class="mb-6">
            @includeIf('filament.admin.partials.top-quick-actions')
        </div>
    </div>

    <!-- Core Widgets Grid (no outer grid wrapper to avoid nested wrapping) -->
    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getVisibleWidgets()"
    >
        <!-- Simple skeleton shimmer (shows while Livewire hydrates / lazy widgets load) -->
        <div x-data="{show:true}" x-show="show" x-transition.opacity.duration.500ms class="space-y-4 mb-8" id="dashboard-skeleton">
            @for($i=0;$i<3;$i++)
                <div class="h-32 w-full rounded-xl bg-gray-200/70 dark:bg-gray-700/40 overflow-hidden relative">
                    <div class="absolute inset-0 animate-pulse bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent -translate-x-full shimmer"></div>
                </div>
            @endfor
        </div>
        <script>
            document.addEventListener('livewire:initialized', ()=>{
                window.addEventListener('livewire:navigated', hideSkeleton);
                window.addEventListener('load', hideSkeleton);
                setTimeout(hideSkeleton, 2500); // hard timeout fallback
                function hideSkeleton(){
                    const el = document.getElementById('dashboard-skeleton');
                    if(el){ el.style.display='none'; }
                }
            });
        </script>
    </x-filament-widgets::widgets>

    <!-- Live heartbeat indicator -->
    <div class="mt-8 px-2">
        <div x-data="{ts:Date.now(),alive:true}" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
            <span>{{ __('Live') }}</span>
        </div>
    </div>

    <!-- Entry animation (applied directly after widgets render) -->
    <template x-on:filament-widgets-loaded.window="
        document.querySelectorAll('[data-widget]').forEach((el,i)=>{
            el.classList.add('opacity-0','translate-y-2','transition','duration-500');
            setTimeout(()=>{
                el.classList.remove('opacity-0','translate-y-2');
                el.classList.add('opacity-100');
            }, 50 * i);
        });
    "></template>
</x-filament-panels::page>
