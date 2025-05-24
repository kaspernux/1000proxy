@php
    $user = auth()->user();
    $orders = $this->getOrderCounts();
@endphp

<x-filament::dropdown
    placement="bottom-end"
    width="xs"
>
    <x-slot name="trigger">
        <button type="button" class="focus:outline-none">
            <img
                class="h-8 w-8 rounded-full"
                src="{{ $user->image ? asset('storage/' . $user->image) : asset('vendor/filament-placeholder-avatar.png') }}"
                alt="User Avatar"
            />
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item
            :href="App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource::getUrl('edit', ['record' => $user->id])"
            icon="heroicon-o-cog-6-tooth"
            tag="a"
        >
            Settings
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item
            href="{{ route('filament.customer.logout') }}"
            icon="heroicon-o-arrow-left-start-on-rectangle"
            tag="a"
        >
            Logout
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item tag="div" disabled>
            <div class="text-xs uppercase text-gray-400 px-3 pt-2 pb-1">Stats</div>
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item tag="div">
            Clients: {{ $this->getActiveClientsCount() }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item tag="div">
            Wallet: ${{ number_format($this->getWalletBalance(), 2) }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item tag="div">
            Orders New: {{ $orders['new'] }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item tag="div">
            Processing: {{ $orders['processing'] }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item tag="div">
            Failed: {{ $orders['failed'] }}
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>
