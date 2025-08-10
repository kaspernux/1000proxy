<x-filament-widgets::widget>
    <x-filament::section heading="Server Operations Overview">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Online</div>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $online }}</div>
            </div>
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Offline</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $offline }}</div>
            </div>
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Maint.</div>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $maintenance }}</div>
            </div>
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Avg Uptime</div>
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $avg_uptime }}%</div>
            </div>
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Active Clients</div>
                <div class="text-2xl font-bold text-fuchsia-600 dark:text-fuchsia-400">{{ $active_clients }}</div>
            </div>
            <div class="p-4 rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Bandwidth (GB)</div>
                <div class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">{{ $total_bandwidth_gb }}</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
