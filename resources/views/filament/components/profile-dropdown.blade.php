@php
    $user = auth()->user();
    $orders = $this->getOrderCounts();
@endphp

<x-filament::dropdown width="48">
    <x-slot name="trigger">
        <img src="{{ $user->image 
            ? asset("storage/{$user->image}") 
            : asset('vendor/filament-placeholder-avatar.png') }}"
             class="h-8 w-8 rounded-full cursor-pointer" alt="Avatar" />
    </x-slot>

    <x-filament::dropdown.group label="Account">
        <x-filament::dropdown.item href="{{ CustomerResource::getUrl('edit', ['record' => auth()->id()]) }}">
            Settings
        </x-filament::dropdown.item>
        <x-filament::dropdown.item href="{{ route('filament.customer.logout') }}">
            Logout
        </x-filament::dropdown.item>
    </x-filament::dropdown.group>

    <x-filament::dropdown.group label="Stats">
        <x-filament::dropdown.item tag="div">
            Clients: {{ $this->getActiveClientsCount() }}
        </x-filament::dropdown.item>
        <x-filament::dropdown.item tag="div">
            Wallet: ${{ number_format($this->getWalletBalance(), 2) }}
        </x-filament::dropdown.item>
        <x-filament::dropdown.item tag="div">
            Orders New: {{ $orders['new'] }}
        </x-filament::dropdown.item>
        <x-filament::dropdown.item tag="div">
            Processing: {{ $orders['processing'] }}
        </x-filament::dropdown.item>
        <x-filament::dropdown.item tag="div">
            Failed: {{ $orders['failed'] }}
        </x-filament::dropdown.item>
    </x-filament::dropdown.group>
</x-filament::dropdown>
