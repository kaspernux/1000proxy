<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 max-w-auto py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    {{-- Enhanced Product Detail Section --}}
    <section class="overflow-hidden container mx-auto px-4 max-w-7xl border-2 rounded-lg border-double border-yellow-600 py-11 font-mono dark:bg-green-400">
        <div class="max-w-6xl px-4 py-4 mx-auto lg:py-8 md:px-6">
            <div class="flex flex-wrap -mx-4">
                {{-- Product Image Section --}}
                <div class="mb-8 md:w-1/2 md:mb-0" x-data="{ mainImage: '{{url('storage/'.$this->serverPlan->product_image)}}' }">
                    <div class="top-0 z-50 py-6 items-center">
                        <div class="relative mb-6 lg:mb-10 lg:h-2/4">
                            <img x-bind:src="mainImage" alt="{{ $this->serverPlan->name }}" class="object-cover w-full lg:h-full rounded-lg">

                            {{-- Server Status Indicator --}}
                            @if($serverStatus)
                            <div class="absolute top-4 right-4 px-3 py-1 rounded-full text-sm font-semibold
                                {{ $serverStatus === 'online' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                                {{ ucfirst($serverStatus) }}
                            </div>
                            @endif
                        </div>

                        {{-- Server Health Metrics --}}
                        @if($serverHealth)
                        <div class="mb-6 p-4 bg-white/10 backdrop-blur-sm rounded-lg">
                            <h3 class="text-white font-semibold mb-3">Server Performance</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-400">{{ $serverHealth['uptime'] ?? '99.9' }}%</div>
                                    <div class="text-sm text-white/70">Uptime</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-400">{{ $serverHealth['response_time'] ?? '< 50' }}ms</div>
                                    <div class="text-sm text-white/70">Response Time</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Quick Info Section --}}
                        <div class="px-6 pb-6 mt-6 border-t border-accent-yellow dark:border-green-400">
                            <div class="flex flex-wrap items-center mt-6">
                                <x-custom-icon name="server" class="w-4 h-4 text-white mr-2" />
                                <h2 class="text-sm font-medium text-white dark:text-white">Instant configuration delivery to your account</h2>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Details Section --}}
                <div class="w-full py-6 pt-6 md:w-1/2 bg-green-900 border-2 items-center rounded-lg border-double border-yellow-600">
                    <div class="lg:pl-20">
                        <div class="mb-8 text-white dark:text-white [&>*]:mx-3 [&>ul]:list-disc [&>ul]:mx-8 [&>*]:text-white [&>*]:dark:text-white">
                            {{-- Product Title and Breadcrumbs --}}
                            <div class="mb-4">
                                <nav class="text-sm text-white/70 mb-2">
                                    <a href="/" wire:navigate class="hover:text-white">Home</a>
                                    <span class="mx-2">/</span>
                                    <a href="/servers" wire:navigate class="hover:text-white">Servers</a>
                                    @if($this->serverPlan->category)
                                        <span class="mx-2">/</span>
                                        <span>{{ $this->serverPlan->category->name }}</span>
                                    @endif
                                </nav>
                                <h2 class="max-w-xl mb-6 text-2xl text-white font-bold dark:text-white md:text-4xl">
                                    {{ $this->serverPlan->name }}
                                </h2>

                                {{-- Brand & Category Info --}}
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @if($this->serverPlan->brand)
                                        <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-full text-sm">
                                            {{ $this->serverPlan->brand->name }}
                                        </span>
                                    @endif
                                    @if($this->serverPlan->category)
                                        <span class="px-3 py-1 bg-green-600/20 text-green-300 rounded-full text-sm">
                                            {{ $this->serverPlan->category->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Enhanced Pricing Calculator --}}
                            <div class="mb-6 p-4 bg-white/10 backdrop-blur-sm rounded-lg">
                                <div class="mb-4">
                                    <label class="block text-white font-medium mb-2">Select Duration:</label>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        @foreach([1, 3, 6, 12] as $duration)
                                        <button wire:click="$set('selectedDuration', {{ $duration }})"
                                                class="px-3 py-2 rounded text-sm font-medium transition
                                                {{ $selectedDuration === $duration
                                                    ? 'bg-green-600 text-white'
                                                    : 'bg-white/20 text-white hover:bg-white/30' }}">
                                            {{ $duration }} {{ $duration === 1 ? 'month' : 'months' }}
                                            @if($duration > 1)
                                                <div class="text-xs text-green-300">Save {{ (1 - (1 / (1 + ($duration * 0.05)))) * 100 }}%</div>
                                            @endif
                                        </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-4">
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

                                    <div class="text-center">
                                        <div class="text-4xl font-bold text-white mb-2">
                                            ${{ number_format($totalPrice, 2) }}
                                        </div>
                                        <div class="text-white/70">
                                            ${{ number_format($monthlyPrice, 2) }}/month
                                            @if($savings > 0)
                                                <span class="text-green-400 ml-2">Save ${{ number_format($savings, 2) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Product Description --}}
                            <p class="max-w-md text-white dark:text-white mb-6">
                                {{ $this->serverPlan->description ?? 'Premium proxy service with high-speed connections and reliable performance.' }}
                            </p>

                            {{-- Server Specifications --}}
                            @if($this->serverPlan->server)
                            <div class="mb-6 p-4 bg-white/10 backdrop-blur-sm rounded-lg">
                                <h3 class="text-white font-semibold mb-3">Server Details</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-white/70">Location:</span>
                                        <span class="text-white">{{ $this->serverPlan->server->location }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/70">Server Name:</span>
                                        <span class="text-white">{{ $this->serverPlan->server->name }}</span>
                                    </div>
                                    @if($this->serverPlan->server->ip_address)
                                    <div class="flex justify-between">
                                        <span class="text-white/70">IP Address:</span>
                                        <span class="text-white font-mono">{{ $this->serverPlan->server->ip_address }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if (!empty($serverPlan->description))
                                {!! Str::markdown($serverPlan->description ?? 'No description available.') !!}
                            @endif
                            </p>
                        </div>
                        <div class="w-32 mb-8">
                            <label for=""
                                class="w-full pb-1 text-xl font-semibold text-white border-b border-yellow-600 dark:border-yellow-600 dark:text-white">Quantity</label>
                            <div class="relative flex flex-row w-full h-10 mt-6 bg-transparent rounded-lg">
                                <button wire:click='decreaseQty'
                                    class="w-20 h-full text-white bg-green-700 rounded-l outline-none cursor-pointer dark:hover:bg-yellow-600 dark:text-white hover:text-white dark:bg-green-700 hover:bg-green-400">
                                    <span class="m-auto text-2xl font-mono text-white">-</span>
                                </button>
                                <input type="number" wire:model='quantity' readonly
                                    class="flex justify-center items-center w-full font-bold text-center text-white placeholder-green-700 bg-green-400 outline-none border-green-700 dark:border-green-700 dark:text-white dark:placeholder-green-700 dark:bg-green-900 focus:outline-none text-md hover:yellow-600"
                                    placeholder="1">
                                <button wire:click='increaseQty'
                                    class="w-20 h-full text-white bg-green-700 rounded-r outline-none cursor-pointer dark:hover:bg-yellow-600 dark:text-white hover:text-white dark:bg-green-700 hover:bg-green-400">
                                    <span class="m-auto text-2xl font-mono text-white">+</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <button wire:click='addToCart({{$serverPlan->id}})'
                                class="w-full p-4 bg-yellow-600 rounded-md lg:w-2/5 dark:text-white text-white hover:bg-green-400 dark:bg-green-400 dark:hover:bg-green-400">
                                <span wire:loading.remove wire:target='addToCart({{$serverPlan->id}})'>Add to Cart</span>
                                <span wire:loading wire:target='addToCart({{$serverPlan->id}})'>Adding...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
