@extends('layouts.app')

@section('content')

<main class="min-h-screen bg-gradient-to-br from-green-900 via-green-800 to-green-600 py-8 px-2 sm:px-6 lg:px-8">
    <!-- Product Header -->
    <div class="max-w-7xl mx-auto mb-8">
        <nav class="flex items-center space-x-2 text-sm text-green-200 mb-4">
            <a href="/" wire:navigate class="hover:text-yellow-400 transition-colors">
                <x-custom-icon name="home" class="w-4 h-4" />
            </a>
            <x-custom-icon name="chevron-right" class="w-4 h-4" />
            <a href="/servers" wire:navigate class="hover:text-yellow-400 transition-colors">Products</a>
            @if($this->serverPlan->category)
                <x-custom-icon name="chevron-right" class="w-4 h-4" />
                <span class="text-yellow-400">{{ $this->serverPlan->category->name }}</span>
            @endif
        </nav>
    </div>

    <section class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Gallery & Info -->
            <div class="space-y-8">
                <!-- Main Product Image -->
                <div class="relative group">
                    <div class="aspect-square bg-gradient-to-br from-green-800 to-green-700 rounded-3xl overflow-hidden shadow-2xl border border-white/10">
                        <img src="{{ url('storage/'.$this->serverPlan->product_image) }}" 
                             alt="{{ $this->serverPlan->name }}"
                             class="w-full h-full object-contain p-8 transition-transform duration-500 group-hover:scale-110">
                        
                        <!-- Status Badges -->
                        @if($serverStatus)
                            <div class="absolute top-6 right-6">
                                <span class="px-4 py-2 rounded-full text-sm font-bold shadow-lg backdrop-blur-sm
                                    {{ $serverStatus === 'online' ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white' }}">
                                    <span class="inline-block w-2 h-2 rounded-full mr-2 {{ $serverStatus === 'online' ? 'bg-green-300' : 'bg-red-300' }} animate-pulse"></span>
                                    {{ ucfirst($serverStatus) }}
                                </span>
                            </div>
                        @endif

                        @if($this->serverPlan->featured)
                            <div class="absolute top-6 left-6">
                                <span class="px-4 py-2 bg-yellow-500 text-black rounded-full text-sm font-bold shadow-lg backdrop-blur-sm flex items-center">
                                    <x-custom-icon name="star" class="w-4 h-4 mr-2" />
                                    Featured
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Server Performance Stats -->
                @if($serverHealth)
                <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 border border-white/10">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <x-custom-icon name="chart-bar" class="w-6 h-6 mr-3 text-yellow-400" />
                        Performance Metrics
                    </h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="text-center">
                            <div class="text-4xl font-bold text-green-400 mb-2">{{ number_format($serverHealth['uptime'] ?? 99.9, 2) }}%</div>
                            <div class="text-green-200">Uptime</div>
                            <div class="w-full bg-green-900 rounded-full h-2 mt-2">
                                <div class="bg-gradient-to-r from-green-500 to-green-400 h-2 rounded-full" style="width: {{ $serverHealth['uptime'] ?? 99.9 }}%"></div>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-blue-400 mb-2">{{ number_format($serverHealth['response_time'] ?? 50, 2) }}ms</div>
                            <div class="text-blue-200">Response Time</div>
                            <div class="w-full bg-green-900 rounded-full h-2 mt-2">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-400 h-2 rounded-full" style="width: {{ 100 - min(($serverHealth['response_time'] ?? 50) / 5, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Features List -->
                <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 border border-white/10">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <x-custom-icon name="shield-check" class="w-6 h-6 mr-3 text-green-400" />
                        Key Features
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-center text-green-200">
                            <x-custom-icon name="check-circle" class="w-5 h-5 mr-3 text-green-400" />
                            <span>99.9% Uptime Guarantee</span>
                        </div>
                        <div class="flex items-center text-green-200">
                            <x-custom-icon name="check-circle" class="w-5 h-5 mr-3 text-green-400" />
                            <span>24/7 Technical Support</span>
                        </div>
                        <div class="flex items-center text-green-200">
                            <x-custom-icon name="check-circle" class="w-5 h-5 mr-3 text-green-400" />
                            <span>Instant Configuration</span>
                        </div>
                        <div class="flex items-center text-green-200">
                            <x-custom-icon name="check-circle" class="w-5 h-5 mr-3 text-green-400" />
                            <span>Global Network Access</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Details & Purchase -->
            <div class="space-y-8">
                <!-- Product Title & Info -->
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">{{ $this->serverPlan->name }}</h1>
                    
                    <!-- Tags -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        @if($this->serverPlan->brand)
                            <span class="px-4 py-2 bg-blue-600/20 text-blue-200 rounded-xl text-sm font-medium border border-blue-500/30">
                                {{ $this->serverPlan->brand->name }}
                            </span>
                        @endif
                        @if($this->serverPlan->category)
                            <span class="px-4 py-2 bg-green-600/20 text-green-200 rounded-xl text-sm font-medium border border-green-500/30">
                                {{ $this->serverPlan->category->name }}
                            </span>
                        @endif
                    </div>

                    <!-- Description -->
                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 border border-white/10">
                        <p class="text-green-100 text-lg leading-relaxed">
                            {{ $this->serverPlan->description ?? 'Premium proxy service with high-speed connections and reliable performance designed for professional use.' }}
                        </p>
                    </div>
                </div>

                <!-- Pricing Section -->
                <div class="bg-gradient-to-br from-yellow-600/20 to-yellow-500/10 backdrop-blur-md rounded-2xl p-8 border border-yellow-500/30">
                    <h3 class="text-2xl font-bold text-white mb-6">Choose Your Plan</h3>
                    
                    <!-- Duration Selection -->
                    <div class="mb-8">
                        <label class="block text-white font-medium mb-4">Billing Period:</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @foreach([1, 3, 6, 12] as $duration)
                            <button wire:click="$set('selectedDuration', {{ $duration }})"
                                class="relative px-4 py-4 rounded-xl text-sm font-semibold transition-all duration-200 border-2
                                {{ $selectedDuration === $duration 
                                    ? 'bg-yellow-600 text-white border-yellow-500 shadow-lg transform scale-105' 
                                    : 'bg-white/10 text-green-200 border-white/20 hover:bg-yellow-600/20 hover:border-yellow-500/50' }}">
                                <div class="text-base">{{ $duration }} {{ $duration === 1 ? 'month' : 'months' }}</div>
                                @if($duration > 1)
                                    <div class="text-xs text-green-300 mt-1">Save {{ number_format((1 - (1 / (1 + ($duration * 0.05)))) * 100, 0) }}%</div>
                                @endif
                                @if($duration === 12)
                                    <div class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">Best Deal</div>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Price Display -->
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
                    
                    <div class="text-center mb-8">
                        <div class="text-5xl font-bold text-yellow-400 mb-2">${{ number_format($totalPrice, 2) }}</div>
                        <div class="text-xl text-white/80">
                            ${{ number_format($monthlyPrice, 2) }}/month
                            @if($savings > 0)
                                <span class="text-green-400 ml-4 font-semibold">Save ${{ number_format($savings, 2) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Quantity & Add to Cart -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-center space-x-4">
                            <label class="text-white font-medium">Quantity:</label>
                            <div class="flex items-center bg-white/10 rounded-lg border border-white/20">
                                <button wire:click='decreaseQty' 
                                        class="px-4 py-3 text-white hover:bg-yellow-600 transition-colors rounded-l-lg font-bold text-lg">
                                    <x-custom-icon name="minus" class="w-5 h-5" />
                                </button>
                                <input type="number" wire:model='quantity' readonly 
                                       class="w-20 text-center font-bold text-white bg-transparent border-none focus:outline-none py-3">
                                <button wire:click='increaseQty' 
                                        class="px-4 py-3 text-white hover:bg-yellow-600 transition-colors rounded-r-lg font-bold text-lg">
                                    <x-custom-icon name="plus" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <button wire:click='addToCart({{$serverPlan->id}})'
                                class="w-full bg-gradient-to-r from-yellow-600 to-yellow-500 hover:from-yellow-500 hover:to-yellow-400 text-white font-bold text-xl py-4 px-8 rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center justify-center space-x-3">
                            <x-custom-icon name="shopping-cart" class="w-6 h-6" wire:loading.remove wire:target='addToCart({{$serverPlan->id}})' />
                            <div class="animate-spin rounded-full h-6 w-6 border-2 border-white border-t-transparent" wire:loading wire:target='addToCart({{$serverPlan->id}})'></div>
                            <span wire:loading.remove wire:target='addToCart({{$serverPlan->id}})'>Add to Cart</span>
                            <span wire:loading wire:target='addToCart({{$serverPlan->id}})'>Adding...</span>
                        </button>
                    </div>
                </div>

                <!-- Server Specifications -->
                @if($this->serverPlan->server)
                <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 border border-white/10">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <x-custom-icon name="server" class="w-6 h-6 mr-3 text-blue-400" />
                        Server Specifications
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-white/70">Location:</span>
                                <span class="text-white font-medium">{{ $this->serverPlan->server->location }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-white/70">Server Name:</span>
                                <span class="text-white font-medium">{{ $this->serverPlan->server->name }}</span>
                            </div>
                            @if($this->serverPlan->server->ip_address)
                            <div class="flex justify-between items-center">
                                <span class="text-white/70">IP Address:</span>
                                <span class="text-white font-mono bg-green-900/50 px-3 py-1 rounded">{{ $this->serverPlan->server->ip_address }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Trust Indicators -->
                <div class="bg-white/5 backdrop-blur-md rounded-2xl p-6 border border-white/10">
                    <div class="grid grid-cols-3 gap-6 text-center">
                        <div>
                            <div class="text-3xl font-bold text-green-400 mb-2">99.9%</div>
                            <div class="text-green-200 text-sm">Uptime</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-blue-400 mb-2">24/7</div>
                            <div class="text-blue-200 text-sm">Support</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-yellow-400 mb-2">1000+</div>
                            <div class="text-yellow-200 text-sm">Happy Customers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

@endsection
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
