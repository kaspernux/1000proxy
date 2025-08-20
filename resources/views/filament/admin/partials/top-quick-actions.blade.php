<div class="grid gap-2 sm:gap-3 grid-cols-2 md:grid-cols-4">
    <a href="{{ route('filament.admin.pages.server-management-dashboard') }}" class="group flex items-center gap-2 rounded-lg px-3 py-2 bg-emerald-600 text-white hover:bg-emerald-700">
        <x-heroicon-o-server-stack class="size-4" />
        <span class="text-sm">Servers</span>
    </a>
    <a href="{{ route('filament.admin.proxy-shop.resources.orders.index') }}" class="group flex items-center gap-2 rounded-lg px-3 py-2 bg-sky-600 text-white hover:bg-sky-700">
        <x-heroicon-o-shopping-cart class="size-4" />
        <span class="text-sm">Orders</span>
    </a>
    <a href="{{ route('filament.admin.resources.activity-logs.index') }}" class="group flex items-center gap-2 rounded-lg px-3 py-2 bg-amber-600 text-white hover:bg-amber-700">
        <x-heroicon-o-clipboard-document-check class="size-4" />
        <span class="text-sm">Activity</span>
    </a>
    <a href="{{ route('filament.admin.resources.business-intelligence.index') }}" class="group flex items-center gap-2 rounded-lg px-3 py-2 bg-indigo-600 text-white hover:bg-indigo-700">
        <x-heroicon-o-chart-bar class="size-4" />
        <span class="text-sm">BI</span>
    </a>
</div>
