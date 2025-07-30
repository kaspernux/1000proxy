@extends('layouts.app')

@section('content')

<main class="min-h-screen bg-gradient-to-br from-green-900 to-green-600 py-8 px-2 sm:px-6 lg:px-8 flex flex-col items-center">
    <section class="w-full max-w-7xl mx-auto rounded-2xl border-2 border-double border-yellow-600 shadow-xl bg-white/5 backdrop-blur-md p-4 sm:p-8 md:p-12 flex flex-col md:flex-row gap-8 md:gap-12">
        <!-- Product Image & Status -->
        <aside class="w-full md:w-1/2 flex flex-col gap-6">
            <div class="relative rounded-xl overflow-hidden shadow-lg aspect-square bg-green-800 flex items-center justify-center">
                <img x-bind:src="mainImage" :src="mainImage" alt="{{ $this->serverPlan->name }}" class="object-contain w-full h-full max-h-96 transition-transform duration-300 hover:scale-105" x-data="{ mainImage: '{{url('storage/'.$this->serverPlan->product_image)}}' }">
                @if($serverStatus)
                <span class="absolute top-4 right-4 px-4 py-1 rounded-full text-xs font-bold tracking-wide shadow-lg
                    {{ $serverStatus === 'online' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                    {{ ucfirst($serverStatus) }}
                </span>
                @endif
            </div>
            @if($serverHealth)
            <div class="flex flex-col gap-2 bg-green-900/80 rounded-lg p-4 border border-yellow-600">
                <h3 class="text-white font-semibold mb-2 text-lg">Server Performance</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-300">{{ number_format($serverHealth['uptime'] ?? 99.9, 2) }}%</div>
                        <div class="text-xs text-white/70">Uptime</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-300">{{ number_format($serverHealth['response_time'] ?? 50, 2) }}ms</div>
                        <div class="text-xs text-white/70">Response Time</div>
                    </div>
                </div>
            </div>
            @endif
            <div class="flex items-center gap-2 mt-4 text-white text-sm bg-green-800/80 rounded-lg px-4 py-2">
                <x-custom-icon name="server" class="w-4 h-4 text-yellow-400" />
                <span>Instant configuration delivery to your account</span>
            </div>
        </aside>

        <!-- Product Details -->
        <section class="w-full md:w-1/2 flex flex-col gap-6">
            <!-- Breadcrumbs -->
            <nav class="text-xs text-yellow-200 mb-2 flex flex-wrap gap-1 items-center">
                <a href="/" wire:navigate class="hover:text-yellow-400">Home</a>
                <span>/</span>
                <a href="/servers" wire:navigate class="hover:text-yellow-400">Servers</a>
                @if($this->serverPlan->category)
                    <span>/</span>
                    <span>{{ $this->serverPlan->category->name }}</span>
                @endif
            </nav>
            <h1 class="text-2xl md:text-4xl font-extrabold text-white mb-2">{{ $this->serverPlan->name }}</h1>
            <div class="flex flex-wrap gap-2 mb-4">
                @if($this->serverPlan->brand)
                    <span class="px-3 py-1 bg-blue-600/20 text-blue-200 rounded-full text-xs font-semibold">{{ $this->serverPlan->brand->name }}</span>
                @endif
                @if($this->serverPlan->category)
                    <span class="px-3 py-1 bg-green-600/20 text-green-200 rounded-full text-xs font-semibold">{{ $this->serverPlan->category->name }}</span>
                @endif
            </div>

            <!-- Pricing Calculator -->
            <div class="bg-yellow-50/10 border border-yellow-600 rounded-lg p-4 flex flex-col gap-3">
                <label class="block text-white font-medium mb-1">Select Duration:</label>
                <div class="flex gap-2 flex-wrap">
                    @foreach([1, 3, 6, 12] as $duration)
                    <button wire:click="$set('selectedDuration', {{ $duration }})"
                        class="px-3 py-2 rounded text-xs font-semibold transition-all duration-200
                        {{ $selectedDuration === $duration ? 'bg-yellow-600 text-white shadow' : 'bg-white/20 text-yellow-200 hover:bg-yellow-600 hover:text-white' }}">
                        {{ $duration }} {{ $duration === 1 ? 'month' : 'months' }}
                        @if($duration > 1)
                            <span class="block text-[10px] text-green-300 font-normal">Save {{ number_format((1 - (1 / (1 + ($duration * 0.05)))) * 100, 2) }}%</span>
                        @endif
                    </button>
                    @endforeach
                </div>
                @php
                    $basePrice = $this->serverPlan->price;
                    $discountMultiplier = 1;
                    if ($selectedDuration >= 3) $discountMultiplier = 0.95;
                    if ($selectedDuration >= 6) $discountMultiplier = 0.90;
                    if ($selectedDuration >= 12) $discountMultiplier = 0.85;
                    $monthlyPrice = $basePrice * $discountMultiplier;
                    $totalPrice = $monthlyPrice * $selectedDuration;
                    $savings = ($basePrice - $monthlyPrice) * $selectedDuration;
                @endphp
                <div class="flex flex-col items-center gap-1">
                    <div class="text-3xl md:text-4xl font-bold text-yellow-400">${{ number_format($totalPrice, 2) }}</div>
                    <div class="text-white/80 text-sm">${{ number_format($monthlyPrice, 2) }}/month
                        @if($savings > 0)
                            <span class="text-green-400 ml-2">Save ${{ number_format($savings, 2) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Product Description -->
            <div class="prose prose-invert max-w-none text-white text-base leading-relaxed bg-green-900/60 rounded-lg p-4">
                {{ $this->serverPlan->description ?? 'Premium proxy service with high-speed connections and reliable performance.' }}
            </div>

            <!-- Server Specifications -->
            @if($this->serverPlan->server)
            <div class="bg-green-800/80 border border-yellow-600 rounded-lg p-4 flex flex-col gap-2">
                <h3 class="text-white font-semibold mb-2 text-lg">Server Details</h3>
                <div class="flex flex-col gap-1 text-sm">
                    <div class="flex justify-between"><span class="text-white/70">Location:</span><span class="text-white">{{ $this->serverPlan->server->location }}</span></div>
                    <div class="flex justify-between"><span class="text-white/70">Server Name:</span><span class="text-white">{{ $this->serverPlan->server->name }}</span></div>
                    @if($this->serverPlan->server->ip_address)
                    <div class="flex justify-between"><span class="text-white/70">IP Address:</span><span class="text-white font-mono">{{ $this->serverPlan->server->ip_address }}</span></div>
                    @endif
                </div>
            </div>
            @endif
            @if (!empty($serverPlan->description))
                <div class="prose prose-invert max-w-none text-white mt-2">{!! Str::markdown($serverPlan->description ?? 'No description available.') !!}</div>
            @endif

            <!-- Quantity & Add to Cart -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mt-4">
                <div class="flex flex-col items-center w-full sm:w-32">
                    <label class="w-full pb-1 text-lg font-semibold text-white border-b border-yellow-600">Quantity</label>
                    <div class="flex flex-row w-full h-10 mt-2 bg-transparent rounded-lg overflow-hidden border border-yellow-600">
                        <button wire:click='decreaseQty' class="w-10 h-full text-white bg-green-700 hover:bg-yellow-600 transition-all duration-150 font-bold text-xl">-</button>
                        <input type="number" wire:model='quantity' readonly class="w-full text-center font-bold text-white bg-green-900 border-none focus:outline-none" placeholder="1">
                        <button wire:click='increaseQty' class="w-10 h-full text-white bg-green-700 hover:bg-yellow-600 transition-all duration-150 font-bold text-xl">+</button>
                    </div>
                </div>
                <button wire:click='addToCart({{$serverPlan->id}})'
                    class="w-full sm:w-auto px-8 py-3 bg-yellow-600 rounded-lg text-white font-bold text-lg shadow hover:bg-yellow-500 transition-all duration-200 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target='addToCart({{$serverPlan->id}})'>Add to Cart</span>
                    <span wire:loading wire:target='addToCart({{$serverPlan->id}})'>Adding...</span>
                </button>
            </div>
        </section>
    </section>
</main>
@endsection
