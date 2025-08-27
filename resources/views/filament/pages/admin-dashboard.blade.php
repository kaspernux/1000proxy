<x-filament-panels::page class="fi-dashboard-page">
    <!-- Hero Header: gradient, icon, and compact controls -->
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-primary-600/10 via-primary-500/10 to-transparent p-5 ring-1 ring-gray-200/60 dark:ring-white/10">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-600/10 text-primary-700 dark:text-primary-300">
                        <x-heroicon-o-home class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-2xl">{{ __('Admin Dashboard') }}</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Revenue, infrastructure, orders, and user activity at a glance.') }}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" x-on:click="window.dispatchEvent(new CustomEvent('filament-refresh-widgets'))">
                    {{ __('Refresh') }}
                </x-filament::button>
                @php($analyticsUrl = url('admin/analytics'))
                @if (app('router')->has('filament.admin.pages.analytics'))
                    <x-filament::button color="primary" icon="heroicon-o-chart-bar" tag="a" href="{{ route('filament.admin.pages.analytics') }}">
                        {{ __('Analytics') }}
                    </x-filament::button>
                @else
                    <x-filament::button color="primary" icon="heroicon-o-chart-bar" tag="a" href="{{ $analyticsUrl }}">
                        {{ __('Analytics') }}
                    </x-filament::button>
                @endif
            </div>
        </div>

        <!-- Top quick actions (optional include) -->
        @includeIf('filament.admin.partials.top-quick-actions')
    </div>

    <!-- Widgets Grid -->
    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getVisibleWidgets()"
    >
        <!-- Skeleton shimmer while Livewire hydrates -->
    <div x-data="{show:true}" x-init="setTimeout(()=>show=false,1200)" x-show="show" x-transition.opacity.duration.400ms class="space-y-3 mb-6" id="dashboard-skeleton">
            @for ($i = 0; $i < 3; $i++)
        <div class="relative h-28 w-full overflow-hidden rounded-xl bg-gray-200/70 dark:bg-gray-800/40">
            <div class="absolute inset-0 animate-pulse bg-gradient-to-r from-transparent via-white/30 dark:via-white/10 to-transparent"></div>
                </div>
            @endfor
        </div>
    </x-filament-widgets::widgets>

    <!-- Live heartbeat indicator -->
    <div class="mt-6 px-1">
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span class="relative inline-flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span></span>
            <span>{{ __('Live') }}</span>
        </div>
    </div>

    <!-- Entry fade-in for widgets -->
    <script>
        document.addEventListener('livewire:initialized', ()=>{
            requestAnimationFrame(()=>{
                document.querySelectorAll('.fi-widgets-container [data-theme], .fi-widgets-container .fi-section, .fi-widgets-container .fi-widget').forEach((el, i) => {
                    el.style.opacity = 0; el.style.transform = 'translateY(6px)';
                    setTimeout(() => { el.style.transition = 'opacity 320ms, transform 320ms'; el.style.opacity = 1; el.style.transform = 'translateY(0)'; }, 50 * i);
                });
            });
        });
    </script>
</x-filament-panels::page>
