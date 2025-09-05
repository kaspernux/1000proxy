<main class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 py-8 px-2 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <!-- Product Header -->
    <div class="max-w-7xl mx-auto mb-8 relative z-10">
        <nav class="flex items-center space-x-2 text-sm text-gray-300 mb-4">
            <a href="/" wire:navigate class="hover:text-blue-400 transition-colors duration-300">
                <x-custom-icon name="home" class="w-4 h-4" />
            </a>
            <x-custom-icon name="chevron-right" class="w-4 h-4" />
            <a href="/servers" wire:navigate class="hover:text-blue-400 transition-colors duration-300">Products</a>
            @if($this->serverPlan->category)
                <x-custom-icon name="chevron-right" class="w-4 h-4" />
                <span class="text-blue-400">{{ $this->serverPlan->category->name }}</span>
            @endif
        </nav>
    </div>
    

    <section class="max-w-7xl mx-auto relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Gallery & Info -->
            <div class="space-y-8">
                <!-- Main Product Image -->
                <div class="relative group">
                    <div class="aspect-square bg-gradient-to-br from-blue-600/20 to-purple-600/20 backdrop-blur-md rounded-3xl overflow-hidden shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-500">
                        @php
                            $imageUrl = null;
                            $altText = $this->serverPlan->name;
                            
                            // Priority 1: Plan's product image
                            if (!empty($this->serverPlan->product_image) && file_exists(storage_path('app/public/'.$this->serverPlan->product_image))) {
                                $imageUrl = asset('storage/'.$this->serverPlan->product_image);
                                $altText = $this->serverPlan->name . ' - Product Image';
                            }
                            // Priority 2: Brand image
                            elseif ($this->serverPlan->brand && !empty($this->serverPlan->brand->image) && file_exists(storage_path('app/public/'.$this->serverPlan->brand->image))) {
                                $imageUrl = asset('storage/'.$this->serverPlan->brand->image);
                                $altText = $this->serverPlan->brand->name . ' Brand Logo';
                            }
                            // Priority 3: Category image
                            elseif ($this->serverPlan->category && !empty($this->serverPlan->category->image) && file_exists(storage_path('app/public/'.$this->serverPlan->category->image))) {
                                $imageUrl = asset('storage/'.$this->serverPlan->category->image);
                                $altText = $this->serverPlan->category->name . ' Category';
                            }
                            // Priority 4: Default fallback
                            if (!$imageUrl) {
                                $imageUrl = asset('images/default-proxy.svg');
                                $altText = 'Default Proxy Server Image';
                            }
                        @endphp
                        
                        <img src="{{ $imageUrl }}" 
                             alt="{{ $altText }}"
                             class="w-full h-full object-contain p-8 transition-transform duration-500 group-hover:scale-110"
                             loading="lazy"
                             onerror="this.src='{{ asset('images/default-proxy.svg') }}';">
                        
                        <!-- Status Badges -->
                        @if($serverStatus)
                            <div class="absolute top-6 right-6">
                                <span class="px-4 py-2 rounded-full text-sm font-bold shadow-lg backdrop-blur-md border border-white/20
                                    {{ $serverStatus === 'online' ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white' }}">
                                    <span class="inline-block w-2 h-2 rounded-full mr-2 {{ $serverStatus === 'online' ? 'bg-green-300' : 'bg-red-300' }} animate-pulse"></span>
                                    {{ ucfirst($serverStatus) }}
                                </span>
                            </div>
                        @endif

                        @if($this->serverPlan->is_featured)
                            <div class="absolute top-6 left-6">
                                <span class="px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-full text-sm font-bold shadow-lg backdrop-blur-md flex items-center border border-yellow-400/30">
                                    <x-custom-icon name="star" class="w-4 h-4 mr-2" />
                                    Featured
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Left column condensed: gallery remains here; non-essential panels removed (merged into right column) -->

            </div>

            <!-- Right column: Product details, Server Details & Features and Pricing -->
            <div class="space-y-8">
                <!-- Product Title -->
                <div class="">
                    <h1 class="text-3xl lg:text-4xl font-extrabold text-white mb-4">{{ $this->serverPlan->name }}</h1>
                </div>
                <!-- Pricing Section (sticky for easier purchase) -->
                <div class="sticky top-24">
                    <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/10 backdrop-blur-md rounded-2xl p-8 border border-yellow-400/30 hover:border-yellow-300/50 transition-all duration-300 shadow-xl">
                    <h3 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400 mb-6">Choose Your Plan</h3>
                    
                    <!-- Duration Selection -->
                    <div class="mb-8">
                        <label class="block text-white font-medium mb-4">Billing Period:</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @foreach([1, 3, 6, 12] as $duration)
                            <button wire:click="$set('selectedDuration', {{ $duration }})"
                                class="relative px-4 py-4 rounded-xl text-sm font-semibold transition-all duration-300 border-2 transform hover:scale-105
                                {{ $selectedDuration === $duration 
                                    ? 'bg-gradient-to-r from-yellow-600 to-orange-600 text-white border-yellow-500 shadow-lg scale-105' 
                                    : 'bg-white/10 text-gray-300 border-white/20 hover:bg-yellow-500/20 hover:border-yellow-400/50 backdrop-blur-md' }}">
                                <div class="text-base">{{ $duration }} {{ $duration === 1 ? 'month' : 'months' }}</div>
                                @if($duration > 1)
                                    <div class="text-xs text-green-300 mt-1">Save {{ number_format((1 - (1 / (1 + ($duration * 0.05)))) * 100, 0) }}%</div>
                                @endif
                                @if($duration === 12)
                                    <div class="absolute -top-2 -right-2 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs px-2 py-1 rounded-full animate-pulse">Best Deal</div>
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
                        <div class="text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400 mb-2">${{ number_format($totalPrice, 2) }}</div>
                        <div class="text-xl text-gray-300">
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
                            <div class="flex items-center bg-white/10 rounded-lg border border-white/20 backdrop-blur-md">
                                <button wire:click='decreaseQty' 
                                        class="px-4 py-3 text-white hover:bg-yellow-600 transition-all duration-300 rounded-l-lg font-bold text-lg transform hover:scale-110">
                                    <x-custom-icon name="minus" class="w-5 h-5" />
                                </button>
                                <input type="number" wire:model='quantity' readonly 
                                       class="w-20 text-center font-bold text-white bg-transparent border-none focus:outline-none py-3">
                                <button wire:click='increaseQty' 
                                        class="px-4 py-3 text-white hover:bg-yellow-600 transition-all duration-300 rounded-r-lg font-bold text-lg transform hover:scale-110">
                                    <x-custom-icon name="plus" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <button wire:click='addToCart({{$serverPlan->id}})'
                                class="w-full bg-gradient-to-r from-yellow-600 to-orange-500 hover:from-orange-500 hover:to-yellow-600 text-white font-bold text-xl py-4 px-8 rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center justify-center space-x-3">
                            <x-custom-icon name="shopping-cart" class="w-6 h-6" wire:loading.remove wire:target='addToCart({{$serverPlan->id}})' />
                            <div class="animate-spin rounded-full h-6 w-6 border-2 border-white border-t-transparent" wire:loading wire:target='addToCart({{$serverPlan->id}})'></div>
                            <span wire:loading.remove wire:target='addToCart({{$serverPlan->id}})'>Add to Cart</span>
                            <span wire:loading wire:target='addToCart({{$serverPlan->id}})'>Adding...</span>
                        </button>
                    </div>
                    </div>
                </div>

                <!-- Customer's Purchased Configurations Section -->
                @if($customerOwnsPlan && !empty($customerConfigurations))
                <div class="bg-gradient-to-r from-green-600/20 to-emerald-600/20 backdrop-blur-md rounded-2xl p-6 border border-green-500/30 hover:border-green-400/50 transition-all duration-300 mb-6">
                    <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400 mb-4 flex items-center">
                        <x-custom-icon name="check-circle" class="w-6 h-6 mr-3 text-green-400" />
                        Your Active Configurations
                    </h3>
                    <div class="space-y-4">
                        <div class="bg-green-900/20 border border-green-500/30 rounded-lg p-4">
                            <p class="text-green-300 text-sm mb-4">
                                You've already purchased this plan! Here are your active proxy configurations:
                            </p>

                            @foreach($customerConfigurations as $index => $config)
                            <div class="bg-white/5 rounded-lg p-4 mb-4 last:mb-0">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="text-white font-semibold">Configuration #{{ $index + 1 }}</h4>
                                    <span class="text-xs text-green-400 bg-green-400/20 px-2 py-1 rounded-full">Active</span>
                                </div>

                                <!-- Configuration Link -->
                                @if(!empty($config['client_link']))
                                <div class="mb-3">
                                    <label class="block text-gray-400 text-sm mb-2">Client Configuration:</label>
                                    <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                                        <code class="text-green-300 text-xs break-all select-all">{{ $config['client_link'] }}</code>
                                    </div>
                                    <button class="mt-2 text-xs text-blue-400 hover:text-blue-300" 
                                            onclick="navigator.clipboard.writeText('{{ $config['client_link'] }}')">
                                        📋 Copy Link
                                    </button>
                                </div>
                                @endif

                                <!-- Subscription Links -->
                                @if(!empty($config['subscription_link']))
                                <div class="mb-3">
                                    <label class="block text-gray-400 text-sm mb-2">Subscription URL:</label>
                                    <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                                        <code class="text-blue-300 text-xs break-all select-all">{{ $config['subscription_link'] }}</code>
                                    </div>
                                    <button class="mt-2 text-xs text-blue-400 hover:text-blue-300" 
                                            onclick="navigator.clipboard.writeText('{{ $config['subscription_link'] }}')">
                                        📋 Copy Subscription
                                    </button>
                                </div>
                                @endif

                                <!-- QR Codes -->
                                @if(!empty($config['qr_codes']))
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if(!empty($config['qr_codes']['client']))
                                    <div class="text-center">
                                        <label class="block text-gray-400 text-sm mb-2">Client QR Code:</label>
                                        <div class="bg-white p-2 rounded-lg inline-block">
                                            <img src="{{ $config['qr_codes']['client'] }}" 
                                                 alt="Client QR Code" 
                                                 class="w-32 h-32 mx-auto">
                                        </div>
                                    </div>
                                    @endif
                                    @if(!empty($config['qr_codes']['subscription']))
                                    <div class="text-center">
                                        <label class="block text-gray-400 text-sm mb-2">Subscription QR Code:</label>
                                        <div class="bg-white p-2 rounded-lg inline-block">
                                            <img src="{{ $config['qr_codes']['subscription'] }}" 
                                                 alt="Subscription QR Code" 
                                                 class="w-32 h-32 mx-auto">
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <!-- Usage Information -->
                                @if(!empty($config['usage_info']))
                                <div class="mt-4 p-3 bg-blue-900/20 border border-blue-500/30 rounded-lg">
                                    <h5 class="text-blue-300 font-medium mb-2">Usage Information:</h5>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        @if(isset($config['usage_info']['traffic_used_mb']))
                                        <div>
                                            <span class="text-gray-400">Traffic Used:</span>
                                            <span class="text-white">{{ number_format($config['usage_info']['traffic_used_mb']) }} MB</span>
                                        </div>
                                        @endif
                                        @if(isset($config['usage_info']['traffic_limit_mb']))
                                        <div>
                                            <span class="text-gray-400">Traffic Limit:</span>
                                            <span class="text-white">{{ number_format($config['usage_info']['traffic_limit_mb']) }} MB</span>
                                        </div>
                                        @endif
                                        @if(isset($config['usage_info']['expires_at']) && $config['usage_info']['expires_at'])
                                        <div>
                                            <span class="text-gray-400">Expires:</span>
                                            <span class="text-white">{{ \Carbon\Carbon::parse($config['usage_info']['expires_at'])->format('M j, Y') }}</span>
                                        </div>
                                        @endif
                                        @if(isset($config['usage_info']['status']))
                                        <div>
                                            <span class="text-gray-400">Status:</span>
                                            <span class="text-green-400 capitalize">{{ $config['usage_info']['status'] }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endforeach

                            <!-- Quick Action Buttons -->
                            <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-green-500/20">
                                <a href="/customer/my-active-servers" 
                                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg transition-colors duration-200">
                                    <x-custom-icon name="cog-6-tooth" class="w-4 h-4 mr-2" />
                                    Manage Servers
                                </a>
                                <a href="/customer/orders" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors duration-200">
                                    <x-custom-icon name="document-text" class="w-4 h-4 mr-2" />
                                    View Orders
                                </a>
                                <button onclick="window.print()" 
                                        class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors duration-200">
                                    <x-custom-icon name="printer" class="w-4 h-4 mr-2" />
                                    Print Configs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($this->serverPlan->server)
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 hover:border-white/30 transition-all duration-300 mb-6">
                    <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400 mb-4 flex items-center">
                        <x-custom-icon name="server" class="w-6 h-6 mr-3 text-blue-400" />
                        Server Details &amp; Features
                    </h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex justify-between items-center p-3 bg-white/5 rounded-lg">
                                <span class="text-gray-400">Location:</span>
                                <span class="text-white font-medium">{{ $this->serverPlan->server->location }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-white/5 rounded-lg">
                                <span class="text-gray-400">Server Name:</span>
                                <span class="text-white font-medium">{{ $this->serverPlan->server->name }}</span>
                            </div>
                        </div>

                        @if($this->serverPlan->server->ip_address)
                        @php
                            $fullIp = $this->serverPlan->server->ip_address;
                            $maskedShort = preg_replace('/^(\d+)\..*/', '$1.xxx.xxx.xxx', $fullIp);
                        @endphp
                        <div class="flex justify-between items-center p-3 bg-white/5 rounded-lg">
                            <span class="text-gray-400">IP Address:</span>
                            <div class="text-right">
                                <div class="text-white font-mono bg-gray-700/50 px-3 py-1 rounded border border-gray-600/50">{{ $maskedShort }}</div>
                            </div>
                        </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4 mt-2">
                            <div class="p-3 bg-white/5 rounded-lg text-center">
                                <div class="text-3xl font-bold text-green-400">{{ number_format($serverHealth['uptime'] ?? 99.9, 2) }}%</div>
                                <div class="text-gray-300 text-sm">Uptime</div>
                            </div>
                            <div class="p-3 bg-white/5 rounded-lg text-center">
                                <div class="text-3xl font-bold text-blue-400">{{ number_format($serverHealth['response_time'] ?? 50, 2) }}ms</div>
                                <div class="text-gray-300 text-sm">Response Time</div>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-white/5 rounded-lg">
                            <h4 class="text-sm text-gray-300 font-semibold mb-3">Key Features</h4>
                            <ul class="text-gray-300 list-inside space-y-2">
                                <li class="flex items-center"><x-custom-icon name="check-circle" class="w-4 h-4 text-green-400 mr-2" /> 99.9% Uptime Guarantee</li>
                                <li class="flex items-center"><x-custom-icon name="check-circle" class="w-4 h-4 text-green-400 mr-2" /> 24/7 Technical Support</li>
                                <li class="flex items-center"><x-custom-icon name="check-circle" class="w-4 h-4 text-green-400 mr-2" /> Instant Configuration</li>
                                <li class="flex items-center"><x-custom-icon name="check-circle" class="w-4 h-4 text-green-400 mr-2" /> Global Network Access</li>
                            </ul>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            </div>
        </div>
    </section>
</main>